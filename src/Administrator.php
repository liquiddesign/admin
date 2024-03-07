<?php

declare(strict_types=1);

namespace Admin;

use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Security\AuthenticationException;
use Nette\Security\Authorizator;
use Nette\Security\IAuthenticator;
use Nette\Security\IIdentity;
use Nette\Security\UserStorage;
use Security\Authenticator;

class Administrator extends \Nette\Security\User
{
	private ?string $defaultLink = null;
	
	private ?string $fallbackLink = null;
	
	private SessionSection $google2FaSession;
	
	
	public function __construct(Session $session, ?IAuthenticator $authenticator = null, ?Authorizator $authorizator = null, ?UserStorage $legacyStorage = null)
	{
		parent::__construct(clone $legacyStorage, $authenticator, $authorizator);
		
		/** @var \Nette\Http\UserStorage $legacyStorage */
		$legacyStorage = $this->getStorage();
		$legacyStorage->setNamespace('admin');
		
		$this->google2FaSession = $session->getSection('Google2FA');
		
		if (!($authenticator instanceof Authenticator) || !$this->google2FaSession->get('identity')) {
			return;
		}

		$authenticator->wakeupIdentity($this->google2FaSession->get('identity'));
	}
	
	public function setRequired2FA(IIdentity $identity): void
	{
		$this->google2FaSession->set('identity', $identity, '60 seconds');
	}
	
	public function getRequired2FA(): IIdentity
	{
		if (!$this->isRequired2FA()) {
			throw new AuthenticationException('2FA is not set');
		}
		
		return $this->google2FaSession->get('identity');
	}
	
	public function isRequired2FA(): bool
	{
		return $this->google2FaSession->get('identity') instanceof IIdentity;
	}
	
	public function clearRequired2FA(): void
	{
		$this->google2FaSession->remove('identity');
	}
	
	public function setDefaultLink(string $defaultLink): void
	{
		$this->defaultLink = $defaultLink;
	}
	
	public function setFallbackLink(string $fallbackLink): void
	{
		$this->fallbackLink = $fallbackLink;
	}
	
	public function getFallbackLink(): ?string
	{
		return $this->fallbackLink;
	}
	
	public function getDefaultLink(): string
	{
		if (!$this->defaultLink) {
			throw new \DomainException('Default link is not set');
		}
		
		if (!$this->isAllowed($this->defaultLink)) {
			return $this->fallbackLink;
		}
		
		return $this->defaultLink;
	}
}
