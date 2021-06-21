<?php

declare(strict_types=1);

namespace Admin\Controls;

use Admin\Administrator;
use Admin\DB\ChangelogRepository;
use Nette\Http\Session;
use Nette\Localization\Translator;
use StORM\ICollection;

class AdminGridFactory
{
	private ChangelogRepository $changelogRepository;
	
	private AdminFormFactory $formFactory;
	
	private Administrator $administrator;

	private Session $session;

	private Translator $translator;

	private array $itemsPerPage;

	private bool $showItemsPerPage;

	private ?int $defaultOnPage;

	public function __construct(Administrator $administrator, AdminFormFactory $formFactory, Session $session, Translator $translator, ChangelogRepository $changelogRepository)
	{
		$this->formFactory = $formFactory;
		$this->session = $session;
		$this->translator = $translator;
		$this->changelogRepository = $changelogRepository;
		$this->administrator = $administrator;
	}

	public function setItemsPerPage(array $items): void
	{
		$this->itemsPerPage = $items;
	}

	public function setShowItemsPerPage(bool $show): void
	{
		$this->showItemsPerPage = $show;
	}

	public function setDefaultOnPage(?int $defaultOnPage = null): void
	{
		$this->defaultOnPage = $defaultOnPage;
	}
	
	public function create(ICollection $source, ?int $defaultOnPage = null, ?string $defaultOrderExpression = null, ?string $defaultOrderDir = null, bool $encodeId = false): AdminGrid
	{
		$grid = new AdminGrid($source, $defaultOnPage, $defaultOrderExpression, $defaultOrderDir, $encodeId, $this->session);
		$grid->setFormsFactory($this->formFactory);
		$grid->setItemsPerPage($this->itemsPerPage);
		$grid->setShowItemsPerPage($this->showItemsPerPage);
		$grid->setChangelogRepository($this->changelogRepository);

		if ($this->defaultOnPage && !$defaultOnPage) {
			$grid->setDefaultOnPage($this->defaultOnPage);
		}

		$grid->setTranslator($this->translator);
		
		$grid->onUpdateRow[] = function ($object) use ($grid): void {
			if ($grid->entityName) {
				$this->changelogRepository->createOne([
					'user' => $this->administrator->getIdentity()->getAccount()->login,
					'entity' => $grid->entityName,
					'objectId' => $object,
					'type' => 'update',
				]);
			}
		};
		
		$grid->onDeleteRow[] = function ($object) use ($grid): void {
			if ($grid->entityName) {
				$this->changelogRepository->createOne([
					'user' => $this->administrator->getIdentity()->getAccount()->login,
					'entity' => $grid->entityName,
					'objectId' => $object->uuid,
					'type' => 'delete',
				]);
			}
		};
		
		return $grid;
	}
}
