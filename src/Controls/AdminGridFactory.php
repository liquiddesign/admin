<?php

declare(strict_types=1);

namespace Admin\Controls;

use Nette\Http\Session;
use Nette\Localization\Translator;
use StORM\ICollection;

class AdminGridFactory
{
	private AdminFormFactory $formFactory;

	private Session $session;

	private Translator $translator;

	private array $itemsPerPage;

	private bool $showItemsPerPage;

	public function __construct(AdminFormFactory $formFactory,Session $session, Translator $translator)
	{
		$this->formFactory = $formFactory;
		$this->session = $session;
		$this->translator = $translator;
	}

	public function setItemsPerPage(array $items): void
	{
		$this->itemsPerPage = $items;
	}

	public function setShowItemsPerPage(bool $show): void
	{
		$this->showItemsPerPage = $show;
	}
	
	public function create(ICollection $source, ?int $defaultOnPage = null, ?string $defaultOrderExpression = null, ?string $defaultOrderDir = null, bool $encodeId = false)
	{
		$grid = new AdminGrid($source, $defaultOnPage, $defaultOrderExpression, $defaultOrderDir, $encodeId, $this->session);
		$grid->setFormsFactory($this->formFactory);
		$grid->setItemsPerPage($this->itemsPerPage);
		$grid->setShowItemsPerPage($this->showItemsPerPage);
		$grid->setAdminGridTranslator($this->translator);
		
		return $grid;
	}
}
