<?php

declare(strict_types=1);

namespace Admin\Controls;

use Admin\Administrator;
use Admin\DB\ChangelogRepository;
use Base\BaseHelpers;
use Base\Entity\ShopEntity;
use Base\ShopsConfig;
use JetBrains\PhpStorm\Deprecated;
use Nette\Http\Session;
use Nette\Localization\Translator;
use Nette\Utils\Arrays;
use Pages\DB\Page;
use Security\DB\IUser;
use StORM\Collection;
use StORM\DIConnection;
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
		protected readonly Administrator $administrator,
		protected readonly AdminFormFactory $formFactory,
		protected readonly Session $session,
		protected readonly Translator $translator,
		protected readonly ChangelogRepository $changelogRepository,
		protected readonly ShopsConfig $shopsConfig,
		protected readonly DIConnection $connection,
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
		#[Deprecated('Shops are filtered automatically if supported.')]
		bool $filterShops = true,
		bool $defaultShowPaginator = true
	): AdminGrid {
		unset($filterShops);

		$grid = new AdminGrid($source, $defaultOnPage, $defaultOrderExpression, $defaultOrderDir, $encodeId, $this->session, $defaultShowPaginator);
		$grid->setFormsFactory($this->formFactory);
		$grid->setItemsPerPage($this->itemsPerPage);
		$grid->setShowItemsPerPage($this->showItemsPerPage);
		$grid->setChangelogRepository($this->changelogRepository);

		if ($this->defaultOnPage && !$defaultOnPage) {
			$grid->setDefaultOnPage($this->defaultOnPage);
		}

		$grid->setTranslator($this->translator);

		$grid->setItemCountCallback(function (Collection $collection): int {
			$vars = $collection->getVars();
			$pkName = $collection->getRepository()->getStructure()->getPK()->getName();
			$collection->setSelect([])->setOrderBy([]);
			$subCollection = AdminGrid::processCollectionBaseFrom($collection, useOrder: false, join: false);
			$subCollection->setSelect(['DISTINCT this.' . $pkName]);

			$collection->setGroupBy([]);

			return $this->connection->rows()
				->setFrom(['agg' => "({$subCollection->getSql()})"], $vars)
				->enum('agg.' . $pkName, unique: false);
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

		$shopsAvailable = $source instanceof IEntityParent && $source->getRepository()->getStructure()->getRelation('shop');

		if ($useShops && $shopsAvailable) {
			$grid->addColumn('<i class="fas fa-store-alt"></i>', function (ShopEntity $shopEntity): string|null {
				if ($shop = $shopEntity->shop) {
					return $shop->icon ? "<img
						width=\"24\"
						height=\"24\"
						src=\"data:image/png;base64,$shop->icon\"
						alt=\"$shop->name\"
					/>" : $shop->name;
				}

				return null;
			}, '%s', 'shop', ['class' => 'fit']);

			$this->addShopsFilterSelect($grid);
		}
		
		return $grid;
	}

	public function addShopsFilterSelect(AdminGrid $grid): void
	{
		if ($shops = $this->shopsConfig->getAvailableShopsArrayForSelect()) {
			$grid->addFilterDataMultiSelect(function (Collection $source, $value): void {
				$source->where('this.fk_shop', BaseHelpers::replaceArrayValue($value, '0', null));
			}, '', 'shops', null, ['0' => 'Bez obchodu'] + $shops, ['placeholder' => '- Obchody -']);
		}
	}

	public function getPageUrl(AdminGrid $grid, Page $page, string|null $mutation = null): string|null
	{
		/** @var \Admin\DB\Administrator $admin */
		$admin = $this->administrator->getIdentity();
		$mutations = $admin->role->getMutations() ?? $this->connection->getAvailableMutations();
		$primaryMutation = Arrays::first(\array_keys($mutations));

		$baseUrl = $grid->getPresenter()->getHttpRequest()->getUrl();

		if ($page instanceof ShopEntity && ($shopsBaseUrls = $page->shop?->getBaseUrls())) {
			$baseUrl = $baseUrl->withHost(Arrays::first($shopsBaseUrls));
		}

		$mutatedUrl = $page->getValue('url', $mutation);

		return $baseUrl->getBaseUrl() . ($mutation === $primaryMutation ? $mutatedUrl : ($mutation ? "$mutation/" . $mutatedUrl : $mutatedUrl));
	}
}
