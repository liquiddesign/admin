<?php

declare(strict_types=1);

namespace Admin;

use Nette\Forms\Controls\BaseControl;
use Security\DB\Account;

class FormValidators
{
	public static function checkOldPassword(BaseControl $control, Account $account): bool
	{
		if ($control->getValue() === '') {
			return true;
		}
		
		return $account->checkPassword($control->getValue());
	}

	public static function checkUniqueCode(BaseControl $input, array $args): bool
	{
		[$repository, $existingEntity] = $args;

		$entity = $repository->many()->where('this.code', $input->getValue())->first();

		return !$entity || ($existingEntity && $entity->getPK() === $existingEntity->getPK());
	}
}
