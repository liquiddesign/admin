<?php

declare(strict_types=1);

namespace Admin;

use Nette\Forms\IControl;
use Security\DB\Account;

class FormValidators
{
	public static function checkOldPassword(IControl $control, Account $account): bool
	{
		if ($control->getValue() == '') {
			return true;
		}
		
		return $account->checkPassword($control->getValue());
	}
}
