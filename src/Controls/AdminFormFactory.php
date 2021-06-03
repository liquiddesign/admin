<?php

declare(strict_types=1);

namespace Admin\Controls;

use Admin\Administrator;
use Nette\Localization\Translator;
use StORM\Connection;
use StORM\DIConnection;
use Web\DB\PageRepository;
use Forms\FormFactory;

class AdminFormFactory
{
	private PageRepository $pageRepository;
	
	private Administrator $administrator;
	
	public FormFactory $formFactory;
	
	private Translator $translator;
	
	private DIConnection $connection;
	
	private bool $prettyPages;
	
	private array $mutations;
	
	public function __construct(Administrator $administrator, FormFactory $formFactory, DIConnection $connection, PageRepository $pageRepository, Translator $translator)
	{
		$this->formFactory = $formFactory;
		$this->pageRepository = $pageRepository;
		$this->administrator = $administrator;
		$this->translator = $translator;
		$this->connection = $connection;
		$this->mutations = $formFactory->getDefaultMutations();
	}
	
	public function setPrettyPages(bool $prettyPages): void
	{
		$this->prettyPages = $prettyPages;
	}
	
	public function getMutations(): array
	{
		return $this->mutations;
	}
	
	public function setMutations(array $mutations): void
	{
		$this->mutations = $mutations;
	}
	
	public function create(bool $mutationSelector = false, bool $translatedCheckbox = false): AdminForm
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->formFactory->create(AdminForm::class);
		
		if ($this->administrator->getIdentity() && $this->administrator->getIdentity()->role) {
			$mutations = $this->administrator->getIdentity()->role->getMutations() === null ? $this->getMutations() : $this->administrator->getIdentity()->role->getMutations();
			$form->setMutations($mutations);
			$form->setPrimaryMutation($mutations[0]);
		}
		
		$form->setAdminFormTranslator($this->translator);
		$form->setPrettyPages($this->prettyPages);
		$form->setPageRepository($this->pageRepository);
		$form->setRenderer(new BootstrapRenderer());
		$form->setConnection($this->connection);
		$form->addHidden('uuid')->setNullable();
		$form->addHidden('_defaults')->setNullable()->setOmitted(true);
		$form->addGroup('HLAVNÍ ÚDAJE');
		
		if ($mutationSelector && \count($form->getMutations()) > 1) {
			$form->addMutationSelector($this->translator->translate('admin.selectMutatiom', 'Zvolte mutaci'));
			if ($translatedCheckbox) {
				$form->addTranslatedCheckbox($this->translator->translate('admin.activeMutation', 'Mutace je aktivní'));
			}
			$form->addGroup();
		}
		
		$form->onError[] = function (AdminForm $form) {
			$form->getPresenter()->flashMessage($this->translator->translate('admin.formError', 'Chybně vyplněný formulář!'), 'error');
		};
		
		return $form;
	}
}