<?php

declare(strict_types=1);

namespace Admin\Bridges;

use Admin\DB\AdministratorRepository;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Security\Authenticator;
use Security\DB\AccountRepository;
use Security\DB\PermissionRepository;
use Security\DB\RoleRepository;

class SecurityDI extends \Nette\DI\CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'test' => Expect::string(null),
		]);
	}
	
	public function loadConfiguration(): void
	{
		$config = (array) $this->getConfig();
		
		/** @var \Nette\DI\ContainerBuilder $builder */
		$builder = $this->getContainerBuilder();
		
		// add repositories
		$builder->addDefinition($this->prefix('administrators'))->setType(AdministratorRepository::class);
		
		
		return;
	}
}
