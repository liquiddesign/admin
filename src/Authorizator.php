<?php

declare(strict_types=1);

namespace Admin;

use Admin\DB\PermissionRepository;
use Nette\Security\IAuthorizator;

class Authorizator implements IAuthorizator
{
	private ?string $superRole = null;
	
	/**
	 * @var bool[]
	 */
	private array $allowedCache = [];
	
	private PermissionRepository $permissionRepo;
	
	private bool $enabled;
	
	public function __construct(bool $enabled, PermissionRepository $permissionRepo)
	{
		$this->enabled = $enabled;
		$this->permissionRepo = $permissionRepo;
	}
	
	public function isAllowed($role, $resource, $privilege): bool
	{
		if (!$this->enabled) {
			return false;
		}
		
		if ($role === $this->superRole) {
			return true;
		}
		
		return $this->allowedCache[$role . '-' . $resource . '-' . $privilege] ??= $this->permissionRepo->isAllowed($role, $resource, $privilege === null ? null : \intval($privilege));
	}
	
	public function setSuperRole(?string $role): void
	{
		$this->superRole = $role;
	}
}
