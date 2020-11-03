<?php

declare(strict_types=1);

namespace Admin;

use Admin\Controls\IMenuFactory;
use Admin\Controls\Menu;
use Forms\Form;

/**
 * Trait PresenterTrait
 * @mixin \Nette\Application\UI\Presenter
 */
trait PresenterTrait
{
	public string $lang;
	
	public Administrator $admin;
	
	/**
	 * @inject
	 */
	public IMenuFactory $menuFactory;
	
	public function checkRequirements($element): void
	{
		if (!$this->admin->isLoggedIn()) {
			if ($this->admin->logoutReason === \Nette\Security\IUserStorage::INACTIVITY) {
				$this->flashMessage('You have been signed out due to inactivity. Please sign in again.');
			}
			
			$this->redirect(':Admin:Login:default', ['backlink' => $this->storeRequest()]);
		}
	}
	
	public function createComponentMenu(): Menu
	{
		$menu = $this->menuFactory->create();
		
		return $menu;
	}
	
	public function handleLogout()
	{
		$this->admin->logout(true);
		
		$this->redirect(':Admin:Login:default');
	}
	
	public function formatLayoutTemplateFiles(): array
	{
		return [$this->getAdminRootPath() . '@layout.latte'];
	}
	
	public function beforeRender()
	{
		$this->template->setFile($this->getAdminRootPath() . 'templates' . \DIRECTORY_SEPARATOR . 'content.latte');
	}
	
	private function getAdminRootPath(): string
	{
		return $this->context->parameters['appDir'] .  \DIRECTORY_SEPARATOR . 'Admin' .   \DIRECTORY_SEPARATOR;
	}
}