<?php

declare(strict_types=1);

namespace Admin;

use Nette\Security\Authorizator;
use Nette\Security\IAuthenticator;
use Nette\Security\IAuthorizator;
use Nette\Security\IIdentity;
use Nette\Security\IUserStorage;
use Nette\Security\UserStorage;

class Administrator extends \Nette\Security\User
{
	private ?string $defaultLink = null;

	public function __construct(IAuthenticator $authenticator = null, Authorizator $authorizator = null, IUserStorage $legacyStorage = null)
	{
		parent::__construct(clone $legacyStorage, $authenticator, $authorizator);

		/** @var \Nette\Http\UserStorage $legacyStorage */
		$legacyStorage = $this->getStorage();
		$legacyStorage->setNamespace('admin');
	}

	public function setDefaultLink(string $defaultLink): void
	{
		$this->defaultLink = $defaultLink;
	}

	public function getDefaultLink(): string
	{
		if (!$this->defaultLink) {
			throw new \DomainException('Default link is not set');
		}

		return $this->defaultLink;
	}
}
