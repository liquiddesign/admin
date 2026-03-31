<?php

declare(strict_types=1);

namespace Admin\Controls;

use Admin\Administrator;
use Nette;
use Security\Authenticator;

/**
 * @method onLogin(\Admin\Controls\LoginForm $form)
 * @method onLoginFail(\Admin\Controls\LoginForm $form, int $errorCode)
 * @method onRequired2FA(\Admin\Controls\LoginForm $form)
 */
class LoginForm extends \Nette\Application\UI\Form
{
	/**
	 * @var array<callable(static): void> Occurs after login
	 */
	public array $onLogin = [];
	
	/**
	 * @var array<callable(static): void> Occurs after login fail
	 */
	public array $onLoginFail = [];
	
	/**
	 * @var array<callable(static): void> Occurs when 2FA is neeed
	 */
	public array $onRequired2FA = [];
	
	private Administrator $admin;
	
	private Authenticator $authenticator;
	
	public function __construct(Nette\DI\Container $context)
	{
		parent::__construct();
		
		$this->addText('login')->setRequired(true);
		$this->addPassword('password')->setRequired(true);
		$this->addSubmit('submit');
		$this->onSuccess[] = [$this, 'submit'];
		
		$this->admin = $context->getService('admin.administrator');
		$this->authenticator = $context->getService('authenticator');
	}
	
	protected function submit(): void
	{
		try {
			$values = $this->getValues('array');
			
			/** @var \Admin\DB\Administrator $identity */
			$identity = $this->authenticator->authenticate($values['login'], $values['password'], \Admin\DB\Administrator::class);

			if ($identity->isGoogleOAuth()) {
				throw new Nette\Security\AuthenticationException('This account uses Google OAuth login.', Nette\Security\Authenticator::NOT_APPROVED);
			}

			if ($identity->has2FAEnabled()) {
				$this->admin->setRequired2FA($identity);
				$this->onRequired2FA($this);
				
				return;
			}
			
			$this->admin->login($identity);
			$this->onLogin($this);
		} catch (Nette\Security\AuthenticationException $exception) {
			$this->onLoginFail($this, $exception->getCode());
		}
	}
}
