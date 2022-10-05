<?php

declare(strict_types=1);

namespace Admin\DB;

use Nette\Utils\Arrays;

/**
 * Class RoleRepository
 * @extends \StORM\Repository<\Admin\DB\Permission>
 */
class PermissionRepository extends \StORM\Repository
{
	private const FREE_RESOURCES = [':Admin:Error4xx:default'];
	
	public function isAllowed(string $role, string $resource, ?string $privilege = null): bool
	{
		if (Arrays::contains(self::FREE_RESOURCES, $resource)) {
			return true;
		}
		
		return !$this->many()
			->where("'$resource' LIKE CONCAT(REPLACE(this.resource, ':*', ''),'%')")
			->where('this.privilege IS NULL OR :privilege IS NULL OR this.privilege = :privilege', ['privilege' => $privilege])
			->where('fk_role', $role)->isEmpty();
	}
}
