<?php

declare(strict_types=1);

namespace Admin\Controls;

use Nette\Http\Session;
use StORM\ICollection;

class AdminGridFactory
{
	private AdminFormFactory $formFactory;

	private Session $session;

	public function __construct(AdminFormFactory $formFactory,Session $session)
	{
		$this->formFactory = $formFactory;
		$this->session = $session;
	}
	
	public function create(ICollection $source, ?int $defaultOnPage = null, ?string $defaultOrderExpression = null, ?string $defaultOrderDir = null, bool $encodeId = false)
	{
		$grid = new AdminGrid($source, $defaultOnPage, $defaultOrderExpression, $defaultOrderDir, $encodeId, $this->session);
		$grid->setFormsFactory($this->formFactory);
		
		return $grid;
	}
}
