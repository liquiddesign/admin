<?php

declare(strict_types=1);

namespace Admin;

use Admin\Controls\IMenuFactory;
use Admin\Controls\Menu;
use Forms\Form;
use Nette\Application\BadRequestException;
use StORM\DIConnection;

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
	public DIConnection $stm;
	
	/**
	 * @inject
	 */
	public IMenuFactory $menuFactory;
	
	public function startUp(): void
	{
		parent::startUp();
		
		$this->stm->setMutation($this->lang);
	}
	
	public function checkRequirements($element): void
	{
		if (!$this->admin->isLoggedIn()) {
			if ($this->admin->logoutReason === \Nette\Security\IUserStorage::INACTIVITY) {
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
	
	public function formatLayoutTemplateFiles(): array
	{
		return [$this->getAdminRootPath() . '@layout.latte'];
	}
	
	public function beforeRender()
	{
		if (!$this->template->getFile()) {
			$this->template->setFile($this->getAdminRootPath() . 'templates' . \DIRECTORY_SEPARATOR . 'content.latte');
		}
	}
	
	protected function getAdminRootPath(): string
	{
		return $this->context->parameters['appDir'] .  \DIRECTORY_SEPARATOR . 'Admin' .   \DIRECTORY_SEPARATOR;
	}
}