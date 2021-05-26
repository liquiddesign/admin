<?php

declare(strict_types=1);

namespace Admin\Controls;

use Admin\Administrator;
use Admin\DB\ChangelogRepository;
use Nette\Localization\Translator;
use Web\DB\PageRepository;
use Forms\FormFactory;

class AdminFormFactory
{
	private ChangelogRepository $changelogRepository;
	
	private PageRepository $pageRepository;
	
	private Administrator $administrator;

	public FormFactory $formFactory;

	private Translator $translator;

	private bool $prettyPages;

	public function __construct(Administrator $administrator, FormFactory $formFactory, PageRepository $pageRepository, Translator $translator, ChangelogRepository $changelogRepository)
	{
		$this->formFactory = $formFactory;
		$this->pageRepository = $pageRepository;
		$this->administrator = $administrator;
		$this->translator = $translator;
		$this->changelogRepository = $changelogRepository;
	}

	public function setPrettyPages(bool $prettyPages): void
	{
		$this->prettyPages = $prettyPages;
	}
	
	public function getMutations(): array
	{
		return $this->formFactory->getDefaultMutations();
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
		$form->addHidden('uuid')->setNullable();
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
		
		$form->onSuccess[] = function (AdminForm $form) {
			if ($form->entityName) {
				$this->changelogRepository->createOne([
					'user' => $form->getPresenter()->admin->getIdentity()->getAccount()->login,
					'entity' => $form->entityName,
					'objectId' => $form->getValues()['uuid'],
					'type' => $form->getPresenterIfExists()->getParameter('action')
				]);
			}
		};

		return $form;
	}
}