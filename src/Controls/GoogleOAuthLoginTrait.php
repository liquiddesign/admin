<?php

declare(strict_types=1);

namespace Admin\Controls;

use Admin\DB\AdministratorRepository;
use Admin\Services\GoogleOAuthService;
use Carbon\Carbon;
use Nette\Security\Passwords;
use Tracy\Debugger;

/**
 * Trait pro LoginPresenter — přidává Google OAuth přihlášení.
 * Vyžaduje: $this->admin (Administrator), $this->backlink (string).
 */
trait GoogleOAuthLoginTrait
{
	private GoogleOAuthService $googleOAuthService;

	private AdministratorRepository $googleOAuthAdministratorRepository;

	private Passwords $googleOAuthPasswords;

	public function injectGoogleOAuthDependencies(
		GoogleOAuthService $googleOAuthService,
		AdministratorRepository $administratorRepository,
		Passwords $passwords,
	): void {
		$this->googleOAuthService = $googleOAuthService;
		$this->googleOAuthAdministratorRepository = $administratorRepository;
		$this->googleOAuthPasswords = $passwords;
	}

	/**
	 * Volej na začátku renderDefault() v presenteru pro nastavení template proměnných.
	 */
	public function setupGoogleOAuthTemplate(): void
	{
		$this->template->googleOAuthEnabled = $this->googleOAuthService->isEnabled();
	}

	/**
	 * Volej na začátku actionDefault() v presenteru pro zpracování Google OAuth flow.
	 * Vrací true pokud byl zpracován OAuth request (presenter by měl ukončit další zpracování).
	 */
	protected function processGoogleOAuth(): bool
	{
		// Krok 1: Redirect na Google
		if ($this->getParameter('googleLogin') !== null) {
			return $this->googleOAuthRedirect();
		}

		// Krok 2: Callback z Google
		if ($this->getParameter('code') !== null || $this->getParameter('error') !== null) {
			return $this->googleOAuthCallback();
		}

		return false;
	}

	private function googleOAuthRedirect(): bool
	{
		if (!$this->googleOAuthService->isEnabled()) {
			$this->flashMessage('Google přihlášení není povoleno', 'error');
			$this->redirect('this');

			return true;
		}

		$redirectUri = $this->link('//default', ['backlink' => '']);
		$authUrl = $this->googleOAuthService->getAuthorizationUrl($redirectUri);

		$session = $this->getSession('googleOAuth');
		$session->set('state', $this->googleOAuthService->getState());
		$session->set('backlink', $this->backlink);

		$this->redirectUrl($authUrl);

		return true;
	}

	private function googleOAuthCallback(): bool
	{
		/** @var string|null $state */
		$state = $this->getParameter('state');
		/** @var string|null $code */
		$code = $this->getParameter('code');
		/** @var string|null $error */
		$error = $this->getParameter('error');

		$session = $this->getSession('googleOAuth');
		/** @var string|null $sessionState */
		$sessionState = $session->get('state');
		/** @var string $savedBacklink */
		$savedBacklink = (string) ($session->get('backlink') ?? '');
		$session->remove('state');
		$session->remove('backlink');

		if ($error !== null || $state === null || $code === null || $state !== $sessionState) {
			$this->flashMessage('Přihlášení přes Google selhalo', 'error');
			$this->redirect('default');

			return true;
		}

		try {
			$redirectUri = $this->link('//default', ['backlink' => '']);
			$googleUser = $this->googleOAuthService->handleCallback($code, $redirectUri);
		} catch (\Throwable $e) {
			Debugger::log($e);
			$this->flashMessage('Přihlášení přes Google selhalo: ' . $e->getMessage(), 'error');
			$this->redirect('default');

			return true;
		}

		$administrator = $this->findOrCreateGoogleAdministrator($googleUser['email'], $googleUser['name']);

		$this->admin->login($administrator);
		$this->admin->setExpiration($this->googleOAuthService->getSessionExpiration());

		$this->restoreRequest($savedBacklink);

		if ($this->admin->isAllowed($this->admin->getDefaultLink())) {
			$this->redirect($this->admin->getDefaultLink());
		}

		$this->flashMessage('Nedostatečná oprávnění', 'error');
		$this->redirect('default');

		return true;
	}

	private function findOrCreateGoogleAdministrator(string $email, string $fullName): \Admin\DB\Administrator
	{
		$existing = $this->googleOAuthAdministratorRepository->getByAccountLogin($email);

		if ($existing instanceof \Admin\DB\Administrator) {
			$existing->getAccount()?->update([
				'tsLastLogin' => Carbon::now()->toDateTimeString(),
				'tsLastActivity' => Carbon::now()->toDateTimeString(),
			]);

			if (!$existing->isGoogleOAuth()) {
				$existing->update(['googleOAuth' => true]);
			}

			return $existing;
		}

		$role = $this->googleOAuthService->getAutoCreateRole();

		/** @var \Admin\DB\Administrator $administrator */
		$administrator = $this->googleOAuthAdministratorRepository->register(
			[
				'fullName' => $fullName,
				'role' => $role,
				'googleOAuth' => true,
			],
			[
				'login' => $email,
				'password' => $this->googleOAuthPasswords->hash(\bin2hex(\random_bytes(32))),
				'active' => true,
				'authorized' => true,
			],
		);

		return $administrator;
	}
}
