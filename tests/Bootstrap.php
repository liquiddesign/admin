<?php

declare(strict_types=1);

namespace Security\Tests;

\define('TEMP_DIR', __DIR__ . '/temp/' . \getmypid());
\define('CONFIGS_DIR', __DIR__ . '/_configs');
\define('ENTITIES_DIR', __DIR__ . '/DB');

use Migrator\Bridges\MigratorDI;
use Nette\DI\Container;
use Messages\Bridges\MessagesDI;
use Security\Bridges\SecurityDI;
use StORM\Bridges\StormDI;

class Bootstrap
{
	public static function createContainer(): Container
	{
		@\mkdir(\dirname(\TEMP_DIR));
		@\mkdir(\TEMP_DIR);
		
		$config = CONFIGS_DIR . '/config.neon';
		$extensions = [
			'storm' => new StormDI(),
			'migrator' => new MigratorDI(),
			'lqdSecurity' => new SecurityDI(),
		];
		$loadDefinitions = [];
		
		$loader = new \Nette\DI\ContainerLoader(\TEMP_DIR, true);
		
		$class = $loader->load(static function (\Nette\DI\Compiler $compiler) use ($config, $extensions, $loadDefinitions): void {
			$compiler->loadConfig($config);
			
			foreach ($loadDefinitions as $name => $class) {
				$compiler->loadDefinitionsFromConfig([$name => $class]);
			}
			
			foreach ($extensions as $name => $extension) {
				$compiler->addExtension($name, $extension);
			}
		});
		
		return new $class();
	}
}