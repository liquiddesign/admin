<?php

declare(strict_types=1);

namespace Admin;

use Admin\Controls\AdminFormFactory;
use Admin\Controls\IMenuFactory;
use Admin\Controls\Menu;
use Admin\Controls\AdminGridFactory;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;
use Nette\DI\Container;
use Nette\Localization\Translator;
use Nette\Utils\FileSystem;
use StORM\Entity;

abstract class BackendPresenter extends Presenter
{
	public string $wwwDir;
	
	public string $tempDir;
	
	public string $contentTemplate = __DIR__ . '/../../../../app/Admin/templates/content.latte';
	
	public string $layoutTemplate = __DIR__ . '/../../../../app/Admin/@layout.latte';
	
	public Administrator $admin;
	
	/** @inject */
	public IMenuFactory $menuFactory;
	
	public ?string $backLink = null;
	
	/** @inject */
	public AdminGridFactory $gridFactory;
	
	/** @inject */
	public Container $container;
	
	/** @inject */
	public AdminFormFactory $formFactory;
	
	/** @inject */
	public Translator $translator;
	
	/** @persistent */
	public string $lang;
	
	public array $langs = [];
	
	public function checkRequirements($element): void
	{
		if (!$this->admin->isLoggedIn()) {
			if ($this->admin->logoutReason === \Nette\Security\UserStorage::LOGOUT_INACTIVITY) {
				$this->flashMessage('You have been signed out due to inactivity. Please sign in again.');
			}
			
			$this->redirect(':Admin:Login:default', ['backlink' => $this->storeRequest()]);
		}
		
		if (!$this->admin->isAllowed($this->getAction(true))) {
			throw new BadRequestException('Not allowed action');
		}
	}
	
	public function createComponentMenu(): Menu
	{
		return $this->menuFactory->create();
	}
	
	public function handleLogout()
	{
		$this->admin->logout(true);
		
		$this->redirect(':Admin:Login:default');
	}
	
	public function beforeRender()
	{
		if (!$this->template->getFile()) {
			$this->template->setFile($this->contentTemplate);
		}
		
		$this->template->admin = $this->admin;
	}
	
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
		
		if (\substr($message, 0, 1) === '.') {
			return $this->translator->translate('admin' . $message, ...$parameters);
		}
		
		return $this->translator->translate($message, ...$parameters);
	}
	
	protected function createBackButton(string $link, ...$arguments): string
	{
		if ($this->backLink) {
			$link = 'restoreBackLink!';
			$arguments = ['backLink' => $this->backLink];
		}
		
		return $this->createButtonWithClass($link, '<i class="fas fa-arrow-left"></i>&nbsp;' . $this->translator->translate('admin.backButton', 'Zpět'), 'btn btn-sm btn-secondary', ...$arguments);
	}
	
	public function handleRestoreBackLink(string $backLink)
	{
		$this->restoreRequest($backLink);
	}
	
	protected function createNewItemButton(string $link, array $args = [], string $label = null): string
	{
		return "<a href=\"" . $this->link($link, $args) . "\"><button class='btn btn-success btn-sm'><i class='fa fa-sm fa-plus m-1'></i>" . ($label ?: $this->translator->translate('admin.newItem', 'Nová položka')) . "</button></a>";
	}
	
	protected function createButtonWithClass(string $link, string $label, string $class, ...$arguments): string
	{
		return "<a href=\"" . $this->link($link, ...$arguments) . "\"><button class=\"$class\">$label</button></a>";
	}
	
	protected function createButton(string $link, string $label, ...$arguments): string
	{
		return "<a href=\"" . $this->link($link, ...$arguments) . "\"><button class='btn btn-sm btn-primary'>$label</button></a>";
	}
	
	protected function createFlag(string $mutation): string
	{
		[$flagsPath, $flagsExt, $flagsMap] = $this->formFactory->formFactory->getDefaultFlagsConfiguration();
		$baseUrl = $this->getHttpRequest()->getUrl()->getBaseUrl();
		
		return "<img class='mutation-flag' src='$baseUrl$flagsPath/$flagsMap[$mutation].$flagsExt' alt='$mutation' title='$mutation'>";
	}
	
	protected function createImageDirs(string $dir)
	{
		$subDirs = ['origin', 'detail', 'thumb'];
		$rootDir = $this->container->parameters['wwwDir'] . \DIRECTORY_SEPARATOR . 'userfiles' . \DIRECTORY_SEPARATOR . $dir;
		FileSystem::createDir($rootDir);
		
		foreach ($subDirs as $subDir) {
			FileSystem::createDir($rootDir . \DIRECTORY_SEPARATOR . $subDir);
		}
	}
	
	public function onDeleteImage(Entity $object, string $propertyName = 'imageFileName')
	{
		if ($object->$propertyName) {
			$subDirs = ['origin', 'detail', 'thumb'];
			$dir = $object::IMAGE_DIR;
			
			foreach ($subDirs as $subDir) {
				$rootDir = $this->container->parameters['wwwDir'] . \DIRECTORY_SEPARATOR . 'userfiles' . \DIRECTORY_SEPARATOR . $dir;
				FileSystem::delete($rootDir . \DIRECTORY_SEPARATOR . $subDir . \DIRECTORY_SEPARATOR . $object->$propertyName);
			}
			
			$object->update([$propertyName => null]);
		}
	}
	
	protected function onDeletePage(Entity $object)
	{
		//@TODO
		if ($page = $this->pageRepository->getPageByTypeAndParams('product_list', null, ['producer' => $object])) {
			$page->delete();
		}
	}
	
	protected function onDelete(Entity $object)
	{
		$this->onDeleteImage($object);
	}
	
	public function actionBulkEdit(string $grid = 'grid', string $backLink = 'default', string $label = 'Úprava')
	{
		$this[$grid]['bulkForm']->onSuccess[] = function () {
			$this->flashMessage($this->translator->translate('admin.saved', 'Uloženo'), 'success');
			$this->redirect('default');
		};
	}
	
	public function renderBulkEdit(string $grid = 'grid', string $backLink = 'default', string $label = 'Úprava')
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
}
