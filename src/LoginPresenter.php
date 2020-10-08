<?php

declare(strict_types=1);

namespace App\Admin;

use Nette;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;

final class LoginPresenter extends Nette\Application\UI\Presenter
{
	private const DEFAULT_PLINK = ':Web:Admin:Page:default';

	public \App\Admin\User $admin;
	
	/**
	 * @persistent
	 */
	public string $backlink = '';
	
	/**
	 * @inject
	 */
	public \App\Admin\DB\UserRepository $userRepository;
	
	public function actionDefault(): void
	{
		if ($this->admin->isLoggedIn()) {
			$this->redirectLoggedUser();
		}
		
		return;
	}
	
	public function createComponentLoginForm(): Form
	{
		$form = new Form($this, 'loginForm');
		
		$form->addText('login', 'Váš e-mail / ID')->setRequired('Zadejte prosím Váš e-mail nebo ID');
		$form->addPassword('password', 'Heslo / PIN')->setRequired('Zadejte prosím heslo');
		
		$form->addSubmit('submit', 'Přihlásit se');
		$form->onSuccess[] = [$this, 'success'];
		
		return $form;
	}
	
	public function success(Form $form, Nette\Utils\ArrayHash $values): void
	{
		try {
			$this->admin->login($values->login, $values->password);
			
			$this->getPresenter()->redirect(self::DEFAULT_PLINK);
		} catch (AuthenticationException $e) {
			$form->addError('Zadali jste nesprávné přihlašovací údaje');
		}
	}
	
	public function beforeRender(): void
	{
		$this->template->admin = $this->admin;
		$this->template->baseUrl = \substr($this->getHttpRequest()->getUrl()->scriptPath, 0, -1);
		$this->template->pubUrl = $this->template->baseUrl . '/public';
		$this->template->nodeUrl = $this->template->baseUrl . '/public/node_modules';
		$this->template->imgUrl = $this->template->baseUrl . '/public/img';
		$this->template->lqdUrl = $this->template->baseUrl . '/vendor/lqdlib';
		$this->template->ts = (new \DateTime())->getTimestamp();
	}
	
	protected function startup(): void
	{
		$this->admin = $this->context->getService('admin.user');
		
		parent::startup();
		
		return;
	}
	
	protected function redirectDefault(): void
	{
		$this->redirect(self::DEFAULT_PLINK);
	}
	
	/**
	 * @throws \Nette\Application\AbortException
	 */
	private function redirectLoggedUser(): void
	{
		$this->redirectDefault();
	}
}
