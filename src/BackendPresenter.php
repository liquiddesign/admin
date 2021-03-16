<?php

declare(strict_types=1);

namespace Admin;

use Admin\Controls\IMenuFactory;
use Admin\Controls\Menu;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;

abstract class BackendPresenter extends Presenter
{
	public Administrator $admin;
	
	/** @inject */
	public IMenuFactory $menuFactory;
	
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
	
	public function beforeRender()
	{
		$dirname = \dirname((new \ReflectionClass(static::class))->getFileName());
		
		if (!$this->template->getFile()) {
			$this->template->setFile($dirname . '/../../Admin/templates' . \DIRECTORY_SEPARATOR . 'content.latte');
		}
		
		$this->template->admin = $this->admin;
	}
	
	public function formatLayoutTemplateFiles(): array
	{
		$dirname = \dirname((new \ReflectionClass(static::class))->getFileName());
		
		return [$dirname . '/../../Admin/@layout.latte'];
	}
}