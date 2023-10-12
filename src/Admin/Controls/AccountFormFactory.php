<?php

declare(strict_types=1);

namespace Admin\Admin\Controls;

use Admin\Controls\AdminForm;
use Admin\Controls\AdminFormFactory;
use Base\ShopsConfig;
use Messages\DB\TemplateRepository;
use Nette\Forms\Controls\Button;
use Nette\Localization\Translator;
use Nette\Mail\Mailer;
use Nette\Security\Passwords;
use Nette\SmartObject;
use Security\DB\Account;
use Security\DB\AccountRepository;
use Security\DB\IUser;
use StORM\Entity;

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
	 * @var array<callable>&callable(\Security\DB\Account, array): void
	 */
	public $onCreateAccount;

	/**
	 * @var array<callable>&callable(): void
	 */
	public $onDeleteAccount;

	/**
	 * @var array<callable>&callable(\Security\DB\Account, array): void
	 */
	public $onUpdateAccount;

	public function __construct(
		protected readonly AdminFormFactory $adminFormFactory,
		protected readonly AccountRepository $accountRepository,
		protected readonly TemplateRepository $templateRepository,
		protected readonly Mailer $mailer,
		protected readonly Translator $translator,
		protected readonly Passwords $passwords,
		protected readonly ShopsConfig $shopsConfig,
	) {
	}

	public function addContainer(AdminForm $form, bool $addRoles = false, bool $sendEmail = true, bool $fullname = false, bool $activeFromTo = false, Account|null $existingAccount = null): void
	{
		unset($addRoles);

		/** @var \Forms\Container|array{'shop': \Nette\Forms\Controls\TextInput} $accountContainer */
		$accountContainer = $form->addContainer('account');
		$accountContainer->addHidden('uuid')->setNullable();

		if ($fullname) {
			$accountContainer->addText('fullname', $this->translator->translate('adminAdminAdministrator.fullName', 'Jméno a příjmení'));
		}

		$accountContainer->addText('login', 'Login')->setRequired();

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
				$form->getTranslatedMutations(),
			)->setPrompt($this->translator->translate('adminAdminAdministrator.auto', 'Automaticky'));
		}

//		}

		$accountContainer->addCheckbox('active', $this->translator->translate('adminAdminAdministrator.active', 'Aktivní'))->setDefaultValue(true);
		$accountContainer->addCheckbox('authorized', $this->translator->translate('adminAdminAdministrator.authorized', 'Autorizovaný'))->setDefaultValue(true);
		$accountContainer->addDatetime('tsLastLogin', $this->translator->translate('adminAdminAdministrator.tsLastLogin', 'Poslední přihlášení'))->setDisabled();
		$accountContainer->addDatetime('tsRegisteredEmailSent', $this->translator->translate('adminAdminAdministrator.tsRegisteredEmailSent', 'Datum odeslání emailu o registraci'))
			->setNullable();

		if ($activeFromTo) {
			$accountContainer->addDatetime('activeFrom', 'Aktivní od')->setNullable();
			$accountContainer->addDatetime('activeTo', 'Aktivní do')->setNullable();
		}

		$this->adminFormFactory->addShopsContainerToAdminForm($form, false, $accountContainer);

		if ($existingAccount) {
			$accountContainer['shop']->setDisabled();
		}

		$accountContainer->addHidden('email');

		if (!$sendEmail) {
			return;
		}

		// @TODO: otestovat a predelat
		//$accountContainer->addCheckbox('sendEmail', 'Odeslat e-mail o vytvoření');
	}

	public function create(bool $delete = true, ?callable $beforeSubmits = null, bool $fullname = false, bool $activeFromTo = false, Account|null $existingAccount = null): AdminForm
	{
		$form = $this->adminFormFactory->create();

		$this->addContainer($form, false, true, $fullname, $activeFromTo, $existingAccount);

		if ($beforeSubmits) {
			\call_user_func_array($beforeSubmits, [$form]);
		}

		$form->addSubmits();

		if ($delete) {
			$submit = $form->addSubmit('delete');
			$class = 'btn btn-outline-danger btn-sm ml-1 mt-1 mb-1 mr-1';
			$submit->setHtmlAttribute('class', $class)->getControlPrototype()->setName('button')->setHtml('<i class="far fa-trash-alt"></i>');
			$submit->onClick[] = function (Button $button): void {
				$values = $button->getForm()->getValues('array')['account'];
				$this->accountRepository->many()->where('uuid', $values['uuid'])->delete();
				$this->onDeleteAccount();
			};
		}

		$form->onValidate[] = function (AdminForm $form): void {
			if (!$form->isValid()) {
				return;
			}

			$values = $form->getValues('array')['account'];

			$query = $this->accountRepository->many()->where('this.login', $values['login']);

			if (isset($values['uuid'])) {
				$query->whereNot('this.uuid', $values['uuid']);
			}

			if (isset($values['shop'])) {
				$query->where('this.fk_shop', $values['shop']);
			}

			if (!$query->first()) {
				return;
			}

			/** @var \Nette\Forms\Controls\TextInput $input */
			$input = $form['account']['login'];

			$input->addError($this->translator->translate('adminAdminAdministrator.loginExists', 'Login již existuje'));
		};

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
			$values['password'] = $this->passwords->hash($values['password']);
		} else {
			unset($values['password']);
		}

		if ($values['sendEmail'] ?? null) {
			try {
				$message = $this->templateRepository->createMessage($emailTemplate, ['password' => $password ?? null, 'email' => $values['email']] + $emailParams, $values['email']);
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
		if (!$holder instanceof Entity) {
			return;
		}
		
		/* @phpstan-ignore-next-line */
		$holder->accounts->delete();
		
		$holder->delete();
	}

	/**
	 * @deprecated Validation is in onValidate
	 */
	public static function validateLogin(\Nette\Forms\Controls\TextInput $input, array $args): bool
	{
		/** @var \Security\DB\AccountRepository $repository */
		$repository = $args[0];
		$collection = $repository->many()->where('login', (string) $input->getValue());

		if (isset($args[1]) && $args[1]) {
			$collection->whereNot('this.uuid', $args[1]);
		}

		return $collection->isEmpty();
	}
}
