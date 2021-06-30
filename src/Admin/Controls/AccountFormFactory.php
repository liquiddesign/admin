<?php

declare(strict_types=1);

namespace Admin\Admin\Controls;

use Admin\Controls\AdminForm;
use Admin\Controls\AdminFormFactory;
use Messages\DB\TemplateRepository;
use Nette\Forms\Controls\Button;
use Nette\Localization\Translator;
use Nette\Mail\Mailer;
use Nette\SmartObject;
use Security\Authenticator;
use Security\DB\AccountRepository;
use Security\DB\IUser;
use Security\DB\RoleRepository;

/**
 * @method onCreateAccount(\Security\DB\Account $account, array $values)
 * @method onUpdateAccount(\Security\DB\Account $account, array $values, array $oldValues)
 * @method onDeleteAccount()
 */
class AccountFormFactory
{
	use SmartObject;

	protected const CONFIGURATIONS = [
		'preferredMutation' => false,
	];

	/**
	 * @var callable[]&callable(\Security\DB\Account, array): void
	 */
	public $onCreateAccount;

	/**
	 * @var callable[]&callable(): void
	 */
	public $onDeleteAccount;

	/**
	 * @var callable[]&callable(\Security\DB\Account, array): void
	 */
	public $onUpdateAccount;

	private AccountRepository $accountRepository;

	private Mailer $mailer;

	private TemplateRepository $templateRepository;

	private AdminFormFactory $adminFormFactory;

	private RoleRepository $roleRepository;
	
	private Translator $translator;

	public function __construct(
		AdminFormFactory $adminFormFactory,
		AccountRepository $accountRepository,
		TemplateRepository $templateRepository,
		RoleRepository $roleRepository,
		Mailer $mailer,
		Translator $translator
	) {
		$this->adminFormFactory = $adminFormFactory;
		$this->accountRepository = $accountRepository;
		$this->templateRepository = $templateRepository;
		$this->roleRepository = $roleRepository;
		$this->mailer = $mailer;
		$this->translator = $translator;
	}

	public function addContainer(AdminForm $form, bool $addRoles = false, bool $sendEmail = true, bool $fullname = false): void
	{
		$accountContainer = $form->addContainer('account');
		$accountContainer->addHidden('uuid')->setNullable();
		
		if ($fullname) {
			$accountContainer->addText('fullname', $this->translator->translate('adminAdminAdministrator.fullName', 'Jméno a příjmení'));
		}
		
		$accountContainer->addText('login', 'Login')
			->setRequired()
			->addRule(
				[$this, 'validateLogin'],
				$this->translator->translate('adminAdminAdministrator.loginExists', 'Login již existuje'),
				[$this->accountRepository, $form['account']['uuid']],
			);

		$accountContainer->addPassword('password', $this->translator->translate('adminAdminAdministrator.password', 'Heslo'));
		$accountContainer->addPassword('passwordCheck', $this->translator->translate('adminAdminAdministrator.passwordCheck', 'Kontrola hesla'))
			->addRule(
				$form::EQUAL,
				$this->translator->translate('adminAdminAdministrator.passwordError', 'Hesla nejsou shodná!'),
				$form['account']['password'],
			);

//		if(isset(static::CONFIGURATIONS['preferredMutation']) && static::CONFIGURATIONS['preferredMutation']){
		if (\count($this->adminFormFactory->getMutations()) > 1) {
			$accountContainer->addDataSelect(
				'preferredMutation',
				$this->translator->translate('adminAdminAdministrator.language', 'Preferovaný jazyk'),
				\array_combine($this->adminFormFactory->formFactory->getDefaultMutations(), $this->adminFormFactory->formFactory->getDefaultMutations()),
			)->setPrompt($this->translator->translate('adminAdminAdministrator.auto', 'Automaticky'));
		}
//		}

		$accountContainer->addCheckbox('active', $this->translator->translate('adminAdminAdministrator.active', 'Aktivní'))->setDefaultValue(true);
		
		$accountContainer->addHidden('email');

		if ($sendEmail) {
			// @TODO: otestovat a predelat
			//$accountContainer->addCheckbox('sendEmail', 'Odeslat e-mail o vytvoření');
		}
	}
	
	public function create(bool $delete = true, ?callable $beforeSubmits = null, bool $fullname = false): AdminForm
	{
		$form = $this->adminFormFactory->create();
		
		$this->addContainer($form, false, true, $fullname);
		
		if ($beforeSubmits) {
			\call_user_func_array($beforeSubmits, [$form]);
		}

		$form->addSubmits();

		if ($delete) {
			$submit = $form->addSubmit('delete');
			$class = 'btn btn-outline-danger btn-sm ml-0 mt-1 mb-1 mr-1';
			$submit->setHtmlAttribute('class', $class)->getControlPrototype()->setName('button')->setHtml('<i class="far fa-trash-alt"></i>');
			$submit->onClick[] = function (Button $button): void {
				$values = $button->getForm()->getValues('array')['account'];
				$this->accountRepository->many()->where('uuid', $values['uuid'])->delete();
				$this->onDeleteAccount();
			};
		}

		$form->onSuccess[] = [$this, 'success'];

		return $form;
	}

	public function success(AdminForm $form): void
	{
		$emailTemplate = 'lostPassword.changed';
		$emailParams = [];

		$values = $form->getValues('array')['account'];

		if ($values['password']) {
			$password = $values['password'];
			$values['password'] = Authenticator::setCredentialTreatment($values['password']);
		} else {
			unset($values['password']);
		}

		if ($values['sendEmail'] ?? null) {
			try {
				$message = $this->templateRepository->createMessage($emailTemplate, ['password' => $password, 'email' => $values['email']] + $emailParams, $values['email']);
				$this->mailer->send($message);
			} catch (\Exception $e) {
				$form->getPresenter()->flashMessage('Varování: Nelze odeslat email! Účet byl přesto upraven.', 'warning');
			}
		}

		if (!$values['uuid']) {
			/** @var \Security\DB\Account $account */
			$account = $this->accountRepository->createOne($values, true);
			$this->onCreateAccount($account, $form->getValues('array'));
		} else {
			$account = $this->accountRepository->one($values['uuid'], true);
			$oldData = $account->toArray();
			$account->update($values);
			$this->onUpdateAccount($account, $form->getValues('array'), $oldData);
		}
	}
	
	public function deleteAccountHolder(IUser $holder): void
	{
		try {
			$holder->accounts->delete();
		} catch (\Exception $e) {
			throw new \Exception($e);
		}
		
		$holder->delete();
	}

	public static function validateLogin(\Nette\Forms\Controls\TextInput $input, array $args): bool
	{
		/** @var \Security\DB\AccountRepository $repository */
		$repository = $args[0];
		$collection = $repository->many()->where('login', (string)$input->getValue());
		
		if (isset($args[1]) && $args[1]) {
			$collection->whereNot('this.uuid', $args[1]);
		}

		return $collection->isEmpty();
	}
}
