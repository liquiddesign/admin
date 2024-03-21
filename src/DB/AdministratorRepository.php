<?php

namespace Admin\DB;

use Admin\Google2FA;
use Security\DB\IUserRepository;
use Security\DB\UserRepositoryTrait;
use StORM\DIConnection;
use StORM\SchemaManager;

/**
 * @template T of \Admin\DB\Administrator
 * @extends \StORM\Repository<T>
 */
class AdministratorRepository extends \StORM\Repository implements IUserRepository
{
	use UserRepositoryTrait;

	
	private Google2FA $google2FA;
	
	public function __construct(DIConnection $connection, SchemaManager $schemaManager, Google2FA $google2FA)
	{
		parent::__construct($connection, $schemaManager);
		
		$this->google2FA = $google2FA;
		$this->injectEntityArguments($google2FA);
	}
	
	public function getGoogle2FA(): Google2FA
	{
		return $this->google2FA;
	}
}
