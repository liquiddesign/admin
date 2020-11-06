<?php

namespace Admin\DB;

use Security\DB\IUser;
use Security\DB\IUserRepository;
use Security\DB\UserRepositoryTrait;

class AdministratorRepository extends \StORM\Repository implements IUserRepository
{
	use UserRepositoryTrait;
}
