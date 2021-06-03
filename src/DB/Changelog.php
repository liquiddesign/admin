<?php

declare(strict_types=1);

namespace Admin\DB;

use StORM\Entity;

/**
 * @table
 */
class Changelog extends Entity
{
	/**
	 * @column
	 */
	public string $user;
	
	/**
	 * @column{"type":"timestamp","default":"CURRENT_TIMESTAMP"}
	 */
	public string $created;
	
	/**
	 * @column
	 */
	public string $entity;
	
	/**
	 * @column
	 */
	public ?string $objectId;
	
	/**
	 * @column
	 */
	public string $type;
}