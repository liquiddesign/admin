<?php

declare(strict_types=1);

namespace Admin\Controls;

class MenuItem
{
	public ?string $label = null;
	
	public ?string $link = null;
	
	public array $items = [];
	
	public ?string $icon = null;
	
	public bool $active = false;
}