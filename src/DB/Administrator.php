<?php

namespace Admin\DB;

use Nette\Security\IIdentity;
use Security\DB\Account;
use Security\DB\IUser;
use StORM\RelationCollection;

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
	
	/**
	 * @relationNxN
	 * @var \StORM\RelationCollection<\Security\DB\Account>|\Security\DB\Account[]
	 */
	public RelationCollection $accounts;
	
	/**
	 * @constraint{"onUpdate":"SET NULL","onDelete":"SET NULL"}
	 * @relation
	 */
	public ?Role $role = null;
	
	protected ?Account $account;
	
	function getId()
	{
		return $this->getPK();
	}
	
	function getRoles(): array
	{
		return $this->getValue('role') ? [$this->getValue('role')] : [];
	}
	
	public function getAccount(): ?Account
	{
		return $this->account;
	}
	
	public function setAccount(Account $account): void
	{
		$this->account = $account;
	}
}
