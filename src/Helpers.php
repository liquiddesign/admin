<?php

declare(strict_types=1);

namespace Admin;

use Nette\Utils\FileSystem;

class Helpers
{
	public static function generateUserDirectories(string $wwwDir, $dirs = [], $subDirs = []): void
	{
		foreach ($dirs as $dir) {
			$rootDir = $wwwDir . '/userfiles/' . $dir;
			FileSystem::createDir($rootDir);
			
			foreach ($subDirs as $subDir) {
				FileSystem::createDir($rootDir . '/' . $subDir);
			}
		}
	}
}
