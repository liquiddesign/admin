<?php
declare(strict_types=1);

namespace Admin\DB;

interface IGeneralAjaxRepository
{
	/**
	 * @param bool $includeHidden
	 * @param string|null $q
	 * @param int|null $page
	 * @return array<string, string>
	 */
	public function getAjaxArrayForSelect(bool $includeHidden = true, ?string $q = null, ?int $page = null): array;
}
