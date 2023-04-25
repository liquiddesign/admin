<?php

namespace Admin\Configs;

enum AdminFormFactoryShopsConfig: int
{
	case SHOPS_DISABLED = 0;

	/**
	 * Adds hidden input with shop ID
	 */
	case SHOPS_ENABLED = 1;

	/**
	 * Adds hidden input without value
	 */
	case SHOPS_ENABLED_DEFAULT_NULL = 2;
}
