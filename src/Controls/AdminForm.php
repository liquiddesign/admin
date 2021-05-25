<?php

declare(strict_types=1);

namespace Admin\Controls;

use Nette\Localization\Translator;
use Pages\DB\IPage;
use Web\DB\PageRepository;
use Forms\Container;
use Forms\LocaleContainer;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\TextArea;
use Nette\Forms\Controls\TextBase;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use StORM\DIConnection;
use StORM\Meta\Structure;

class AdminForm extends \Forms\Form
{

	private PageRepository $pageRepository;

	private \StORM\DIConnection $storm;

	private Translator $translator;

	private bool $prettyPages = false;

	public function setPageRepository(PageRepository $pageRepository)
	{
		$this->pageRepository = $pageRepository;
	}

	public function setConnection(DIConnection $storm)
	{
		$this->storm = $storm;
	}

	public function setAdminFormTranslator(Translator $translator)
	{
		$this->translator = $translator;
	}

	public function setPrettyPages(bool $prettyPages)
	{
		$this->prettyPages = $prettyPages;
	}

	public function getPrettyPages(): bool
	{
		return $this->prettyPages;
	}

	public function addSubmits(bool $stayPut = false, bool $continue = true)
	{
		$this->addGroup();

		$this->addSubmit('submit', $this->translator->translate('admin.save', 'Uložit'));

		if ($continue) {
			$this->addSubmit('submitAndContinue', $this->translator->translate('admin.saveAndContinue', 'Uložit a pokračovat'));
		}

		if ($stayPut) {
			$this->addSubmit('submitAndNext', $this->translator->translate('admin.saveAndNext', 'Uložit a vložit další'));
		}
	}

	public function syncPages(callable $callback)
	{
		if ($this->prettyPages) {
			$callback();
		}
	}

	public function processRedirect(
		string $detailLink,
		?string $backLink = null,
		array $detailArguments = [],
		array $backLinkArguments = [],
		array $continueArguments = []
	) {
		/** @var \Nette\Forms\Controls\Button $submitter */
		$submitter = $this->isSubmitted();
		$backLink = $backLink ?: $detailLink;

		if ($submitter->getName() === 'submit') {
			if ($this->getPresenter()->getParameter('backLink')) {
				$this->getPresenter()->restoreRequest($this->getPresenter()->getParameter('backLink'));
			}

			$this->getPresenter()->redirect($backLink, $backLinkArguments);
		} elseif ($submitter->getName() === 'submitAndContinue') {
			$this->getPresenter()->redirect($detailLink, $detailArguments);
		} elseif ($submitter->getName() === 'submitAndNext') {
			$this->getPresenter()->redirect('this', $continueArguments);
		}
	}

	public static function validateUrl(\Nette\Forms\Controls\TextInput $input, array $args): bool
	{
		[$repository, $mutation, $uuid] = $args;

		return (bool )$repository->isUrlAvailable((string)$input->getValue(), $mutation, $uuid);
	}

	public function addPageContainer(
		?string $pageType = null,
		array $params = [],
		?LocaleContainer $copyControls = null
	): Container {
		if (!$this->prettyPages) {
			return $this->addContainer('page');
		}

		/** @var \Web\DB\Page|null $page */
		$page = $pageType ? $this->pageRepository->getPageByTypeAndParams($pageType, null, $params, true) : null;
		
		/** @var Container $pageContainer */
		$pageContainer = $this->getComponent('page', false) ?: $this->addContainer('page');
		
		$group = $this->addGroup('URL a SEO', true);
		$pageContainer->setCurrentGroup($group);
		
		$pageContainer->addHidden('uuid')->setNullable();
		$pageContainer->addLocaleText('url', 'URL')->forAll(function (TextInput $text, $mutation) use ($page, $pageType) {
			$text->setHtmlAttribute('class', 'seo_url')
				->addRule([$this, 'validateUrl'], 'URL již existuje',
					[$this->pageRepository, $mutation, $page ? $page->getPK() : null])->setNullable($pageType !== 'index');
		})->forPrimary(function (TextInput $text, $mutation) use ($pageType) {
			if ($pageType === 'index') {
				$text->setRequired(false);
				$text->setHtmlAttribute('readonly', 'readonly');
			} else {
				$text->setRequired(true);
			}
		});
		
		if (!isset($pageContainer['isOffline'])) {
			$pageContainer->addCheckbox('isOffline', 'Nedostupná')->setHtmlAttribute('data-info',
				'Na daném URL bude stránka jako stránka 404');
		}

		$pageContainer->addLocaleText('title', 'Titulek')->forAll(function (TextInput $text) {
			$text->setHtmlAttribute('data-characters', 70);
		});

		$pageContainer->addLocaleTextArea('description', 'Popisek')->forAll(function (TextArea $text) {
			$text->setHtmlAttribute('style', 'width: 862px !important;')
				->setHtmlAttribute('data-characters', 150);
		});
		$pageContainer->addHidden('type', $pageType);
		$pageContainer->addHidden('params', $params ? \http_build_query($params) . '&' : '');

		if ($page) {
			$pageContainer->setDefaults($page->toArray());
		}

		if ($copyControls) {
			$copyControls->forAll(function (TextInput $text) {
				$text->setHtmlAttribute('data-copy', 'page[title],page[url]');
			});
		}

		return $pageContainer;
	}

	public function addIntegerNullable(string $name, $label = null): TextInput
	{
		$element = (new TextInput($label))
			->setNullable();
		$element->addCondition(Form::FILLED)
			->addRule(Form::INTEGER);
		$element->setHtmlType('number');

		return $this[$name] = $element;
	}

	public function bind(
		?Structure $mainStructure,
		array $containerStructures = [],
		bool $setDefaultValues = true
	): void {
		foreach ($this->getComponents(true, BaseControl::class) as $control) {
			$structure = $mainStructure;
			$name = $control->getName();

			if ($control->getParent() instanceof LocaleContainer) {
				$name = $control->getParent()->getName();
			} elseif ($control->getParent() instanceof Container) {
				if (!isset($containerStructures[$control->getParent()->getName()])) {
					continue;
				}

				$structure = $containerStructures[$control->getParent()->getName()];
			}

			if (!$structure || !$structure->getColumn($name)) {
				continue;
			}

			if ($control instanceof TextBase) {
				$control->setNullable($structure->getColumn($name)->isNullable());
			}

			if ($setDefaultValues) {
				$defaultValue = (new \ReflectionClass($structure->getColumn($name)->getEntityClass()))->getDefaultProperties()[$structure->getColumn($name)->getPropertyName()] ?? null;
				$control->setDefaultValue($structure->getColumn($name)->getDefault() ?? $defaultValue);
			}
		}
	}
}