<?php

declare(strict_types=1);

namespace Admin\Controls;

use Admin\Administrator;
use Admin\DB\ChangelogRepository;
use Base\ShopsConfig;
use Nette\Http\Session;
use Nette\Localization\Translator;
use Security\DB\IUser;
use StORM\Collection;
use StORM\Connection;
use StORM\ICollection;
use StORM\IEntityParent;

class AdminGridFactory
{
	/**
	 * @var array<int>
	 */
	private array $itemsPerPage;

	private bool $showItemsPerPage;

	private ?int $defaultOnPage;

	public function __construct(
		private readonly Administrator $administrator,
		private readonly AdminFormFactory $formFactory,
		private readonly Session $session,
		private readonly Translator $translator,
		private readonly ChangelogRepository $changelogRepository,
		private readonly ShopsConfig $shopsConfig,
		private readonly Connection $connection,
	) {
	}

	public function setItemsPerPage(array $items): void
	{
		$this->itemsPerPage = $items;
	}

	public function setShowItemsPerPage(bool $show): void
	{
		$this->showItemsPerPage = $show;
	}

	public function setDefaultOnPage(?int $defaultOnPage = null): void
	{
		$this->defaultOnPage = $defaultOnPage;
	}
	
	public function create(
		ICollection $source,
		?int $defaultOnPage = null,
		?string $defaultOrderExpression = null,
		?string $defaultOrderDir = null,
		bool $encodeId = false,
		bool $useShops = true,
		bool $filterShops = true,
	): AdminGrid {
		if ($useShops) {
			$shop = $this->shopsConfig->getSelectedShop();
			$shopsAvailable = $shop && $source instanceof IEntityParent && $source->getRepository()->getStructure()->getRelation('shop');

			if ($filterShops && $shopsAvailable) {
				$source->where('this.fk_shop = :shopVar OR this.fk_shop IS NULL', [':shopVar' => $shop->getPK()]);
			}
		}

		$grid = new AdminGrid($source, $defaultOnPage, $defaultOrderExpression, $defaultOrderDir, $encodeId, $this->session);
		$grid->setFormsFactory($this->formFactory);
		$grid->setItemsPerPage($this->itemsPerPage);
		$grid->setShowItemsPerPage($this->showItemsPerPage);
		$grid->setChangelogRepository($this->changelogRepository);

		if ($this->defaultOnPage && !$defaultOnPage) {
			$grid->setDefaultOnPage($this->defaultOnPage);
		}

		$grid->setTranslator($this->translator);

		$grid->setItemCountCallback(function (Collection $collection): int {
			$collection->setSelect([])->setGroupBy([])->setOrderBy([]);

			$subCollection = AdminGrid::processCollectionBaseFrom($collection, useOrder: false, join: false);
			$subCollection->setSelect(['DISTINCT this.uuid']);

			return $this->connection->rows()
				->setFrom(['agg' => "({$subCollection->getSql()})"], $collection->getVars())
				->enum('agg.uuid', unique: false);
		});
		
		$grid->onUpdateRow[] = function ($object) use ($grid): void {
			if ($grid->entityName && $this->administrator->getIdentity() instanceof IUser) {
				$this->changelogRepository->createOne([
					'user' => $this->administrator->getIdentity()->getAccount()->login,
					'entity' => $grid->entityName,
					'objectId' => $object,
					'type' => 'update',
				]);
			}
		};
		
		$grid->onDeleteRow[] = function ($object) use ($grid): void {
			if ($grid->entityName && $this->administrator->getIdentity() instanceof IUser) {
				$this->changelogRepository->createOne([
					'user' => $this->administrator->getIdentity()->getAccount()->login,
					'entity' => $grid->entityName,
					'objectId' => $object->uuid,
					'type' => 'delete',
				]);
			}
		};

		if ($useShops && $shopsAvailable) {
			$grid->addColumnTextFit('<i class="fas fa-store-alt"></i>', 'shop', '%s');
		}
		
		return $grid;
	}
}
