<?php

declare(strict_types=1);

namespace Admin;

use Nette\Security\Authorizator;
use Nette\Security\IAuthenticator;
use Nette\Security\IAuthorizator;
use Nette\Security\IIdentity;
use Nette\Security\IUserStorage;
use Nette\Security\UserStorage;
use Security\DB\PermissionRepository;
use StORM\Collection;

class Administrator extends \Nette\Security\User
{
	private ?string $defaultLink = null;
	
	private PermissionRepository $permissionRepository;
	
	public function __construct(IAuthenticator $authenticator = null, Authorizator $authorizator = null, IUserStorage $legacyStorage = null, PermissionRepository $permissionRepository)
	{
		parent::__construct(clone $legacyStorage, $authenticator, $authorizator);
		
		/** @var \Nette\Http\UserStorage $legacyStorage */
		$legacyStorage = $this->getStorage();
		$legacyStorage->setNamespace('admin');
		$this->permissionRepository = $permissionRepository;
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
		
		if (!$this->isAllowed($this->defaultLink)) {
			$resource = isset($this->getRoles()[0]) ? $this->permissionRepository->many()->where('fk_role', $this->getRoles()[0])->firstValue('resource') : null;
			
			if ($resource === null) {
				throw new \DomainException('Empty permission table');
			}
			
			return \substr($resource, -1) === '*' ? \substr_replace($resource, 'default', -1) : $resource;
		}
		
		return $this->defaultLink;
	}
}
