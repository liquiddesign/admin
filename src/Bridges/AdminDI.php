<?php

declare(strict_types=1);

namespace Admin\Bridges;

use Admin\Administrator;
use Admin\DB\AdministratorRepository;
use Admin\Route;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Pages\Bridges\PagesTracy;
use Pages\Router;
use Security\Authenticator;
use Security\DB\AccountRepository;
use Security\DB\PermissionRepository;
use Security\DB\RoleRepository;

class AdminDI extends \Nette\DI\CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'defaultLink' => Expect::string()->required(true),
			'menu' => Expect::array([]),
		]);
	}
	
	public function loadConfiguration(): void
	{
		$config = $this->getConfig();
		
		/** @var \Nette\DI\ContainerBuilder $builder */
		$builder = $this->getContainerBuilder();
		
		$builder->addDefinition($this->prefix('administrators'))->setType(AdministratorRepository::class);
		
		$adminDef = $builder->addDefinition($this->prefix('administrator'))->setType(Administrator::class)->setAutowired(false);
		$adminDef->addSetup('setDefaultLink', [$config->defaultLink]);
	
		if ($builder->hasDefinition('routing.router')) {
			/** @var \Nette\DI\Definitions\ServiceDefinition $routerListDef */
			$routerListDef = $builder->getDefinition('routing.router');
			$routerListDef->addSetup('add', [new \Nette\DI\Definitions\Statement(Route::class)]);
		}
		
		
		
		return;
	}
}
