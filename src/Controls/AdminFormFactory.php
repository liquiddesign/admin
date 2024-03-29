<?php

declare(strict_types=1);

namespace Admin\Controls;

use Admin\Administrator;
use Admin\DB\ChangelogRepository;
use Forms\Form;
use Forms\FormFactory;
use Grid\Datagrid;
use Nette\Forms\Controls\BaseControl;
use Nette\Localization\Translator;
use Pages\DB\IPageRepository;
use StORM\Collection;
use StORM\DIConnection;

class AdminFormFactory
{
	public FormFactory $formFactory;

	private ChangelogRepository $changelogRepository;

	private IPageRepository $pageRepository;

	private Administrator $administrator;

	private Translator $translator;

	private DIConnection $connection;

	private bool $prettyPages;
	
	/**
	 * @var string[]
	 */
	private array $mutations;

	private ?string $defaultMutation = null;

	public function __construct(
		Administrator $administrator,
		FormFactory $formFactory,
		DIConnection $connection,
		IPageRepository $pageRepository,
		Translator $translator,
		ChangelogRepository $changelogRepository
	) {
		$this->formFactory = $formFactory;
		$this->pageRepository = $pageRepository;
		$this->administrator = $administrator;
		$this->translator = $translator;
		$this->connection = $connection;
		$this->mutations = $formFactory->getDefaultMutations();
		$this->changelogRepository = $changelogRepository;
	}

	public function setPrettyPages(bool $prettyPages): void
	{
		$this->prettyPages = $prettyPages;
	}

	public function getPrettyPages(): bool
	{
		return $this->prettyPages;
	}
	
	/**
	 * @return string[]
	 */
	public function getMutations(): array
	{
		return $this->mutations;
	}

	public function setMutations(array $mutations): void
	{
		$this->mutations = $mutations;
	}

	public function getDefaultMutation(): ?string
	{
		return $this->defaultMutation;
	}

	public function setDefaultMutation(?string $defaultMutation): void
	{
		$this->defaultMutation = $defaultMutation;
	}

	public function create(
		bool $mutationSelector = false,
		bool $translatedCheckbox = false,
		bool $generateUuid = false,
		bool $defaultsField = false,
		bool $defaultGroup = true,
		bool $forcePrimary = true
	): AdminForm {
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->formFactory->create(AdminForm::class);

		if ($this->administrator->getIdentity() instanceof \Admin\DB\Administrator && $this->administrator->getIdentity()->role) {
			$mutations = $this->administrator->getIdentity()->role->getMutations() === null ? $this->getMutations() : $this->administrator->getIdentity()->role->getMutations();
			$form->setMutations($mutations);
			$form->setPrimaryMutation($this->getDefaultMutation() ?? $mutations[0]);
		}

		$form->setAdministrator($this->administrator);
		$form->setAdminFormTranslator($this->translator);
		$form->setPrettyPages($this->prettyPages);
		$form->setPageRepository($this->pageRepository);
		$form->setRenderer(new BootstrapRenderer());
		$form->setConnection($this->connection);
		$form->addHidden('uuid')->setDefaultValue($generateUuid ? DIConnection::generateUuid() : null)->setNullable();

		if ($defaultsField) {
			$form->addHidden('_defaults')->setNullable()->setOmitted(true);
		}

		$form->onAnchor[] = function (AdminForm $form): void {
			if ($form->getComponent(AdminForm::MUTATION_SELECTOR_NAME, false) instanceof BaseControl && $lang = $form->getPresenter()->getParameter('selectedLang')) {
				$form->getComponent(AdminForm::MUTATION_SELECTOR_NAME, false)->setDefaultValue($lang);
			}
		};

		if ($defaultGroup) {
			$form->addGroup($this->translator->translate('admin.mainContainer', 'HLAVNÍ ÚDAJE'));
		}

		if ($mutationSelector && \count($form->getMutations()) > 1) {
			$form->addMutationSelector($this->translator->translate('admin.selectMutatiom', 'Zvolte mutaci'));
			
			if ($translatedCheckbox) {
				$form->addTranslatedCheckbox($this->translator->translate('admin.activeMutation', 'Mutace je aktivní'), Form::MUTATION_TRANSLATOR_NAME, false, $forcePrimary);
			}

			$form->addGroup();
		}

		$form->onError[] = function (AdminForm $form): void {
			$form->getPresenter()->flashMessage($this->translator->translate('admin.formError', 'Chybně vyplněný formulář!'), 'error');
		};

		$form->onSuccess[] = function (AdminForm $form): void {
			if ($form->entityName && $this->administrator->getIdentity() instanceof \Admin\DB\Administrator) {
				$this->changelogRepository->createOne([
					'user' => $this->administrator->getIdentity()->getAccount()->login,
					'entity' => $form->entityName,
					'objectId' => $form->getValues()['uuid'],
					'type' => $form->getPresenterIfExists()->getParameter('action'),
				]);
			}
		};

		return $form;
	}

	public function createCsvExport(
		Datagrid $grid,
		callable $onProcess,
		string $actionLink,
		?array $ids = null
	): AdminForm {
		$ids = $ids ?: [];
		$totalNo = $grid->getPaginator()->getItemCount();
		$selectedNo = \count($ids);

		$form = $this->create();
		$form->setAction($actionLink);
		$form->addRadioList('bulkType', 'Exportovat', [
			'selected' => "vybrané ($selectedNo)",
			'all' => "celý výsledek ($totalNo)",
		])->setDefaultValue('selected');

		$form->addSelect('delimiter', 'Oddělovač', [
			';' => 'Středník (;)',
			',' => 'Čárka (,)',
			'   ' => 'Tab (\t)',
			' ' => 'Mezera ( )',
			'|' => 'Pipe (|)',
		]);

		$form->addSubmit('submit', 'Exportovat');

		$form->onSuccess[] = function (AdminForm $form) use ($onProcess): void {
			$values = $form->getValues('array');

			$onProcess($values);
		};

		return $form;
	}

	public function createBulkActionForm(
		Datagrid $grid,
		callable $onProcess,
		string $actionLink,
		Collection $collection,
		?array $ids = null,
		?callable $onFormCreation = null,
		?callable $onFormCreationBeforeSubmit = null
	): AdminForm {
		$ids = $ids ?: [];
		$totalNo = $grid->getPaginator()->getItemCount();
		$selectedNo = \count($ids);

		$form = $this->create();
		$form->setAction($actionLink);
		$form->addRadioList('bulkType', 'Výběr položek', [
			'selected' => "vybrané ($selectedNo)",
			'all' => "celý výsledek ($totalNo)",
		])->setDefaultValue('selected');

		if ($onFormCreationBeforeSubmit) {
			$onFormCreationBeforeSubmit($form);
		}

		$form->addSubmit('submit', 'Provést');

		if ($onFormCreation) {
			$onFormCreation($form);
		}

		$form->onSuccess[] = function (AdminForm $form) use ($onProcess, $grid, $ids, $collection): void {
			$values = $form->getValues('array');

			$collection = $values['bulkType'] === 'selected' ? $collection->where('this.uuid', $ids) : $grid->getFilteredSource();

			$onProcess($values, $collection, $form);
		};

		return $form;
	}
}
