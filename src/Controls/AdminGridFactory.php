<?php

declare(strict_types=1);

namespace Admin\Controls;

use StORM\ICollection;

class AdminGridFactory
{
	private AdminFormFactory $formFactory;
	
	public function __construct(AdminFormFactory $formFactory)
	{
		$this->formFactory = $formFactory;
	}
	
	public function create(ICollection $source, ?int $defaultOnPage = null, ?string $defaultOrderExpression = null, ?string $defaultOrderDir = null, bool $encodeId = false)
	{
		$grid = new AdminGrid($source, $defaultOnPage, $defaultOrderExpression, $defaultOrderDir, $encodeId);
		$grid->setFormsFactory($this->formFactory);
		
		return $grid;
	}
}
