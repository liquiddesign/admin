<?php

namespace Admin;

use Nette\Application\Routers;
use Nette\Utils\Strings;
use Pages\Pages;

class Route extends Routers\Route
{
	public const MASK = 'admin[/<module>/<presenter>[/<action=default>][/<id>]]?lang=<lang>';
	
	public function __construct(?string $defaultMutation, Pages $pages)
	{
		parent::__construct(self::MASK, [
			'module' => [
				\Nette\Routing\Route::VALUE => 'Admin',
				\Nette\Routing\Route::FILTER_IN => static function ($str) {
					return Strings::firstUpper($str) . ':Admin';
				},
				\Nette\Routing\Route::FILTER_OUT => static function ($str) {
					if (Strings::substring($str, -6) === ':Admin') {
						return Strings::firstLower(Strings::substring($str, 0, -6));
					}
					
					if ($str === 'Admin') {
						return Strings::firstLower($str);
					}
					
					return null;
				},
			],
			'presenter' => [
				\Nette\Routing\Route::VALUE => 'Login',
				\Nette\Routing\Route::FILTER_OUT => static function ($str) {
					return self::action2path(Strings::substring($str, 0, 6) === 'Admin:' ? Strings::substring($str, 6) : $str);
				},
			],
			'action' => [\Nette\Routing\Route::VALUE => 'default'],
			null => [
				\Nette\Routing\Route::FILTER_OUT => [$pages, 'unmapParameters'],
				\Nette\Routing\Route::FILTER_IN => [$pages, 'mapParameters'],
			],
			'lang' => [\Nette\Routing\Route::VALUE => $defaultMutation],
		]);
	}
}
