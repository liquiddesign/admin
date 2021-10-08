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
use Admin\Route;
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
			'menu' => Expect::array([]),
			'mutations' => Expect::list([]),
			'defaultMutation' => Expect::list(null),
			'superRole' => Expect::string(null),
			'prettyPages' => Expect::bool(false),
			'serviceMode' => Expect::bool(false),
			'adminGrid' => Expect::array([]),
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
			$itemName = is_array($value) && isset($value['itemName']) ? $value['itemName'] : [];

			$factory->addSetup('addMenuItem', [$name, $link, $items, $icon, $itemName]);
		}
		
		$builder->addFactoryDefinition($this->prefix('loginFormFactory'))->setImplement(ILoginFormFactory::class);
		$administratorDef = $builder->addDefinition($this->prefix('administrator'))->setType(Administrator::class)->setAutowired(false);
		$administratorDef->addSetup('setDefaultLink', [$config->defaultLink]);
		$administratorDef->addSetup('setFallbackLink', [$config->fallbackLink]);
		
		if ($builder->hasDefinition('routing.router')) {
			/** @var \Nette\DI\Definitions\ServiceDefinition $routerListDef */
			$routerListDef = $builder->getDefinition('routing.router');
			$routerListDef->addSetup('add', [new \Nette\DI\Definitions\Statement(Route::class, [$config->mutations[0] ?? null])]);
		}
		
		$authorizator = $builder->addDefinition('authorizator')->setType(Authorizator::class);
		
		if ($config->serviceMode) {
			$authorizator->addSetup('setSuperRole', [$config->superRole]);
		}
		
		$formDef = $builder->addDefinition($this->prefix('adminFormFactory'))->setFactory(AdminFormFactory::class, [$administratorDef]);
		$formDef->addSetup('setPrettyPages', [$config->prettyPages]);
		$formDef->addSetup('setMutations', [$config->mutations]);
		
		$gridDef = $builder->addDefinition($this->prefix('adminGridFactory'))->setFactory(AdminGridFactory::class, [$administratorDef]);
		$gridDef->addSetup('setItemsPerPage', [$config->adminGrid['itemsPerPage'] ?? array(10, 20, 50, 100)]);
		$gridDef->addSetup('setShowItemsPerPage', [$config->adminGrid['showItemsPerPage'] ?? true]);
		$gridDef->addSetup('setDefaultOnPage', [$config->adminGrid['defaulOnPage'] ?? null]);

		return;
	}


	public function beforeCompile(): void
	{
		$config = $this->getConfig();
		$this->getContainerBuilder()->resolve();

		foreach ($this->findByType(BackendPresenter::class) as $def) {
			$setup = new Statement('$langs', [$config->mutations]);
			$def->addSetup($setup);
		}
	}

	private function findByType(string $type): array
	{
		return \array_filter($this->getContainerBuilder()->getDefinitions(), function ($def) use ($type): bool {
			return \is_a($def->getType(), $type, true);
		});
	}
}
