<?php

namespace Admin;

use Nette\Application\Routers;
use StORM\Entity;

class Route extends Routers\Route
{
	public function __construct()
	{
		parent::__construct('admin[/<module>/<presenter>[/<action=default>][/<id>]][<lang=cs cs|en>/]', [
			'module' => [
				\Nette\Routing\Route::VALUE => 'Admin',
				\Nette\Routing\Route::FILTER_IN => static function ($str) {
					return \ucfirst($str) . ':Admin';
				},
				\Nette\Routing\Route::FILTER_OUT => static function ($str) {
					if (\substr($str, -6) === ':Admin') {
						return \strtolower(\substr($str, 0, -6));
					}
					
					if ($str === 'Admin') {
						return \strtolower($str);
					}
					
					return null;
				},
			],
			'presenter' => [
				\Nette\Routing\Route::VALUE => 'Login',
				\Nette\Routing\Route::FILTER_OUT => static function ($str) {
					return \substr($str, 0, 6) === 'Admin:' ? \strtolower(\substr($str, 6)) : \strtolower($str);
				},
			],
			'action' => [\Nette\Routing\Route::VALUE => 'default'],
			null => [
				\Nette\Routing\Route::FILTER_OUT => static function (array $params) {
					foreach ($params as $k => $v) {
						if ($v instanceof Entity) {
							$params[$k] = (string)$v;
						}
					}
					
					return $params;
				},
			],
		]);
	}
}
