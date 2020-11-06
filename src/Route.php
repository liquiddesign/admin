<?php

namespace Admin;

use Nette\Application\Routers;
use Pages\Pages;
use StORM\Entity;

class Route extends Routers\Route
{
	public function __construct(array $mutations = [], Pages $pages)
	{
		$lang = isset($mutations[0]) ? '[<lang=' . $mutations[0] . ' ' . \implode('|', $mutations) . '>/]' : '';
		
		parent::__construct('admin[/<module>/<presenter>[/<action=default>][/<id>]]' . $lang, [
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
				\Nette\Routing\Route::FILTER_OUT => [$pages, 'unmapParameters'],
				\Nette\Routing\Route::FILTER_IN => [$pages, 'mapParameters'],
			],
		]);
	}
}
