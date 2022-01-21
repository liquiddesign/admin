<?php

declare(strict_types=1);

namespace Admin\Controls;

use Admin\Administrator;
use Nette\Application\UI\Control;
use Nette\DI\Container;

/**
 * @property \Nette\Bridges\ApplicationLatte\Template|\StdClass $template
 */
class Menu extends Control
{
	private Administrator $admin;
	
	/**
	 * @var \Admin\Controls\MenuItem[]
	 */
	private array $items = [];
	
	public function __construct(Container $context)
	{
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->admin = $context->getService('admin.administrator');
	}
	
	public function addMenuItem($label, $link, $subItems = [], $icon = null, $itemName = []): MenuItem
	{
		$menuItem = new MenuItem();
		$menuItem->label = $label;
		$menuItem->link = $link;
		$menuItem->icon = $icon;
		$menuItem->itemName = $itemName;
		
		foreach ($subItems as $label => $link) {
			$subItem = new MenuItem();
			$subItem->label = $label;

			if (\is_array($link)) {
				$subItem->link = $link['link'];
				$subItem->itemName = $link['itemName'];
			} else {
				$subItem->link = $link;
			}
			
			$menuItem->items[] = $subItem;
		}
		
		$this->items[] = $menuItem;
		
		return $menuItem;
	}
	
	/**
	 * @throws \Nette\Application\UI\InvalidLinkException
	 */
	public function render(): void
	{
		$this->template->setFile($this->template->getFile() ?: __DIR__ . '/menu.latte');
		$this->template->menu = [];
		/** @phpstan-ignore-next-line */
		$this->template->lang = $this->getPresenter()->lang ?? 'cs';
		$items = $this->items;
		
		foreach ($items as $item) {
			if ($item->link === null || $this->admin->isAllowed($item->link)) {
				$active = false;
				
				foreach ($item->items as $key => $subItem) {
					if (!$this->admin->isAllowed($subItem->link)) {
						unset($item->items[$key]);
					}
					
					if (!$this->getPresenter()->isLinkCurrent(\substr($subItem->link, 0, \strrpos($subItem->link, ':')) . ':*')) {
						continue;
					}

					$subItem->active = $active = true;
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
	 * @return \Admin\Controls\MenuItem[]
	 */
	public function getItems(): array
	{
		return $this->items;
	}
}
