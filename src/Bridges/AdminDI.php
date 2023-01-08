<?php

declare(strict_types=1);

namespace Admin\Bridges;

use Admin\Administrator;
use Admin\Authorizator;
use Admin\BackendPresenter;
use Admin\Controls\AdminFormFactory;
use Admin\Controls\AdminGridFactory;
use Admin\Controls\ILoginFormFactory;
use Admin\Controls\IMenuFactory;
use Admin\DB\AdministratorRepository;
use Admin\Google2FA;
use Admin\Route;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

class AdminDI extends \Nette\DI\CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'defaultLink' => Expect::string()->required(true),
			'fallbackLink' => Expect::string()->required(true),
			'menu' => Expect::arrayOf(Expect::structure([
				'link' => Expect::string(),
				'items' => Expect::array([]),
				'icon' => Expect::string(),
				'itemName' => Expect::array([]),
			])),
			'mutations' => Expect::list([]),
			'defaultMutation' => Expect::list(null),
			'superRole' => Expect::string(null),
			'authorizatorEnabled' => Expect::bool(null),
			'prettyPages' => Expect::bool(false),
			'serviceMode' => Expect::bool(false),
			'adminGrid' => Expect::array([]),
			'google2FA' => Expect::structure([
				'enabled' => Expect::bool(false),
				'company' => Expect::string('Admin'),
			]),
		]);
	}
	
	public function loadConfiguration(): void
	{
		/** @var \stdClass $config */
		$config = $this->getConfig();
		
		/** @var \Nette\DI\ContainerBuilder $builder */
		$builder = $this->getContainerBuilder();
		
		$builder->addDefinition($this->prefix('administrators'), new ServiceDefinition())->setType(AdministratorRepository::class);
		$builder->addDefinition($this->prefix('google2FA'), new ServiceDefinition())->setType(Google2FA::class)
			->setArgument('enabled', $config->google2FA->enabled ?? false)
			->setArgument('company', $config->google2FA->company ?? 'Admin');
		
		$factory = $builder->addFactoryDefinition($this->prefix('menuFactory'))->setImplement(IMenuFactory::class)->getResultDefinition();
		
		foreach ($config->menu as $name => $value) {
			/** @var \stdClass $value */
			$factory->addSetup('addMenuItem', [$name, $value->link, $value->items, $value->icon, $value->itemName]);
		}
		
		$builder->addFactoryDefinition($this->prefix('loginFormFactory'))->setImplement(ILoginFormFactory::class);
		$administratorDef = $builder->addDefinition($this->prefix('administrator'), new ServiceDefinition())->setType(Administrator::class)->setAutowired(false);
		$administratorDef->addSetup('setDefaultLink', [$config->defaultLink]);
		$administratorDef->addSetup('setFallbackLink', [$config->fallbackLink]);
		
		if ($builder->hasDefinition('routing.router')) {
			/** @var \Nette\DI\Definitions\ServiceDefinition $routerListDef */
			$routerListDef = $builder->getDefinition('routing.router');
			$routerListDef->addSetup('add', [new \Nette\DI\Definitions\Statement(Route::class, [$config->mutations[0] ?? null])]);
		}
		
		$authorizator = $builder->addDefinition('authorizator', new ServiceDefinition())
			->setArgument('enabled', $config->authorizatorEnabled ?? true)
			->setType(Authorizator::class);
		
		if ($config->serviceMode) {
			$authorizator->addSetup('setSuperRole', [$config->superRole]);
		}
		
		$formDef = $builder->addDefinition($this->prefix('adminFormFactory'), new ServiceDefinition())->setFactory(AdminFormFactory::class, [$administratorDef]);
		$formDef->addSetup('setPrettyPages', [$config->prettyPages]);
		$formDef->addSetup('setMutations', [$config->mutations]);
		
		$gridDef = $builder->addDefinition($this->prefix('adminGridFactory'), new ServiceDefinition())->setFactory(AdminGridFactory::class, [$administratorDef]);
		$gridDef->addSetup('setItemsPerPage', [$config->adminGrid['itemsPerPage'] ?? array(10, 20, 50, 100)]);
		$gridDef->addSetup('setShowItemsPerPage', [$config->adminGrid['showItemsPerPage'] ?? true]);
		$gridDef->addSetup('setDefaultOnPage', [$config->adminGrid['defaulOnPage'] ?? null]);
		
		/** @var \Nette\DI\Definitions\FactoryDefinition $definition */
		$definition = $builder->getDefinition('latte.latteFactory');
		$definition->getResultDefinition()->addSetup('addExtension', [new \Latte\Essential\RawPhpExtension()]);
	}
	
	public function beforeCompile(): void
	{
		/** @var \stdClass $config */
		$config = $this->getConfig();
		$this->getContainerBuilder()->resolve();
		
		foreach ($this->findByType(BackendPresenter::class) as $def) {
			if (!$def instanceof ServiceDefinition) {
				continue;
			}
			
			$setup = new Statement('$langs', [$config->mutations]);
			$def->addSetup($setup);
		}
	}
	
	/**
	 * @param string $type
	 * @return \Nette\DI\Definitions\Definition[]
	 */
	private function findByType(string $type): array
	{
		return \array_filter($this->getContainerBuilder()->getDefinitions(), function ($def) use ($type): bool {
			return \is_a($def->getType(), $type, true);
		});
	}
}
