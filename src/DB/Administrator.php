<?php

namespace Admin\DB;

use Nette\Security\IIdentity;
use Security\DB\Account;
use Security\DB\IUser;
use Security\DB\Role;

/**
 * @table
 */
class Administrator extends \StORM\Entity implements IIdentity, IUser
{
	/**
	 * Celé jméno
	 * @column
	 */
	public ?string $fullName = null;
	
	public ?string $role = null;
	
	/**
	 * @relation
	 * @constraint
	 */
	public ?Account $account;
	
	function getId()
	{
		return $this->getValue('account');
	}
	
	function getRoles(): array
	{
		return $this->getAccount() ? [$this->getAccount()->role] : [];
	}
	
	public function getAccount(): ?Account
	{
		dump($this);
		return $this->account;
	}
}
