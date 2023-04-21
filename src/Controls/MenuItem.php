<?php

declare(strict_types=1);

namespace Admin\Controls;

class MenuItem
{
	public ?string $label = null;
	
	public ?string $link = null;
	
	public ?string $icon = null;
	
	public bool $active = false;
	
	/**
	 * @var array<\Admin\Controls\MenuItem>
	 */
	public array $items = [];
	
	/**
	 * @var array<string>
	 */
	public array $itemName = [];
}
