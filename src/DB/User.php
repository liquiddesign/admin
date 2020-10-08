<?php

namespace Admin\DB;

use Nette\Security\IIdentity;

/**
 * @table
 */
class User extends \StORM\Entity implements IIdentity
{
	/**
	 * Celé jméno
	 * @column
	 */
	public ?string $fullname = null;
	
	/**
	 * Login
	 * @column
	 */
	public string $email;
	
	/**
	 * Heslo
	 * @column
	 */
	public string $password;
	
	/**
	 * Returns the ID of user.
	 * @return mixed
	 */
	public function getId()
	{
		return $this->getPK();
	}
	
	/**
	 * @return string[]
	 */
	public function getRoles(): array
	{
		return [];
	}
}
