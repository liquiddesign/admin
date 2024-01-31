<?php

namespace Admin;

use Nette\Application\Routers;
use Pages\Pages;

class Route extends Routers\Route
{
	public const MASK = '[/<module>/<presenter>[/<action=default>][/<id>]]?lang=<lang>';
	
	public function __construct(?string $defaultMutation, string $prefix, Pages $pages)
	{
		parent::__construct($prefix . self::MASK, [
			'module' => [
				\Nette\Routing\Route::VALUE => 'Admin',
				\Nette\Routing\Route::FILTER_IN => static function ($str) {
					return \ucfirst($str) . ':Admin';
				},
				\Nette\Routing\Route::FILTER_OUT => static function ($str) {
					if (\substr($str, -6) === ':Admin') {
						return \lcfirst(\substr($str, 0, -6));
					}
					
					if ($str === 'Admin') {
						return \lcfirst($str);
					}
					
					return null;
				},
			],
			'presenter' => [
				\Nette\Routing\Route::VALUE => 'Login',
				\Nette\Routing\Route::FILTER_OUT => static function ($str) {
					return self::action2path(\substr($str, 0, 6) === 'Admin:' ? \substr($str, 6) : $str);
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
