<?php

declare(strict_types=1);

namespace Admin;

use Security\DB\Account;

class Google2FA extends \PragmaRX\Google2FA\Google2FA
{
	private string $company;
	
	private bool $enabled;
	
	public function __construct(bool $enabled, string $company)
	{
		$this->company = $company;
		$this->enabled = $enabled;
	}
	
	public function getQrCodeImage(\Admin\DB\Administrator $administrator, Account $account, int $size = 300): string
	{
		$link = $this->getQRCodeUrl(
			$this->company,
			$account->login,
			$administrator->google2faSecret,
		);
		
		return 'https://chart.googleapis.com/chart?cht=qr&chs=' . $size . 'x' . $size . '&chl=' . $link;
	}
	
	public function isEnabled(): bool
	{
		return $this->enabled;
	}
}
