<?php

declare(strict_types=1);

namespace Admin\Controls;

use App\Admin\Controls\AdminFormFactory;
use Nette\DI\Container;
use StORM\ICollection;

class AdminGridFactory
{
	private AdminFormFactory $formFactory;
	
	public function __construct(Container $container)
	{
		$this->formFactory = $container->getService('admin.formFactory');
	}
	
	public function create(ICollection $source, ?int $defaultOnPage = null, ?string $defaultOrderExpression = null, ?string $defaultOrderDir = null, bool $encodeId = false)
	{
		$grid = new AdminGrid($source, $defaultOnPage, $defaultOrderExpression, $defaultOrderDir, $encodeId);
		$grid->setFormsFactory($this->formFactory);
		
		return $grid;
	}
}
