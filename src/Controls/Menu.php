<?php

declare(strict_types=1);

namespace Admin\Controls;

use Admin\Administrator;
use Nette\Application\UI\Control;
use Nette\DI\Container;

class Menu extends Control
{
	private Container $context;
	
	private Administrator $admin;
	
	private array $items = [];
	
	public function __construct(Container $context)
	{
		$this->context = $context;
		$this->admin = $context->getService('admin.administrator');
	}
	
	public function addMenuItem($label, $link): MenuItem
	{
		$menuItem = new MenuItem();
		$menuItem->label = $label;
		$menuItem->link = $link;
		
		$this->items[] = $menuItem;
		
		return $menuItem;
	}
	
	public function render(): void
	{
		$this->template->setFile($this->getAdminRootPath() . 'templates' . \DIRECTORY_SEPARATOR . 'menu.latte');
		$this->template->menu = [];
		$items = $this->items;
		
		foreach ($items as $item) {
			if ($this->admin->isAllowed($item->link)) {
				foreach ($item->items as $key => $subItem) {
					if (!$this->admin->isAllowed($item->link)) {
						unset($this->items[$key]);
					}
				}
				
				$this->template->menu[] = $item;
			}
		}
		
		$this->template->render();
	}
	
	private function getAdminRootPath(): string
	{
		return $this->context->parameters['appDir'] .  \DIRECTORY_SEPARATOR . 'Admin' .   \DIRECTORY_SEPARATOR;
	}
}