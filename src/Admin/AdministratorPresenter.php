<?php

declare(strict_types=1);

namespace Admin\Admin;

use Admin\Admin\Controls\AccountFormFactory;
use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Admin\DB\Administrator;
use Admin\DB\AdministratorRepository;
use Admin\DB\RoleRepository;
use Forms\Form;
use Messages\DB\TemplateRepository;
use Nette\Mail\Mailer;
use Security\DB\AccountRepository;

class AdministratorPresenter extends BackendPresenter
{
	protected const CONFIGURATION = [
		'groups' => [],
	];

	/**
	 * @inject
	 */
	public AccountFormFactory $accountFormFactory;
	
	/**
	 * @inject
	 */
	public AccountRepository $accountRepository;
	
	/**
	 * @inject
	 */
	public AdministratorRepository $adminRepo;
	
	/**
	 * @inject
	 */
	public RoleRepository $roleRepo;
	
	/**
	 * @inject
	 */
	public TemplateRepository $templateRepository;
	
	/**
	 * @inject
	 */
	public Mailer $mailer;
	
	public string $tAdministrators;
	
	public function beforeRender(): void
	{
		parent::beforeRender();
		
		$this->tAdministrators = $this->_('.administrators', 'Administrátoři');
	}
	
	public function createComponentGrid(): AdminGrid
	{
		$source = $this->adminRepo->many()->where('fk_role != "servis" OR fk_role IS NULL');
		
		$grid = $this->gridFactory->create($source, 20, 'fullName', 'ASC', true);
		$grid->addColumnSelector();
		
		$grid->addColumnText($this->_('fullname', 'Jméno a příjmení'), 'fullName', '%s', 'fullName');
		$grid->addColumnText($this->_('role', 'Role'), 'role.name', '%s');
		$grid->addColumnLinkDetail('Detail');
		
		$grid->addColumnActionDelete([$this->accountFormFactory, 'deleteAccountHolder'], true);
		$grid->addButtonDeleteSelected([$this->accountFormFactory, 'deleteAccountHolder'], true);
		
		$grid->addFilterTextInput('search', ['fullName'], null, $this->_('fullname', 'Jméno a příjmení'));
		$grid->addFilterButtons();
		
		return $grid;
	}
	
	public function createComponentNewForm(): Form
	{
		$form = $this->formFactory->create();
		
		$form->addText('fullName', $this->_('fullname', 'Jméno a Příjmení'))->setRequired();

		$form->addSelect(
			'role',
			$this->_('role', 'Role'),
			$this->roleRepo->many()->whereNot('uuid', 'servis')->toArrayOf('name'),
		)->setRequired();
		
		$this->accountFormFactory->addContainer($form, true, !$this->getParameter('administrator'));

		if (\in_array('editUrl', $this::CONFIGURATION['groups'])) {
			$form->addCheckbox('urlEditor', $this->_('canEdit', 'Může editovat URL'));
		}

		$form->addSubmits(!$this->getParameter('administrator'));
		
		
		$form->onSuccess[] = function (AdminForm $form): void {
			$values = $form->getValues('array');
			unset($values['account']);
			
			/** @var \Admin\DB\Administrator $administrator */
			$administrator = $this->adminRepo->syncOne($values, null, true);
			$this->accountFormFactory->onCreateAccount[] = function ($account) use ($administrator): void {
				$administrator->accounts->relate([$account]);
			};
			$this->accountFormFactory->success($form);
			
			$form->getPresenter()->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detail', 'default', [$administrator]);
		};
		
		return $form;
	}
	
	public function renderDefault(): void
	{
		$this->template->headerLabel = $this->tAdministrators;
		$this->template->headerTree = [
			[$this->tAdministrators],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function renderNew(): void
	{
		$tNew = $this->_('newAdmin', 'Nová položka');
		$this->template->headerLabel = $tNew;
		$this->template->headerTree = [
			[$this->tAdministrators, 'default'],
			[$tNew],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('newForm')];
	}
	
	public function renderDetail(): void
	{
		$tDetail = $this->_('detail', 'Detail');
		$this->template->headerLabel = $tDetail;
		$this->template->headerTree = [
			[$this->tAdministrators, 'default'],
			[$tDetail],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('newForm')];
	}
	
	public function actionNew(): void
	{
		/** @var \Forms\Form $form */
		$form = $this->getComponent('newForm');
		$form['account']['password']->setRequired();
		$form['account']['passwordCheck']->setRequired();
	}
	
	public function actionDetail(Administrator $administrator): void
	{
		/** @var \Forms\Form|\Nette\Forms\Container[] $form */
		$form = $this->getComponent('newForm');
		$form->setDefaults($administrator->toArray());
		
		if (!$account = $administrator->accounts->first()) {
			return;
		}

		$form['account']->setDefaults($account->toArray());
	}
}
