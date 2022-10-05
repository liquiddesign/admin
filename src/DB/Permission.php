<?php

declare(strict_types=1);

namespace Admin\DB;

/**
 * @table
 * @index{"name":"permissions","unique":true,"columns":["resource","privilege","fk_role"]}
 */
class Permission extends \StORM\Entity
{
	/**
	 * @column
	 */
	public string $resource;
	
	/**
	 * @column
	 */
	public ?string $privilege;
	
	/**
	 * @constraint{"onUpdate":"CASCADE","onDelete":"CASCADE"}
	 * @relation
	 */
	public ?Role $role;
}
