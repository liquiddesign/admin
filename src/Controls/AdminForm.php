<?php

declare(strict_types=1);

namespace Admin\Controls;

use Admin\Administrator;
use Forms\Container;
use Forms\LocaleContainer;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\TextArea;
use Nette\Forms\Controls\TextBase;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use Nette\Http\FileUpload;
use Nette\Localization\Translator;
use Nette\NotImplementedException;
use Nette\Utils\Image;
use Pages\DB\IPageRepository;
use Pages\DB\Page;
use StORM\DIConnection;
use StORM\Entity;
use StORM\Meta\Structure;

class AdminForm extends \Forms\Form
{
	/**
	 * Occurs when the form was validated
	 * @var array<callable(self, array|object): void|callable(array|object): void>
	 */
	public $onValidate = [];

	public ?string $entityName = null;

	/** @var array<array<string>> */
	public array $ajaxInputs = [];

	private IPageRepository $pageRepository;

	private \StORM\DIConnection $storm;

	private Translator $translator;

	private Administrator $administrator;

	private bool $prettyPages = false;

	public function renderNoHeader(): void
	{
		$groups = $this->getGroups();
		\reset($groups)->setOption('label', null);

		$this->render();
	}
	
	/**
	 * @param string|null $containerName
	 * @return array<mixed>|null
	 */
	public function getChangedProperties(?string $containerName = null): ?array
	{
		if (!isset($this['_defaults']) || !$this['_defaults'] instanceof BaseControl) {
			throw new NotImplementedException('"_defaults" input is not set');
		}

		if (!$this['_defaults']->getValue()) {
			return null;
		}

		$entityValues = \json_decode($this['_defaults']->getValue(), true);

		if ($containerName) {
			$entityValues = $entityValues[$containerName];
		}

		if (!\is_array($entityValues)) {
			return null;
		}

		$properties = [];

		/** @var \Forms\Container $container */
		$container = $containerName ? $this[$containerName] : $this;

		$values = $container->getValues('array');

		foreach ($values as $name => $value) {
			/* @phpstan-ignore-next-line */
			if ($container[$name] instanceof Container && !$container[$name] instanceof LocaleContainer) {
				continue;
			}

			if (\is_scalar($value)) {
				if ($value !== $entityValues[$name]) {
					$properties[$name] = $name;
				}
			}

			if (\is_array($value) && isset($entityValues[$name]) && $diff = \array_merge(\array_diff($value, $entityValues[$name]), \array_diff($entityValues[$name], $value))) {
				if ($container[$name] instanceof LocaleContainer) {
					foreach (\array_keys($diff) as $mutation) {
						$properties[$name . $this->storm->getAvailableMutations()[$mutation]] = $name;
					}
				} else {
					$properties[$name] = $name;
				}
			}

			if (!($value instanceof FileUpload) || !$value->hasFile()) {
				continue;
			}

			$properties[$name] = $name;
		}

		return $properties;
	}

	public function setAdministrator(Administrator $administrator): void
	{
		$this->administrator = $administrator;
	}

	public function setPageRepository(IPageRepository $pageRepository): void
	{
		$this->pageRepository = $pageRepository;
	}

	public function setConnection(DIConnection $storm): void
	{
		$this->storm = $storm;
	}

	public function setAdminFormTranslator(Translator $translator): void
	{
		$this->translator = $translator;
	}

	public function setPrettyPages(bool $prettyPages): void
	{
		$this->prettyPages = $prettyPages;
	}

	public function setLogging(string $entityName): void
	{
		$this->entityName = $entityName;
	}

	public function getPrettyPages(): bool
	{
		return $this->prettyPages;
	}

	public function addSubmits(bool $stayPut = false, bool $continue = true): void
	{
		$this->addGroup();
		$this->addSubmit('submit', $this->translator->translate('admin.save', 'Uložit'));

		if ($continue) {
			$this->addSubmit('submitAndContinue', $this->translator->translate('admin.saveAndContinue', 'Uložit a pokračovat'));
		}

		if (!$stayPut) {
			return;
		}

		$this->addSubmit('submitAndNext', $this->translator->translate('admin.saveAndNext', 'Uložit a vložit další'));
	}

