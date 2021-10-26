<?php

declare(strict_types=1);

namespace Admin\Controls;

interface IMenuFactory
{
	public function create(): Menu;
}
