<?php

namespace Messages\Tests\Cases;

require_once __DIR__ . '/../../vendor/autoload.php';

use Messages\Tests\Bootstrap;
use Tester\Assert;
use Tester\TestCase;

/**
 * Class Test
 * @package Tests
 */
class Test extends TestCase
{
	public function testExists(): void
	{
		$container = Bootstrap::createContainer();
		
	}
}

(new Test())->run();
