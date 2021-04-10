<?php

declare(strict_types=1);

namespace Admin\Bridges;

use Admin\Administrator;
use Admin\Authorizator;
use Admin\Controls\ILoginFormFactory;
use Admin\Controls\IMenuFactory;
use Admin\DB\AdministratorRepository;
use Admin\Route;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

class AdminDI extends \Nette\DI\CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'defaultLink' => Expect::string()->required(true),
			'fallbackLink' => Expect::string()->required(true),
			'menu' => Expect::array([]),
			'mutations' => Expect::list([]),
			'superRole' => Expect::string(null),
		]);
	}
	
	public function loadConfiguration(): void
	{
		$config = $this->getConfig();
		
		/** @var \Nette\DI\ContainerBuilder $builder */
		$builder = $this->getContainerBuilder();
		
		$builder->addDefinition($this->prefix('administrators'))->setType(AdministratorRepository::class);
		
		$factory = $builder->addFactoryDefinition($this->prefix('menuFactory'))->setImplement(IMenuFactory::class)->getResultDefinition();
		foreach ($config->menu as $name => $value) {
			$link = \is_array($value) && isset($value['link']) ? $value['link'] : (\is_string($value) ? $value : null);
			$items = \is_array($value) && $value['items'] ? $value['items'] : [];
			$icon = \is_array($value) && isset($value['icon']) ? $value['icon'] : null;
			
			$factory->addSetup('addMenuItem', [$name, $link, $items, $icon]);
		}
		
		$builder->addFactoryDefinition($this->prefix('loginFormFactory'))->setImplement(ILoginFormFactory::class);
		
		$adminDef = $builder->addDefinition($this->prefix('administrator'))->setType(Administrator::class)->setAutowired(false);
		$adminDef->addSetup('setDefaultLink', [$config->defaultLink]);
		$adminDef->addSetup('setFallbackLink', [$config->fallbackLink]);
		
		if ($builder->hasDefinition('routing.router')) {
			/** @var \Nette\DI\Definitions\ServiceDefinition $routerListDef */
			$routerListDef = $builder->getDefinition('routing.router');
			$routerListDef->addSetup('add', [new \Nette\DI\Definitions\Statement(Route::class, [$config->mutations])]);
		}
		
		// add authorizator
		$authorizator = $builder->addDefinition('authorizator')->setType(Authorizator::class);
		$authorizator->addSetup('setSuperRole', [$config->superRole]);
		
		return;
	}
}
