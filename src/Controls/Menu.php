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
		$this->admin = $context->getService('admin.administrator');
	}
	
	public function addMenuItem($label, $link, $subitems = [], $icon = null): MenuItem
	{
		$menuItem = new MenuItem();
		$menuItem->label = $label;
		$menuItem->link = $link;
		$menuItem->icon = $icon;
		
		foreach ($subitems as $label => $link) {
			$subItem = new MenuItem();
			$subItem->label = $label;
			$subItem->link = $link;
			$menuItem->items[] = $subItem;
		}
		
		$this->items[] = $menuItem;
		
		return $menuItem;
	}
	
	public function render(): void
	{
		$this->template->setFile($this->template->getFile() ?: __DIR__ . '/menu.latte');
		$this->template->menu = [];
		$items = $this->items;
		
		foreach ($items as $item) {
			
			if ($item->link === null || $this->admin->isAllowed($item->link)) {
				$active = false;
				
				foreach ($item->items as $key => $subItem) {
					if (!$this->admin->isAllowed($subItem->link)) {
						unset($item->items[$key]);
					}
					
					if ($this->getPresenter()->isLinkCurrent(\substr($subItem->link, 0, \strrpos($subItem->link, ':')). ":*")) {
						$subItem->active = $active = true;
					}
				}
				
				if (!$item->items && $item->link === null) {
					continue;
				}
				
				$item->active = $active;
				$this->template->menu[] = $item;
			}
		}
		
		$this->template->render();
	}
	
	/**
	 * @return array
	 */
	public function getItems(): array
	{
		return $this->items;
	}
}
