<?php

declare(strict_types=1);

namespace Admin\DB;

/**
 * @table
 */
class Role extends \StORM\Entity
{
	/**
	 * @column
	 */
	public string $name;
	
	/**
	 * Povolené mutace oddělené středníky, hodnota NULL znamená vše povoleno
	 * @column
	 */
	public ?string $mutations = null;
	
	/**
	 * @return array<string>|null
	 */
	public function getMutations(): ?array
	{
		return $this->mutations === null ? null : ($this->mutations ? \explode(';', $this->mutations) : []);
	}
}
