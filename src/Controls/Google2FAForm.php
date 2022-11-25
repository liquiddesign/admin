<?php

declare(strict_types=1);

namespace Admin\Controls;

use Admin\Administrator;
use Nette;

/**
 * @method onLogin(\Admin\Controls\Google2FAForm $form)
 * @method onLoginFail(\Admin\Controls\Google2FAForm $form, int $errorCode)
 */
class Google2FAForm extends \Nette\Application\UI\Form
{
	/**
	 * @var array<callable(static): void> Occurs after login
	 */
	public array $onLogin = [];
	
	/**
	 * @var array<callable(static): void> Occurs after login fail
	 */
	public array $onLoginFail = [];
	
	private Administrator $admin;
	
	public function __construct(Nette\DI\Container $context)
	{
		parent::__construct();
		
		$this->addText('key')->setRequired(true);
		$this->addSubmit('submit');
		$this->onSuccess[] = [$this, 'submit'];
		
		$this->admin = $context->getService('admin.administrator');
	}
	
	protected function submit(): void
	{
		try {
			$values = $this->getValues('array');
			
			/** @var \Admin\DB\Administrator $identity */
			$identity = $this->admin->getRequired2FA();
			
			if (!$identity->verify2FAKey($values['key'])) {
				throw new Nette\Security\AuthenticationException('2FA Key not match');
			}
			
			$this->admin->clearRequired2FA();

			$this->admin->login($identity);
			
			$this->onLogin($this);
		} catch (Nette\Security\AuthenticationException $exception) {
			$this->onLoginFail($this, $exception->getCode());
		}
	}
}
