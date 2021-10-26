<?php

declare(strict_types=1);

namespace Admin\Admin;

use _PHPStan_76800bfb5\Nette\Neon\Exception;
use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\DB\AdministratorRepository;
use Admin\FormValidators;
use Security\DB\AccountRepository;
use Security\DB\IUser;

class ProfilePresenter extends BackendPresenter
{
	/**
	 * @inject
	 */
	public AccountRepository $accountRepo;
	
	/**
	 * @inject
	 */
	public AdministratorRepository $adminRepo;
	
	public function createComponentAccountForm(): AdminForm
	{
		if (!$this->admin->getIdentity() instanceof IUser) {
			throw new Exception('Identity is not filled with IUSer');
		}
		
		$form = $this->formFactory->create();
		
		$profile = $form->addContainer('profile');
		$profile->addText('fullName', $this->_('name', 'Jméno'));
		
		$account = $form->addContainer('account');
		$account->addText('login', 'Login')->setDisabled();
		$form->addText('role', 'Role')->setDisabled();
		$account->addPassword('oldPassword', $this->_('oldPassword', 'Staré heslo'))
			->addRule([FormValidators::class, 'checkOldPassword'], $this->_('oldPasswordCheck', 'Staré heslo není správné!'), $this->admin->getIdentity()->getAccount());
		$account->addPassword('newPassword', $this->_('newPassword', 'Nové heslo'));
		$account->addPassword('newPasswordCheck', $this->_('passwordCheck', 'Kontrola nového hesla'))
			->addRule($form::EQUAL, $this->_('passCheckError', 'Hesla nejsou shodná!'), $form['account']['newPassword']);
		
		$form->addSubmit('submit', $this->_('.save', 'Uložit'));
		
		return $form;
	}
	
	public function renderDefault(): void
	{
		$tProfile = $this->_('profile', 'Profil');
		$this->template->headerLabel = $tProfile;
		$this->template->headerTree = [
			[$tProfile],
		];
		$this->template->displayButtons = [];
		$this->template->displayControls = [
			$this->getComponent('accountForm'),
		];
	}
	
	public function actionDefault(): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('accountForm');
		
		/** @var \Admin\DB\Administrator $administrator */
		$administrator = $this->admin->getIdentity();
		
		if (!$administrator->getAccount()) {
			return;
		}

		$form->setDefaults([
			'account' => $administrator->getAccount()->toArray(),
			'profile' => $administrator->toArray(),
			'role' => $administrator->role->name ?? '',
		]);
		
		$form->onSuccess[] = function (AdminForm $form) use ($administrator): void {
			$values = $form->getValues();
			
			if ($values['account']->newPassword && $values['account']->oldPassword) {
				$administrator->getAccount()->changePassword($values['account']->newPassword);
			}
			
			$administrator->update($values['profile']);
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$this->redirect('this');
		};
	}
}
