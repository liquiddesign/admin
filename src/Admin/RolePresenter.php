<?php

declare(strict_types=1);

namespace Admin\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminGrid;
use Admin\Controls\Menu;
use Admin\Controls\AdminForm;
use Forms\Form;
use Nette\Utils\Html;
use Admin\Authorizator;
use Admin\DB\PermissionRepository;
use Admin\DB\Role;
use Admin\DB\RoleRepository;
use StORM\DIConnection;
use StORM\ICollection;

class RolePresenter extends BackendPresenter
{
	/** @inject */
	public Authorizator $authorizator;

	/** @inject */
	public RoleRepository $roleRepository;

	/** @inject */
	public PermissionRepository $permissionRepository;
	
	/** @inject */
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

	public function createComponentNewForm(): Form
	{
		$form = $this->formFactory->create();
		$mutations = $this->formFactory->getMutations();

		$form->addText('name', 'Název')->setRequired();
		$form->addDataMultiSelect('mutationsList', 'Povolené mutace', \array_combine($mutations, $mutations))
			->setHtmlAttribute('data-info', '<br>Pokud necháte prázdné, povolené budou všechny');
		$form->addSubmits();

		$form->onSuccess[] = function (AdminForm $form): void {
			$values = $form->getValues('array');
			
			$values['mutations'] = $values['mutationsList'] ? \implode(';', $values['mutationsList']) : null;
			unset($values['mutationsList']);

			$role = $this->roleRepository->syncOne($values);

			$this->permissionRepository->syncOne(['resource' => $this->admin->getFallbackLink(), 'privilege' => 777, 'role' => $role->getPK()]);

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

	public function renderNew()
	{
		$tNew = $this->_('new', 'Nová role');
		$this->template->headerLabel = $tNew;
		$this->template->headerTree = [
			[$this->tRole, 'default'],
			[$tNew],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('newForm')];
	}

	public function renderDetail(): void
	{
		$this->template->headerLabel = 'Detail';
		$this->template->headerTree = [
			[$this->tRole, 'default'],
			['Detail'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('newForm')];
	}

	public function actionDetail(Role $role): void
	{
		/** @var \Forms\Form $form */
		$form = $this->getComponent('newForm');
		$form->setDefaults($role->jsonSerialize());
		$form['mutationsList']->setDefaultValue($role->getMutations());
	}

	public function createComponentRolePermissionsTable()
	{
		/** @var Menu $menu */
		$menu = $this->getComponent('menu');

		return $this->permsFactory->create($menu->getItems(), $this->getParameter('role'));
	}
	
	public function createComponentPermissionGrid()
	{
		/** @var \Admin\Controls\Menu $menu */
		$menu = $this->getComponent('menu');
		/** @var \Admin\DB\Role $role */
		$role = $this->getParameter('role');
		
		$resources = [];
		$collection = $this->createCollectionFromMenu($menu, $role, $resources);
		
		$grid = $this->gridFactory->create($collection, 99);
		$grid->setGetIdCallback(function ($row) {
			return $row->uuid;
		});
		$grid->setItemCountCallback(function ($row) use ($resources) {
			return \count($resources);
		});
		$grid->addColumnText($this->_('name', 'Název'), 'name', '%s', 'name')->onRenderCell[] = function (Html $td, $object): void {
			$td->setHtml($object->root ? '<strong>' .$td->getHtml(). '</strong>' : '---- ' . $td->getHtml());
		};

		$grid->addColumnInputCheckbox($this->_('allow', 'Povolit'), 'allow')->onRenderCell[] = function (Html $td, $object): void {
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
				
				if ($data['allow']) {
					$this->permissionRepository->syncOne(['resource' => $resources[$id], 'privilege' => 777, 'role' => $role,]);
				} else {
					$this->permissionRepository->many()->where('resource', $resources[$id])->where('privilege', 777)->where('fk_role', $role)->delete();
				}
			}
			
			$grid->getPresenter()->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$grid->getPresenter()->redirect('this');
		};
		
		//$grid->addFilterButtons();
		
		return $grid;
	}

	public function renderRolePermissions(Role $role): void
	{
		$tRolePermissions = $this->_('rolePermissions', 'Oprávnění role');
		$this->template->headerLabel = $tRolePermissions;
		$this->template->headerTree = [
			[$this->tRole, 'default'],
			[$tRolePermissions],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('permissionGrid')];
	}
	
	private function createCollectionFromMenu(Menu $menu, Role $role, array &$resources): ICollection
	{
		$select = null;
		
		foreach ($menu->getItems() as $group) {
			foreach (\array_merge([$group], $group->items) as $item) {
				$uuid = $item->link ? \str_replace(':', '_', $item->link) : DIConnection::generateUuid();
				$root = $group === $item;
				$allow = $item->link ? $this->authorizator->isAllowed($role->getPK(), $item->link, 777) : false;
				$resources[$uuid] = $item->link ? \substr($item->link, 0, \strrpos($item->link, ':')) . ':*' : null;
				
				if ($select === null) {
					$select = "'$uuid' as uuid, '$uuid' as uuid, '$item->label' as name, '$allow' as allow, '$item->link' as resource, '$root' as root";
					
					continue;
				}
				
				$select .= " UNION SELECT ALL '$uuid', '$uuid', '$item->label', '$allow', '$item->link', '$root' as root";
			}
		}
		
		return $this->stm->rows(null, [$select])->setIndex('uuid', false);
	}

}