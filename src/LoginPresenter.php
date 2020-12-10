<?php

declare(strict_types=1);

namespace Admin;

use Admin\Controls\ILoginFormFactory;
use Admin\Controls\LoginForm;
use Nette;

abstract class LoginPresenter extends Nette\Application\UI\Presenter
{
	public Administrator $admin;
	
	/**
	 * @inject
	 */
	public ILoginFormFactory $loginFormFactory;
	
	/**
	 * @persistent
	 */
	public string $backlink = '';
	
	public function actionDefault(): void
	{
		if ($this->admin->isLoggedIn()) {
			$this->redirect($this->admin->getDefaultLink());
		}
		
		return;
	}
	
	public function createComponentLoginForm(): LoginForm
	{
		$form = $this->loginFormFactory->create();
		
		$form->onLogin[] = function (LoginForm $form) {
			$this->restoreRequest($this->backlink);
			$form->getPresenter()->redirect($this->admin->getDefaultLink());
		};
		
		return $form;
	}
}
