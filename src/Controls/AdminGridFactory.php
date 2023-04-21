<?php

declare(strict_types=1);

namespace Admin\Controls;

use Admin\Administrator;
use Admin\DB\ChangelogRepository;
use Base\DB\ShopRepository;
use Nette\Http\Session;
use Nette\Localization\Translator;
use Security\DB\IUser;
use StORM\Collection;
use StORM\DIConnection;
use StORM\ICollection;

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
		private readonly ShopRepository $shopRepository,
		private readonly DIConnection $connection,
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
	
	public function create(Collection $source, ?int $defaultOnPage = null, ?string $defaultOrderExpression = null, ?string $defaultOrderDir = null, bool $encodeId = false): AdminGrid
	{
		if (($shop = $this->shopRepository->getSelectedShop()) && $source->getRepository()->getStructure()->getRelation('shop')) {
			$source->where('this.fk_shop = :shopVar OR this.fk_shop IS NULL', [':shopVar' => $shop->getPK()]);
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
		
		return $grid;
	}
}
