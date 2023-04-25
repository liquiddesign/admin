<?php

declare(strict_types=1);

namespace Admin;

use Admin\Controls\AdminFormFactory;
use Admin\Controls\AdminGridFactory;
use Admin\Controls\IMenuFactory;
use Admin\Controls\Menu;
use Admin\DB\IGeneralAjaxRepository;
use Base\DB\Shop;
use Base\DB\ShopRepository;
use Nette\Application\Attributes\Persistent;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;
use Nette\DI\Container;
use Nette\Localization\Translator;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use StORM\DIConnection;
use StORM\Entity;

abstract class BackendPresenter extends Presenter
{
	public string $wwwDir;
	
	public string $tempDir;
	
	public string $contentTemplate = __DIR__ . '/../../../../app/Admin/templates/content.latte';
	
	public string $layoutTemplate = __DIR__ . '/../../../../app/Admin/@layout.latte';
	
	public Administrator $admin;
	
	public bool $isManager = false;
	
	/**
	 * @inject
	 */
	public IMenuFactory $menuFactory;
	
	public ?string $backLink = null;
	
	/**
	 * @inject
	 */
	public AdminGridFactory $gridFactory;
	
	/**
	 * @inject
	 */
	public Container $container;
	
	/**
	 * @inject
	 */
	public AdminFormFactory $formFactory;
	
	/**
	 * @inject
	 */
	public Translator $translator;

	/**
	 * @inject
	 */
	public DIConnection $connection;

	/** @inject */
	public ShopRepository $shopRepository;
	
	/**
	 * @persistent
	 */
	public string $lang;

	#[Persistent]
	public string|null $shop = null;

	public Shop|null $shopObject = null;
	
	/**
	 * @var array<string>
	 */
	public array $langs = [];

	/**
	 * @var array<array<string>>
	 */
	public array $ajaxInputs = [];
	
	public function checkRequirements($element): void
	{
		unset($element);
		
		if (!$this->admin->isLoggedIn()) {
			if ($this->admin->logoutReason === \Nette\Security\UserStorage::LOGOUT_INACTIVITY) {
				$this->flashMessage('You have been signed out due to inactivity. Please sign in again.');
			}
			
			$this->redirect(':Admin:Login:default', ['backlink' => $this->storeRequest()]);
		}
		
		if (!$this->admin->isAllowed($this->getAction(true))) {
			throw new BadRequestException('Not allowed action');
		}
		
		$this->isManager = $this->admin->isAllowed($this->getAction(true), '777');
	}
	
	public function createComponentMenu(): Menu
	{
		return $this->menuFactory->create();
	}
	
	public function handleLogout(): void
	{
		$this->admin->logout(true);
		
		$this->redirect(':Admin:Login:default');
	}
	
	public function beforeRender(): void
	{
		if (!$this->template->getFile()) {
			$this->template->setFile($this->contentTemplate);
		}
		
		$this->template->admin = $this->admin;
		$this->template->isManager = $this->isManager;
		$this->template->shops = $this->shopRepository->many()->toArray();
	}
	
	/**
	 * @return array<string>
	 */
	public function formatLayoutTemplateFiles(): array
	{
		return [$this->layoutTemplate];
	}
	
	public function generateDirectories($dirs = [], $subDirs = []): void
	{
		Helpers::generateUserDirectories($this->wwwDir, $dirs, $subDirs);
	}
	
	public function _($message, ...$parameters): string
	{
		if (!\str_contains($message, '.')) {
			$source = \explode(':', $this->getName());
			$module = $source[\count($source) - 1];
			
			return $this->translator->translate('admin' . $source[0] . $module . '.' . $message, ...$parameters);
		}
		
		if (Strings::substring($message, 0, 1) === '.') {
			return $this->translator->translate('admin' . $message, ...$parameters);
		}
		
		return $this->translator->translate($message, ...$parameters);
	}
	
	public function handleRestoreBackLink(string $backLink): void
	{
		$this->restoreRequest($backLink);
	}

	public function actionBulkEdit(string $grid = 'grid', string $backLink = 'default', string $label = 'Úprava'): void
	{
		unset($backLink);
		unset($label);
		
		$this[$grid]['bulkForm']->onSuccess[] = function (): void {
			$this->flashMessage($this->translator->translate('admin.saved', 'Uloženo'), 'success');
			$this->redirect('default');
		};
	}
	
	public function renderBulkEdit(string $grid = 'grid', string $backLink = 'default', string $label = 'Úprava'): void
	{
		$this->template->headerLabel = 'Hromadná úprava';
		$this->template->headerTree = [
			[$label, $backLink],
			['Hromadná úprava'],
		];
		$this->template->formName = $formName = "$grid-bulkForm";
		$this->template->displayButtons = [$this->createBackButton($backLink)];
		$this->template->displayControls = [$this->getComponent($formName)];
		
		$this->template->setFile(__DIR__ . \DIRECTORY_SEPARATOR . 'templates' . \DIRECTORY_SEPARATOR . 'bulkEdit.latte');
	}

