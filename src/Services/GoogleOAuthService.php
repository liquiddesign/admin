<?php

declare(strict_types=1);

namespace Admin\Services;

use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Token\AccessToken;
use Nette\Utils\Strings;

class GoogleOAuthService
{
	private Google|null $provider = null;

	private string|null $state = null;

	/** @var array<string> */
	private array $allowedDomains;

	/**
	 * @param array<string> $allowedDomains
	 */
	public function __construct(
		private readonly bool $enabled,
		private readonly string $clientId,
		private readonly string $clientSecret,
		array $allowedDomains,
		private readonly string $autoCreateRole,
		private readonly string $sessionExpiration = '12 hours',
	) {
		$this->allowedDomains = $allowedDomains;
	}

	public function isEnabled(): bool
	{
		return $this->enabled && $this->clientId !== '' && $this->clientSecret !== '';
	}

	public function getAutoCreateRole(): string
	{
		return $this->autoCreateRole;
	}

	public function getSessionExpiration(): string
	{
		return $this->sessionExpiration;
	}

	public function createProvider(string $redirectUri): Google
	{
		return new Google([
			'clientId' => $this->clientId,
			'clientSecret' => $this->clientSecret,
			'redirectUri' => $redirectUri,
		]);
	}

	public function getAuthorizationUrl(string $redirectUri): string
	{
		$this->provider = $this->createProvider($redirectUri);

		$url = $this->provider->getAuthorizationUrl([
			'scope' => ['openid', 'email', 'profile'],
			'prompt' => 'select_account',
		]);

		$this->state = $this->provider->getState();

		return $url;
	}

	public function getState(): string
	{
		if ($this->state === null) {
			throw new \RuntimeException('State is not available. Call getAuthorizationUrl() first.');
		}

		return $this->state;
	}

	/**
	 * @return array{email: string, name: string}
	 */
	public function handleCallback(string $code, string $redirectUri): array
	{
		$provider = $this->createProvider($redirectUri);
		$tokenInterface = $provider->getAccessToken('authorization_code', ['code' => $code]);

		if (!$tokenInterface instanceof AccessToken) {
			throw new \RuntimeException('Unexpected token type returned from OAuth provider.');
		}

		/** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
		$googleUser = $provider->getResourceOwner($tokenInterface);

		$email = $googleUser->getEmail();
		$name = $googleUser->getName();

		if ($email === null || $email === '') {
			throw new \RuntimeException('Google account has no email address.');
		}

		if (!$this->validateDomain($email)) {
			$domain = Strings::substring($email, \strrpos($email, '@') + 1);

			throw new \RuntimeException("Domain '$domain' is not allowed.");
		}

		return [
			'email' => $email,
			'name' => $name,
		];
	}

	public function validateDomain(string $email): bool
	{
		$atPos = \strrpos($email, '@');

		if ($atPos === false) {
			return false;
		}

		$domain = Strings::lower(Strings::substring($email, $atPos + 1));

		foreach ($this->allowedDomains as $allowedDomain) {
			if (Strings::lower($allowedDomain) === $domain) {
				return true;
			}
		}

		return false;
	}
}