	public function syncPages(callable $callback): void
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
	): void {
		/** @var \Nette\Forms\Controls\Button $submitter */
		$submitter = $this->isSubmitted();
		$backLink = $backLink ?: $detailLink;

		if ($submitter->getName() === 'submit') {
			if ($this->getPresenter()->getParameter('backLink')) {
				$this->getPresenter()->restoreRequest($this->getPresenter()->getParameter('backLink'));
			}

			$this->getPresenter()->redirect($backLink, $backLinkArguments);
		} elseif ($submitter->getName() === 'submitAndContinue') {
			if ($this->getComponent(\Forms\Form::MUTATION_SELECTOR_NAME, false) instanceof BaseControl) {
				$selectedLang = $this->getComponent(\Forms\Form::MUTATION_SELECTOR_NAME, false)->getValue();
				$detailArguments['selectedLang'] = $selectedLang;
			}

			$this->getPresenter()->redirect($detailLink, $detailArguments);
		} elseif ($submitter->getName() === 'submitAndNext') {
			$this->getPresenter()->redirect('this', $continueArguments);
		}
	}

	public function addPageContainer(
		?string $pageType = null,
		array $params = [],
		?LocaleContainer $copyControls = null,
		bool $isOffline = false,
		bool $required = true,
		bool $content = false,
		string $title = 'URL a SEO',
		bool $opengraph = false,
		bool $linkToDetail = false,
		bool $richSnippet = false
	): Container {
		if (!$this->prettyPages) {
			return $this->addContainer('page');
		}

		/** @var \Pages\DB\Page|null $page */
		$page = $pageType ? $this->pageRepository->getPageByTypeAndParams($pageType, null, $params, true) : null;

		/** @var \Forms\Container $pageContainer */
		$pageContainer = $this->getComponent('page', false) ?: $this->addContainer('page');

		$group = $this->addGroup($title, true);
		$pageContainer->setCurrentGroup($group);

		$pageContainer->addHidden('uuid')->setNullable();
		$pageContainer->addLocaleText('url', 'URL')->forAll(function (TextInput $text, $mutation) use ($page, $pageType): void {
			$text->addRule(
				[$this, 'validateUrl'],
				$this->translator->translate('admin.urlError', 'URL již existuje'),
				[$this->pageRepository, $mutation, $page ? $page->getPK() : null],
			)->setNullable($pageType !== 'index');

			if ($pageType === 'index') {
				$text->setRequired(false);
				$text->setHtmlAttribute('readonly', 'readonly');
			}

			if ($this->administrator->getIdentity() instanceof \Admin\DB\Administrator && !$this->administrator->getIdentity()->urlEditor && $page) {
				$text->setHtmlAttribute('readonly', 'readonly');
			}

			$text->setHtmlAttribute('data-copy-url-targets', 'page[url]');
			$text->setHtmlAttribute('data-copy-url-source', 'name');
			$text->setHtmlAttribute('class', 'd-inline seo_url');
		})->forAll(function (TextInput $text, $mutation) use ($linkToDetail, $page, $pageType, $required): void {
			if (isset($this[self::MUTATION_TRANSLATOR_NAME]) && $pageType !== 'index' && $required) {
				$text->addConditionOn($this[self::MUTATION_TRANSLATOR_NAME][$mutation], $this::EQUAL, true);
			}
			
			if (!isset($this[self::MUTATION_TRANSLATOR_NAME]) && $pageType !== 'index' && $required && $mutation === $this->getPrimaryMutation()) {
				$text->setRequired(true);
			}
			
			$this->monitor(Presenter::class, function (Presenter $presenter) use ($linkToDetail, $page, $text, $mutation): void {
				if ($linkToDetail && $page instanceof Entity && $page->getValue('url', $mutation)) {
					$mutatedUrl = $page->getValue('url', $mutation);
					$url = $presenter->getHttpRequest()->getUrl()->getBaseUrl() . ($mutation === $this->getPrimaryMutation() ? $mutatedUrl : "$mutation/" . $mutatedUrl);
				
					$text->setHtmlAttribute("data-url-link-$mutation", "
						<a data-mutation='$mutation' class='ml-2' href='" . $url . "' target='_blank'>
						<i class='fas fa-external-link-alt'></i> " . $this->translator->translate('admin.showPage', 'Zobrazit stránku') . '
						</a>
					');
				}
			});
		});

		if ($isOffline) {
			$pageContainer->addCheckbox('isOffline', $this->translator->translate('admin.isOffline', 'Nedostupná'))
				->setHtmlAttribute('data-info', $this->translator->translate('admin.isOfflineDescription', 'Na daném URL bude stránka jako stránka 404'));
		}

		$pageContainer->addLocaleText('title', $this->translator->translate('admin.title', 'Titulek'))
			->forAll(function (TextInput $text): void {
				$text->setHtmlAttribute('data-characters', 70)
					->setHtmlAttribute('style', 'width: 450px !important');
			});

		$pageContainer->addLocaleTextArea('description', $this->translator->translate('admin.description', 'Popisek'))
			->forAll(function (TextArea $text): void {
				$text->setHtmlAttribute('style', 'width: 862px !important;')
					->setHtmlAttribute('data-characters', 150);
			});

		if ($opengraph) {
			$opengraphImage = $pageContainer->addImagePicker('opengraph', $this->translator->translate('admin.image', 'Obrázek'), [
				Page::IMAGE_DIR . '/opengraph' => static function (Image $image): void {
					$image->resize(1200, 628, Image::EXACT);
				}]);

			$opengraphImage->setOption('description', $this->translator->translate('admin.imageSizeInfo', 'Obrázek vkládejte o minimální velikosti %dx%d px', [1200, 628]));

			$opengraphImage->onDelete[] = function () use ($page): void {
				if ($page) {
					$page->update(['opengraph' => null]);
					$this->getPresenter()->redirect('this');
				}
			};
		}

		if ($content) {
			$pageContainer->addLocaleTextArea('content', $this->translator->translate('admin.content', 'Obsah'))
				->forAll(function (TextArea $text): void {
					$text->setHtmlAttribute('style', 'width: 862px !important;');
				});
		}

		if ($richSnippet) {
			$pageContainer->addLocaleTextArea('richSnippet', $this->translator->translate('admin.richSnippet', 'Rich snippet'))
				->forAll(function (TextArea $text): void {
					$text->setHtmlAttribute('style', 'width: 862px !important; min-height: 300px !important;');

					$text->setHtmlAttribute('data-info', "<a href='https://search.google.com/test/rich-results' target='_blank'><i class='fas fa-external-link-alt'></i> &nbsp;" .
						$this->translator->translate('admin.richSnippetValidator', 'Validátor') . '</a>');
				});
		}

		$pageContainer->addHidden('type', $pageType);
		$pageContainer->addHidden('params', $params ? \http_build_query($params) . '&' : '');

		if ($page) {
			$pageContainer->setDefaults($page->toArray());
		}

		if ($copyControls) {
			$copyControls->forAll(function (TextInput $text): void {
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
		/** @var \Nette\Forms\Controls\BaseControl $control */
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

			if (!$setDefaultValues) {
				continue;
			}

			$defaultValue = (new \ReflectionClass($structure->getColumn($name)->getEntityClass()))->getDefaultProperties()[$structure->getColumn($name)->getPropertyName()] ?? null;
			$control->setDefaultValue($structure->getColumn($name)->getDefault() ?? $defaultValue);
		}
	}
	
	/**
	 * @param array<mixed>|object $data
	 * @param bool $erase
	 * @return static
	 */
	public function setDefaults($data, bool $erase = false): static
	{
		if (isset($this['_defaults']) && $this['_defaults'] instanceof BaseControl) {
			$this['_defaults']->setDefaultValue(\json_encode($data));
		}

		return parent::setDefaults($data, $erase);
	}
	
	/**
	 * @return array<string>
	 */
	public function getTranslatedMutations(): array
	{
		$mut = [];

		foreach ($this->mutations as $m) {
			$mut[$m] = $this->translator->translate('admin.' . $m, $m);
		}

		return $mut;
	}

	/**
	 * @param mixed $name
	 * @param string|null $label
	 * @param string|null $placeholder
	 * @param string|null $className Class name of entity to get items
	 * @param array|null $configuration
	 * @throws \Nette\Application\UI\InvalidLinkException
	 * @throws \Exception
	 */
	public function addSelectAjax(
		$name,
		?string $label = null,
		?string $placeholder = null,
		?string $className = null,
		?array $configuration = []
	): SelectBox {
		if (!$className) {
			throw new \Exception('Missing DataSource');
		}

		$this->ajaxInputs[$this->getName()][] = $name;

		/** @var \Admin\BackendPresenter|null $presenter */
		$presenter = $this->getPresenterIfExists();

		if (!$presenter) {
			throw new \Exception('Missing Presenter');
		}

		$presenter->ajaxInputs[$this->getName()][] = $name;

		$link = $presenter->link('getAjaxArrayForSelect!', ['name' => $className,]);

		return $this->addSelect2Ajax($name, $link, $label, $configuration, $placeholder);
	}

	/**
	 * @return array<mixed>
	 */
	public function getValuesWithAjax(): array
	{
		$values = (array) $this->getValues();
		$data = $this->getHttpData();

		/**
		 * @var string $key
		 * @var array<mixed>|string $inputName
		 */
		foreach ($this->ajaxInputs[$this->getName()] as $key => $inputName) {
			if (\is_array($inputName)) {
				$this->getValuesWithAjaxItem($values, $data[$key], $inputName);
			} elseif (isset($data[$inputName])) {
				$values[$inputName] = $data[$inputName];
			}
		}

		return $values;
	}

	public static function validateUrl(\Nette\Forms\Controls\TextInput $input, array $args): bool
	{
		[$repository, $mutation, $uuid] = $args;

		return (bool ) $repository->isUrlAvailable((string) $input->getValue(), $mutation, $uuid);
	}

	private function getValuesWithAjaxItem(&$values, $data, $inputName): void
	{
		if (\is_array($inputName)) {
			/**
			 * @var string $key
			 * @var array<mixed>|string $inputName
			 */
			foreach ($this->ajaxInputs[$this->getName()] ?? [] as $key => $inputName) {
				if (\is_array($inputName)) {
					$this->getValuesWithAjaxItem($values, $data[$key], $inputName);
				} else {
					$values[$inputName] = $data[$inputName];
				}
			}
		} else {
			$values[$inputName] = $data[$inputName];
		}
	}
}