	public function handleGetAjaxArrayForSelect(string $name, ?string $q = null, ?int $page = null): void
	{
		if (!$q) {
			$this->payload->results = [];
			$this->sendPayload();
		}

		/** @var \Admin\DB\IGeneralAjaxRepository $repository */
		$repository = $this->connection->findRepository($name);

		if (!$repository instanceof IGeneralAjaxRepository) {
			return;
		}

		$items = $repository->getAjaxArrayForSelect(true, $q, $page);

		$results = [];

		foreach ($items as $pk => $name) {
			$results[] = [
				'id' => $pk,
				'text' => $name,
			];
		}

		$this->payload->results = $results;
		$this->payload->pagination = ['more' => \count($items) === 5];

		$this->sendPayload();
	}

	protected function createBackButton(string $link, ...$arguments): string
	{
		if ($this->backLink) {
			$link = 'restoreBackLink!';
			$arguments = ['backLink' => $this->backLink];
		}
		
		return $this->createButtonWithClass($link, '<i class="fas fa-arrow-left"></i>&nbsp;' . $this->translator->translate('admin.backButton', 'Zpět'), 'btn btn-sm btn-secondary', ...$arguments);
	}
	
	protected function createNewItemButton(string $link, array $args = [], ?string $label = null): string
	{
		$defaultLabel = $this->translator->translate('admin.newItem', 'Nová položka');
		
		return '<a href="' . $this->link($link, $args) . "\"><button class='btn btn-success btn-sm'><i class='fa fa-sm fa-plus m-1'></i>" . ($label ?: $defaultLabel) . '</button></a>';
	}
	
	protected function createButtonWithClass(?string $link, string $label, string $class, ...$arguments): string
	{
		return '<a href="' . ($link ? $this->link($link, ...$arguments) : '#') . "\"><button class=\"$class\">$label</button></a>";
	}
	
	protected function createButton(string $link, string $label, ...$arguments): string
	{
		return '<a href="' . $this->link($link, ...$arguments) . "\"><button class='btn btn-sm btn-primary'>$label</button></a>";
	}
	
	protected function createFlag(string $mutation): string
	{
		[$flagsPath, $flagsExt, $flagsMap] = $this->formFactory->formFactory->getDefaultFlagsConfiguration();
		$baseUrl = $this->getHttpRequest()->getUrl()->getBaseUrl();
		
		return "<img class='mutation-flag' src='$baseUrl$flagsPath/$flagsMap[$mutation].$flagsExt' alt='$mutation' title='$mutation'>";
	}
	
	protected function createImageDirs(string $dir): void
	{
		$subDirs = ['origin', 'detail', 'thumb'];
		$rootDir = $this->container->parameters['wwwDir'] . \DIRECTORY_SEPARATOR . 'userfiles' . \DIRECTORY_SEPARATOR . $dir;
		FileSystem::createDir($rootDir);
		
		foreach ($subDirs as $subDir) {
			FileSystem::createDir($rootDir . \DIRECTORY_SEPARATOR . $subDir);
		}
	}
	
	protected function onDeleteImage(Entity $object, string $propertyName = 'imageFileName'): void
	{
		if ($object->$propertyName && \defined($object::class . '::IMAGE_DIR')) {
			$subDirs = ['origin', 'detail', 'thumb'];
			/* @phpstan-ignore-next-line */
			$dir = $object::IMAGE_DIR;
			
			foreach ($subDirs as $subDir) {
				$rootDir = $this->container->parameters['wwwDir'] . \DIRECTORY_SEPARATOR . 'userfiles' . \DIRECTORY_SEPARATOR . $dir;

				try {
					FileSystem::delete($rootDir . \DIRECTORY_SEPARATOR . $subDir . \DIRECTORY_SEPARATOR . $object->$propertyName);
				} catch (\Throwable $e) {
				}
			}
			
			$object->update([$propertyName => null]);
		}
	}

	protected function onDelete(Entity $object): void
	{
		$this->onDeleteImage($object);
	}

	protected function getAdministrator(): ?\Admin\DB\Administrator
	{
		/** @var \Admin\DB\Administrator|null $administrator */
		$administrator = $this->admin->getIdentity();

		return $administrator;
	}

	protected function startup(): void
	{
		parent::startup();

		$shop = $this->shopRepository->getSelectedShop();
		$this->shopObject = $shop;
		$this->shop = $shop?->getPK();
		$this->template->shop = $shop;
	}
}
