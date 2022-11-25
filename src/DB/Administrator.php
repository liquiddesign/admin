<?php

namespace Admin\DB;

use Admin\Google2FA;
use Nette\Application\ApplicationException;
use Nette\Security\IIdentity;
use Security\DB\Account;
use Security\DB\IUser;
use StORM\IEntityParent;
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
	 * Secret klíč
	 * @column
	 */
	public ?string $google2faSecret = null;
	
	/**
	 * Může editovat URL stránky
	 * @column
	 */
	public bool $urlEditor = false;
	
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

	private Google2FA $google2FA;
	
	public function __construct(array $vars, Google2FA $google2FA, ?IEntityParent $parent = null, array $mutations = [], ?string $mutation = null)
	{
		parent::__construct($vars, $parent, $mutations, $mutation);
		
		$this->google2FA = $google2FA;
	}
	
	/**
	 * @param \StORM\IEntityParent<static> $parent
	 * @param bool $recursive
	 */
	public function setParent(IEntityParent $parent, bool $recursive = true): void
	{
		parent::setParent($parent, $recursive);
		
		if (isset($this->google2FA) || !$this->getRepository() instanceof AdministratorRepository) {
			return;
		}
		
		$this->google2FA = $this->getRepository()->getGoogle2FA();
	}
	
	/**
	 * @return string|int
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
	
	public function has2FAEnabled(): bool
	{
		return !!$this->google2faSecret;
	}
	
	public function get2FAQrCodeImage(Account $account): string
	{
		return $this->google2FA->getQrCodeImage($this, $account);
	}
	
	public function set2FASecretKey(bool $unset = false, bool $save = false): void
	{
		$this->google2faSecret = $unset ? null : $this->google2FA->generateSecretKey();
		
		if (!$save) {
			return;
		}

		$this->update(['google2faSecret' => $this->google2faSecret]);
	}
	
	public function verify2FAKey(string $key, bool $passIfNotSet = false): bool
	{
		if (!$this->google2faSecret && !$passIfNotSet) {
			throw new ApplicationException('2FA secret is not set');
		}

		if (!$this->google2faSecret) {
			return true;
		}
		
		return $this->google2FA->verify($key, $this->google2faSecret);
	}
}
