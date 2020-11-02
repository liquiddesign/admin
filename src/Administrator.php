<?php

declare(strict_types=1);

namespace Admin;

use Nette\Security\IAuthenticator;
use Nette\Security\IAuthorizator;
use Nette\Security\IUserStorage;

class Administrator extends \Nette\Security\User
{
	public function __construct(IUserStorage $storage, ?IAuthenticator $authenticator = null, ?IAuthorizator $authorizator = null)
	{
		parent::__construct(clone $storage, $authenticator, $authorizator);
		
		/** @var \Nette\Http\UserStorage $storage */
		$storage = $this->getStorage();
		$storage->setNamespace('admin');
	}
}
