<?php

declare(strict_types=1);

namespace Admin\Controls;

interface IGoogle2FAFormFactory
{
	public function create(): Google2FAForm;
}
