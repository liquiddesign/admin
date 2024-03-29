<?php

declare(strict_types=1);

namespace Admin\Admin;

use Admin\Authorizator;
use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Admin\Controls\Menu;
use Admin\DB\PermissionRepository;
use Admin\DB\Role;
use Admin\DB\RoleRepository;
use Forms\Form;
use Nette\Utils\Arrays;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use StORM\DIConnection;
use StORM\ICollection;

class RolePresenter extends BackendPresenter
{
	/**
	 * @inject
	 */
	public Authorizator $authorizator;
	
	/**
	 * @inject
	 */
	public RoleRepository $roleRepository;
	
	/**
	 * @inject
	 */
	public PermissionRepository $permissionRepository;
	
	/**
	 * @inject
	 */
	public DIConnection $stm;
	
	public string $tRole;
	
	public function beforeRender(): void
	{
		parent::beforeRender();
		
		$this->tRole = $this->_('role', 'Role');
	}
	
	public function createComponentGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->roleRepository->many()->where('uuid != "servis"'), 20, 'name');
		$grid->addColumnSelector();
		$grid->addColumnText($this->_('name', 'Název'), 'name', '%s', 'name');
		$grid->addColumnLink('rolePermissions', $this->_('rolePermissions', 'Oprávnění'));
		$grid->addColumnLinkDetail('Detail');
		$grid->addColumnActionDelete();
		$grid->addButtonDeleteSelected();
		$grid->addFilterTextInput('search', ['name'], null, $this->_('name', 'Název'));
		$grid->addFilterButtons();
		
		return $grid;
	}
	
	public function createComponentForm(): Form
	{
		$form = $this->formFactory->create();
		$mutations = $this->formFactory->getMutations();
		$form->addText('name', $this->_('name', 'Název'))->setRequired();
		
		if (\count($mutations) > 1) {
			$form->addMultiSelect2('mutationsList', $this->_('allowedMutations', 'Povolené mutace'), $form->getTranslatedMutations())
				->setHtmlAttribute('data-info', '<br>' . $this->_('allowedMutationsInfo', 'Pokud necháte prázdné, povolené budou všechny.'));
		}
		
		/** @var \Admin\Controls\Menu $menu */
		$menu = $this->getComponent('menu');
		
		$realMenuItems = [];
		
		if (!$this->getParameter('role')) {
			$form->addGroup($this->_('permissions', 'Oprávnění'));
			$menuItemsContainer = $form->addContainer('menuItems');
			
			/** @var \Admin\Controls\MenuItem $menuItem */
			foreach ($menu->getItems() as $menuItem) {
				$convertedLabel = \str_replace(' ', '_', Strings::toAscii($menuItem->label));
				$realItemName = isset($menuItem->itemName) && \array_key_exists($this->lang, $menuItem->itemName);
				$menuItemsContainer->addCheckbox($convertedLabel, $realItemName ? $menuItem->itemName[$this->lang] : $menuItem->label);
				$realMenuItems[$convertedLabel] = $menuItem;
			}
		}
		
		$form->addSubmits();
		
		$form->onSuccess[] = function (AdminForm $form) use ($realMenuItems): void {
			$values = $form->getValues('array');
			
			$values['mutations'] = isset($values['mutationsList']) && $values['mutationsList'] ? \implode(';', $values['mutationsList']) : null;
			unset($values['mutationsList']);
			
			$menuItems = Arrays::pick($values, 'menuItems', []);
			
			$role = $this->roleRepository->syncOne($values);
			
			if ($menuItems) {
				foreach ($menuItems as $menuItemLabel => $menuItemAllowed) {
					if (isset($realMenuItems[$menuItemLabel]) && $menuItemAllowed) {
						/** @var \Admin\Controls\MenuItem $menuItem */
						$menuItem = $realMenuItems[$menuItemLabel];
						
						if ($menuItem->link) {
							$this->permissionRepository->syncOne(['resource' => $menuItem->link, 'privilege' => '777', 'role' => $role->getPK()]);
						}
						
						foreach ($menuItem->items as $subMenuItem) {
							$resource = \substr($subMenuItem->link, 0, \strrpos($subMenuItem->link, ':')) . ':*';
							$this->permissionRepository->syncOne(['resource' => $subMenuItem->link ? $resource : null, 'privilege' => '777', 'role' => $role->getPK()]);
						}
					}
				}
				
				$this->permissionRepository->syncOne(['resource' => $this->admin->getFallbackLink(), 'privilege' => '777', 'role' => $role->getPK()]);
			}
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detail', 'default', [$role]);
		};
		
		return $form;
	}
	
	public function renderDefault(): void
	{
		$this->template->headerLabel = $this->tRole;
		$this->template->headerTree = [
			[$this->tRole],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function renderNew(): void
	{
		$tNew = $this->_('new', 'Nová role');
		$this->template->headerLabel = $tNew;
		$this->template->headerTree = [
			[$this->tRole, 'default'],
			[$tNew],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderDetail(): void
	{
		$this->template->headerLabel = 'Detail';
		$this->template->headerTree = [
			[$this->tRole, 'default'],
			['Detail'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function actionDetail(Role $role): void
	{
		/** @var \Forms\Form|\Nette\Forms\Container[] $form */
		$form = $this->getComponent('form');
		$form->setDefaults($role->jsonSerialize());
		$mutations = $this->formFactory->getMutations();
		
		if (\count($mutations) <= 1) {
			return;
		}
		
		$form['mutationsList']->setDefaultValue($role->getMutations());
	}
	
	public function createComponentPermissionGrid(): AdminGrid
	{
		/** @var \Admin\Controls\Menu $menu */
		$menu = $this->getComponent('menu');
		/** @var \Admin\DB\Role $role */
		$role = $this->getParameter('role');
		
		$resources = [];
		$collection = $this->createCollectionFromMenu($menu, $role, $resources);
		
		$grid = $this->gridFactory->create($collection, 99);
		$grid->showPaginator(false);
		$grid->addGridClass('small-table');
		$grid->setGetIdCallback(function ($row) {
			return $row->uuid;
		});
		$grid->setItemCountCallback(function ($row) use ($resources) {
			return \count($resources);
		});
		$grid->addColumnText($this->_('name', 'Název'), 'name', '%s')->onRenderCell[] = function (Html $td, $object): void {
			$td->setHtml($object->root ? '<strong>' . $td->getHtml() . '</strong>' : '---- ' . $td->getHtml());
		};
		
		$input = $grid->addColumnInputCheckbox("<input type='checkbox' id='check-all-permissions' style='vertical-align: middle;'> Povolit", 'allow', '', '', null, ['class' => 'rowSelector']);
		
		$input->onRenderCell[] = function (Html $td, $object): void {
			if (!$object->resource) {
				$td->setHtml('<input type="checkbox" class="form-check form-control-sm" style="visibility: hidden;">');
			}
		};
		
		$input2 = $grid->addColumnInputCheckbox(
			"<input type='checkbox' id='check-all-manager-permissions' style='vertical-align: middle;'> Správce",
			'admin',
			'',
			'',
			null,
			['class' => 'rowSelector'],
		);
		
		$input2->onRenderCell[] = function (Html $td, $object): void {
			if (!$object->resource) {
				$td->setHtml('<input type="checkbox" class="form-check form-control-sm" style="visibility: hidden;">');
			}
		};
		
		$button = $grid->getForm()->addSubmit('submit', 'Uložit');
		$button->setHtmlAttribute('class', 'btn btn-sm btn-primary');
		$button->onClick[] = function ($button) use ($grid, $resources, $role): void {
			foreach ($grid->getInputData() as $id => $data) {
				if (!$resources[$id] || !$this->admin->isAllowed($resources[$id])) {
					continue;
				}
				
				$this->permissionRepository->many()->where('resource', $resources[$id])->where('fk_role', $role)->delete();
				
				if (!$data['allow']) {
					continue;
				}

				$this->permissionRepository->syncOne(['resource' => $resources[$id], 'privilege' => $data['admin'] ? '777' : '555', 'role' => $role,]);
			}
			
			$grid->getPresenter()->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$grid->getPresenter()->redirect('this');
		};
		
		return $grid;
	}
	
	public function handleResetOrder(): void
	{
		/** @var \Admin\Controls\AdminGrid $grid */
		$grid = $this->getComponent('permissionGrid');
		$grid->setOrder(null);
		
		$this->redirect('this');
	}
	
	public function renderRolePermissions(Role $role): void
	{
		unset($role);
		
		$tRolePermissions = $this->_('rolePermissions', 'Oprávnění role');
		$this->template->headerLabel = $tRolePermissions;
		$this->template->headerTree = [
			[$this->tRole, 'default'],
			[$tRolePermissions],
		];
		$this->template->displayButtons = [
			$this->createBackButton('default'),
		];
		$this->template->displayControls = [$this->getComponent('permissionGrid')];
	}
	
	private function createCollectionFromMenu(Menu $menu, Role $role, array &$resources): ICollection
	{
		$select = null;
		
		foreach ($menu->getItems() as $group) {
			foreach (\array_merge([$group], $group->items) as $item) {
				$uuid = $item->link ? \str_replace(':', '_', $item->link) : DIConnection::generateUuid();
				$root = $group === $item;
				$allow = $item->link && $this->authorizator->isAllowed($role->getPK(), $item->link, null);
				$allow2 = $item->link && $this->authorizator->isAllowed($role->getPK(), $item->link, '777');
				
				$resources[$uuid] = $item->link ? \substr($item->link, 0, \strrpos($item->link, ':')) . ':*' : null;
				
				if ($select === null) {
					$select = "'$uuid' as uuid, '$uuid' as uuid, '$item->label' as name, '$allow' as allow, '$allow2' as admin, '$item->link' as resource, '$root' as root";
					
					continue;
				}

				$select .= " UNION SELECT ALL '$uuid', '$uuid', '$item->label', '$allow', '" . ($allow2 ? '1' : '0') . "', '$item->link', '$root' as root";
			}
		}
		
		return $this->stm->rows(null, [$select])->setIndex('uuid', false);
	}
}
