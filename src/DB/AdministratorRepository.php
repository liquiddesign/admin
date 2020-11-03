<?php

namespace Admin\DB;

use Security\DB\IUser;
use Security\DB\IUserRepository;

class AdministratorRepository extends \StORM\Repository implements IUserRepository
{
	public function getByAccountLogin(string $login): ?IUser
	{
		$test = $this->many()->where('account.login', $login)->first();
		$test->roles = ['xxx'];
		
		return $test;
	}
}
