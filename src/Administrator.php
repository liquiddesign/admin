<?php

declare(strict_types=1);

namespace Admin;

use Nette\Security\IAuthenticator;
use Nette\Security\IAuthorizator;
use Nette\Security\IUserStorage;

class Administrator extends \Nette\Security\User
{
	private ?string $defaultLink = null;
	
	public function __construct(IUserStorage $storage, ?IAuthenticator $authenticator = null, ?IAuthorizator $authorizator = null)
	{
		parent::__construct(clone $storage, $authenticator, $authorizator);
		
		/** @var \Nette\Http\UserStorage $storage */
		$storage = $this->getStorage();
		$storage->setNamespace('admin');
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
