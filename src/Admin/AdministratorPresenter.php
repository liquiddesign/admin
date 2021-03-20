<?php

declare(strict_types=1);

namespace Admin\Admin;

use Admin\BackendPresenter;
use Admin\DB\Administrator;
use Admin\DB\AdministratorRepository;
use Admin\Controls\AdminForm;
use Eshop\Admin\Controls\AccountFormFactory;
use Forms\Form;
use Messages\DB\TemplateRepository;
use Nette\Mail\Mailer;
use Security\DB\AccountRepository;
use Security\DB\RoleRepository;

class AdministratorPresenter extends BackendPresenter
{
	/** @inject */
	public AccountFormFactory $accountFormFactory;
	
	/** @inject */
	public AccountRepository $accountRepository;
	
	/** @inject */
	public AdministratorRepository $adminRepo;
	
	/** @inject */
	public RoleRepository $roleRepo;
	
	/** @inject */
	public TemplateRepository $templateRepository;
	
	/** @inject */
	public Mailer $mailer;
	
	public function createComponentGrid()
	{
		$source = $this->adminRepo->many()->where('account.fk_role != "servis" OR account.fk_role IS NULL');

		$grid = $this->gridFactory->create($source, 20, 'fullName', 'ASC', true);
		$grid->addColumnSelector();
		
		$grid->addColumnText('Jméno', 'fullName', '%s', 'fullName');
		$grid->addColumnText('Role', 'account.role.name', '%s');
		$grid->addColumnLinkDetail('Detail');
		
		$grid->addColumnActionDelete([$this->accountFormFactory, 'deleteAccountHolder'], true);
		$grid->addButtonDeleteSelected([$this->accountFormFactory, 'deleteAccountHolder'], true);
		
		$grid->addFilterTextInput('search', ['fullName'], null, 'Jméno');
		$grid->addFilterButtons();
		
		return $grid;
	}
	
	public function createComponentNewForm(): Form
	{
		$form = $this->formFactory->create();
		
		$form->addText('fullName', 'Jméno')->setRequired();
		$this->accountFormFactory->addContainer($form, true, !$this->getParameter('administrator'));
		$form->addSubmits(!$this->getParameter('administrator'));
		
		
		$form->onSuccess[] = function (AdminForm $form) {
			$values = $form->getValues('array');
			unset($values['account']);
			
			$administrator = $this->adminRepo->syncOne($values, null, true);
			$this->accountFormFactory->onCreateAccount[] = function ($account) use ($administrator) {
				$administrator->update(['account' => $account]);
			};
			$this->accountFormFactory->success($form);
			
			$form->getPresenter()->flashMessage('Uloženo', 'success');
			$form->processRedirect('detail', 'default', [$administrator]);
		};
		
		return $form;
	}
	
	public function renderDefault()
	{
		$this->template->headerLabel = 'Administrátoři';
		$this->template->headerTree = [
			['Administrátoři'],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function renderNew()
	{
		$this->template->headerLabel = 'Nová položka';
		$this->template->headerTree = [
			['Správci', 'default'],
			['Nová položka'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('newForm')];
	}
	
	public function renderDetail()
	{
		$this->template->headerLabel = 'Detail';
		$this->template->headerTree = [
			['Správci', 'default'],
			['Detail'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('newForm')];
	}
	
	public function actionNew()
	{
		/** @var Form $form */
		$form = $this->getComponent('newForm');
		$form['account']['password']->setRequired();
		$form['account']['passwordCheck']->setRequired();
	}
	
	public function actionDetail(Administrator $administrator)
	{
		/** @var Form $form */
		$form = $this->getComponent('newForm');
		$form->setDefaults($administrator->toArray(['account']));
	}
}
