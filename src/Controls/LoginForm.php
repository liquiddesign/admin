<?php

declare(strict_types=1);

namespace Admin\Controls;

use Admin\Administrator;
use Nette;

/**
 * @method onLogin(\Admin\Controls\LoginForm $form)
 * @method onLoginFail(\Admin\Controls\LoginForm $form, int $errorCode)
 */
class LoginForm extends \Nette\Application\UI\Form
{
	/**
	 * @var callable[]&callable(\App\User\Controls\LoginForm): void; Occurs after login
	 */
	public $onLogin;
	
	/**
	 * @var callable[]&callable(\App\User\Controls\LoginForm): void; Occurs after login fail
	 */
	public $onLoginFail;
	
	private Administrator $admin;
	
	public function __construct(Nette\DI\Container $context, Nette\Localization\ITranslator $translator)
	{
		parent::__construct();
		
		$this->setTranslator($translator);
		$this->addText('login')->setRequired(true);
		$this->addPassword('password')->setRequired(true);
		$this->addSubmit('submit');
		$this->onSuccess[] = [$this, 'submit'];
		
		$this->admin = $context->getService('admin.administrator');
	}
	
	protected function submit(): void
	{
		try {
			$values = $this->getValues();
			$this->admin->login($values->login, $values->password, \Admin\DB\Administrator::class);
			$this->onLogin($this);
		} catch (Nette\Security\AuthenticationException $exception) {
			$this->onLoginFail($this, $exception->getCode());
		}
	}
	
	
	
}