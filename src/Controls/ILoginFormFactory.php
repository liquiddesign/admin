<?php

declare(strict_types=1);

namespace Admin\Controls;

interface ILoginFormFactory
{
	public function create(): LoginForm;
}
