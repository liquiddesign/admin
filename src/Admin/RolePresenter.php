<?php

declare(strict_types=1);

namespace Admin\Admin;

use Admin\BackendPresenter;
use Admin\Controls\Menu;
use Admin\Controls\AdminForm;
use Forms\Form;
use Nette\Utils\Html;
use Security\Authorizator;
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

	public function createComponentGrid()
	{
		$grid = $this->gridFactory->create($this->roleRepository->many()->where('uuid != "servis"'), 20, 'name');
		$grid->addColumnSelector();
		$grid->addColumnText('Název', 'name', '%s', 'name');
		$grid->addColumnLink('rolePermissions', 'Oprávnění');
		$grid->addColumnLinkDetail('Detail');
		$grid->addColumnActionDelete();

		$grid->addButtonDeleteSelected();

		$grid->addFilterTextInput('search', ['name'], null, 'Název');

		$grid->addFilterButtons();

		return $grid;
	}

	public function createComponentNewForm(): Form
	{
		$form = $this->formFactory->create();

		$form->addText('name', 'Název')->setRequired();
		$form->addSubmits();

		$form->onSuccess[] = function (AdminForm $form) {
			$values = $form->getValues('array');

			$role = $this->roleRepository->syncOne($values);

			$this->permissionRepository->syncOne(['resource' => $this->admin->getFallbackLink(), 'privilege' => 777, 'role' => $role->getPK()]);

			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('detail', 'default', [$role]);
		};

		return $form;
	}

	public function renderDefault()
	{
		$this->template->headerLabel = 'Role';
		$this->template->headerTree = [
			['Role'],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}

	public function renderNew()
	{
		$this->template->headerLabel = 'Nová položka';
		$this->template->headerTree = [
			['Role', 'default'],
			['Nová položka'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('newForm')];
	}

	public function renderDetail()
	{
		$this->template->headerLabel = 'Detail';
		$this->template->headerTree = [
			['Role', 'default'],
			['Detail'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('newForm')];
	}

	public function actionDetail(Role $role)
	{
		/** @var Form $form */
		$form = $this->getComponent('newForm');
		$form->setDefaults($role->jsonSerialize());
	}

	public function createComponentRolePermissionsTable()
	{
		/** @var Menu $menu */
		$menu = $this->getComponent('menu');

		return $this->permsFactory->create($menu->getItems(), $this->getParameter('role'));
	}
	
	public function createComponentPermissionGrid()
	{
		/** @var Menu $menu */
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
		$grid->addColumnText('Název', 'name', '%s', 'name')->onRenderCell[] = function (Html $td, $object) {
			$td->setHtml($object->root ? '<strong>' .$td->getHtml(). '</strong>' : '---- ' . $td->getHtml());
		};

		$grid->addColumnInputCheckbox('Povolit', 'allow')->onRenderCell[] = function (Html $td, $object) {
			if (!$object->resource) {
				$td->setHtml('<input type="checkbox" class="form-check form-control-sm" style="visibility: hidden;">');
			}
		};
		
		$button = $grid->getForm()->addSubmit('submit', 'Uložit');
		$button->setHtmlAttribute('class', 'btn btn-sm btn-primary');
		$button->onClick[] = function ($button) use ($grid, $resources, $role) {
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
			
			$grid->getPresenter()->flashMessage('Uloženo', 'success');
			$grid->getPresenter()->redirect('this');
		};
		
		//$grid->addFilterButtons();
		
		return $grid;
	}

	public function renderRolePermissions(Role $role)
	{
		$this->template->headerLabel = 'Oprávnění role';
		$this->template->headerTree = [
			['Role', 'default'],
			['Oprávnění role'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('permissionGrid')];
	}
	
	private function createCollectionFromMenu(Menu $menu, Role $role, array &$resources): ICollection
	{
		$select = null;
		
		foreach ($menu->getItems() as $group) {
			foreach (\array_merge([$group], $group->items) as $item) {
				$uuid = $item->link ? str_replace(':', '_', $item->link) : DIConnection::generateUuid();
				$root =  $group === $item;
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