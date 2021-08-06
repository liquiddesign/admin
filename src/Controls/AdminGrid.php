<?php

declare(strict_types=1);

namespace Admin\Controls;

use Admin\DB\ChangelogRepository;
use Forms\Form;
use Grid\Column;
use Grid\Datalist;
use Nette\Application\ApplicationException;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\TextInput;
use Nette\Http\Session;
use Nette\Localization\Translator;
use Nette\Utils\Html;
use StORM\Collection;
use StORM\Entity;
use StORM\ICollection;
use StORM\Meta\RelationNxN;
use StORM\RelationCollection;
use Common\NumbersHelper;

/**
 * @method onDelete(Entity $object)
 */
class AdminGrid extends \Grid\Datagrid
{
	/**
	 * @var callable[]
	 */
	public array $onDelete = [];

	public Translator $translator;

	private ChangelogRepository $changelogRepository;

	private ?string $bulkFormId = null;

	private ?array $bulkFormInputs = null;

	private ?string $bulkFormDefaultLink = null;

	private AdminFormFactory $formFactory;

	private ?Session $session;

	private array $itemsPerPage;

	private bool $showItemsPerPage;

	private bool $showPaginator = true;

	private string $appendClass = '';

	public ?string $entityName = null;

	public function __construct(ICollection $source, ?int $defaultOnPage = null, ?string $defaultOrderExpression = null, ?string $defaultOrderDir = null, bool $encodeId = false, ?Session $session = null)
	{
		parent::__construct($source, $defaultOnPage, $defaultOrderExpression, $defaultOrderDir, $encodeId);

		$this->onRender[] = function (\Nette\Utils\Html $tbody, array $columns): void {
			if (\count($tbody) === 0) {
				$tNoResult = $this->translator->translate('admin.gridNoResult', 'Žádný výsledek. Zkuste změnit nebo vymazat filtry.');
				$tbody->addHtml((\Nette\Utils\Html::el('tr')->addHtml(\Nette\Utils\Html::el('td', ['colspan' => \count($columns)])->setHtml('<i>' . $tNoResult . '</i>')->class('text-center p-2'))));
			}
		};

		$this->onLoadState[] = function (Datalist $datalist, $params) use ($session): void {
			Datalist::loadSession($datalist, $params, $session->getSection('admingrid-' . $datalist->getPresenter()->getName() . $datalist->getName()));
		};

		$this->onSaveState[] = function (Datalist $datalist, $params) use ($session): void {
			Datalist::saveSession($datalist, $params, $session->getSection('admingrid-' . $datalist->getPresenter()->getName() . $datalist->getName()));
		};

		$this->onAnchor[] = function (AdminGrid $grid) use ($defaultOnPage): void {
			$grid->template->setFile(__DIR__ . '/adminGrid.latte');
			$grid->template->paginator = $grid->getPaginator(true);
			$grid->template->onpage = $grid->getName() . '-onpage';
			$grid->template->page = $grid->getName() . '-page';
			$grid->template->showItemsPerPage = $this->showItemsPerPage;
			$grid->template->itemsPerPage = $this->itemsPerPage;
			$grid->template->showPaginator = $this->showPaginator;
			$grid->template->appendClass = $this->appendClass;
			$grid->template->itemCountMessage = $this->translator->translate('admin.itemCountMessage', 'Položek');

			if (!$this->showItemsPerPage) {
				if ($defaultOnPage) {
					$grid->setOnPage($defaultOnPage);
				}

				$grid->setOnPage($grid->getDefaultOnPage());
			}

			[$orderColumn, $orderDirection] = \explode('-', $this->getOrderParameter());

			foreach ($this->columns as $column) {
				$column->onRender[] = function (\Nette\Utils\Html $th) use ($orderColumn, $orderDirection, $column): void {
					if ($column->getOrderExpression() && $orderColumn == $column->getOrderExpression()) {
						$th->setHtml('<i class="mr-1 ' . (\strpos($orderColumn, 'DESC') !== false ? 'fa fa-sort-amount-down' : ($orderDirection == 'ASC' ? 'fa fa-sort-amount-up-alt' : 'fa fa-sort-amount-down')) . '"></i>' . $th->getHtml());
					}
				};

				$column->onRenderCell[] = function (Html $td, $object) {
					if (\strpos($td[0], 'btn-sm') !== false) {
						$td->class('fit');
					}
				};
			}
		};

		$this->session = $session;
	}

	public function setChangelogRepository(ChangelogRepository $changelogRepository): void
	{
		$this->changelogRepository = $changelogRepository;
	}

	public function setItemsPerPage(array $items): void
	{
		$this->itemsPerPage = $items;
	}

	public function setShowItemsPerPage(bool $show): void
	{
		$this->showItemsPerPage = $show;
	}

	public function showPaginator(bool $show): void
	{
		$this->showPaginator = $show;
	}

	public function addGridClass(string $classes): void
	{
		$this->appendClass = $classes;
	}

	public function setTranslator(Translator $translator): void
	{
		$this->translator = $translator;
	}

	public function setLogging(string $entityName): void
	{
		$this->entityName = $entityName;
	}

	public function setFormsFactory(AdminFormFactory $formFactory): void
	{
		$this->formFactory = $formFactory;
	}

	/**
	 * @deprecated
	 */
	public function addColumnSelectorMinimal(array $wrapperAttributes = []): Column
	{
		return $this->addColumnSelector($wrapperAttributes);
	}

	protected function createComponentFilterForm(): \Nette\Application\UI\Form
	{
		$form = $this->formFactory->create();
		$this->makeFilterForm($form);

		return $form;
	}

	public function addColumnSelector(array $wrapperAttributes = []): Column
	{
		return parent::addColumnSelector($wrapperAttributes + ['class' => 'fit']);
	}

	public function addColumnTextFit($th, $expressions, $td, ?string $orderExpression = null, array $wrapperAttributes = []): Column
	{
		$column = parent::addColumnText($th, $expressions, $td, $orderExpression, $wrapperAttributes + ['class' => 'fit']);
		$column->onRenderCell[] = [$this, 'decoratorNowrap'];

		return $column;
	}

	public function addColumnImage(string $expression, string $dir, string $subDir = 'thumb', string $th = '')
	{
		return $this->addColumn($th, function ($entity) use ($dir, $expression, $subDir) {
			$baseUrl = $this->getPresenter()->getHttpRequest()->getUrl()->getBaseUrl();

			foreach (\explode('.', $expression) as $property) {
				$entity = $entity->$property;
			}

			if ($entity === null) {
				$path = $baseUrl . '/public/admin/img/no-image-icon.png?t=' . \time();

				return "<img src='$path' style='height:32px;'>";
			}

			$subDir = $subDir ? $subDir . '/' : '';

			$path = $baseUrl . '/userfiles/' . $dir . '/' . $subDir . $entity . '?t=' . \time();

			return "<img src='$path' style='height:32px;'>";
		}, '%s', null, ['class' => 'fit']);
	}

	public function addColumnInputText($th, string $name, $setValueExpression = '', $defaultValue = '', ?string $orderExpression = null, array $wrapperAttributes = [], bool $required = false)
	{
		$this->addColumnInput($th, $name, function () use ($required) {
			$textbox = new TextInput();
			$textbox->setHtmlAttribute('class', 'form-control form-control-sm');
			if ($required) {
				$textbox->setRequired();
			}

			return $textbox;
		}, $setValueExpression, $defaultValue, $orderExpression, ['class' => 'minimal'] + $wrapperAttributes);
	}

	public function addColumnInputInteger($th, string $name, $setValueExpression = '', $defaultValue = '', ?string $orderExpression = null, array $wrapperAttributes = [], bool $required = false)
	{
		$this->addColumnInput($th, $name, function () use ($required) {
			$textbox = new TextInput();
			$textbox->setHtmlAttribute('class', 'form-control form-control-sm');
			$textbox->addRule(Form::INTEGER);
			if ($required) {
				$textbox->setRequired();
			}

			return $textbox;
		}, $setValueExpression, $defaultValue, $orderExpression, ['class' => 'minimal'] + $wrapperAttributes);
	}

	public function addColumnInputFloat($th, string $name, $setValueExpression = '', $defaultValue = '', ?string $orderExpression = null, array $wrapperAttributes = ['class' => 'minimal'], bool $required = false)
	{
		$this->addColumnInput($th, $name, function () use ($required) {
			$textbox = new TextInput();
			$textbox->setHtmlAttribute('class', 'form-control form-control-sm');
			$textbox->setNullable(true);
			$textbox->addCondition(Form::FILLED)->addRule(Form::FLOAT);

			if ($required) {
				$textbox->setRequired();
			}

			return $textbox;
		}, $setValueExpression, $defaultValue, $orderExpression, $wrapperAttributes);
	}

	public function addColumnInputPrice($th, string $name, string $rateProperty = 'rate'): Column
	{
		$grid = $this;

		return $this->addColumnInput($th, $name, function ($id) use ($grid, $name, $rateProperty) {
			$textbox = new TextInput();
			$textbox->setHtmlAttribute('class', 'form-control form-control-sm');

			if ($id) {
				$vat = \strpos($name, 'Vat') !== false;
				$textbox->setHtmlAttribute('data-vat', !$vat ? '0' : '1');
				$rate = (float)\str_replace(',', '.', $grid->getItemsOnPage()[$id]->$rateProperty);

				$textbox->setHtmlAttribute('data-vatMultiplier', !$vat ? 100 / (100 + $rate) : (100 + $rate) / 100);
			}

			$textbox->setNullable(true);
			$textbox->addCondition(Form::FILLED)->addRule(Form::FLOAT);

			return $textbox;
		}, '', '', null, []);
	}

	public function addColumnInputCheckbox($th, string $name, $setValueExpression = '', $defaultValue = '', ?string $orderExpression = null, array $wrapperAttributes = []): Column
	{
		return $this->addColumnInput($th, $name, function () {
			$checkbox = new Checkbox();
			$checkbox->setHtmlAttribute('class', 'form-check form-control-sm');

			return $checkbox;
		}, $setValueExpression, $defaultValue, $orderExpression, ['class' => 'minimal'] + $wrapperAttributes);
	}

	public function addColumnInputSelect($th, string $name, $setValueExpression = '', $defaultValue = '', ?string $orderExpression = null, array $wrapperAttributes = [], $items = null)
	{
		$this->addColumnInput($th, $name, function () use ($items) {
			$selectBox = new SelectBox(null, $items);
			$selectBox->setHtmlAttribute('class', 'form-control form-control-sm');

			return $selectBox;
		}, $setValueExpression, $defaultValue, $orderExpression, $wrapperAttributes);
	}

	public function addColumnPriority()
	{
		return $this->addColumnInputInteger($this->translator->translate('admin.priority', 'Pořadí'), 'priority', '', '', 'priority', [], true);
	}

	public function addColumnHidden(): Column
	{
		return $this->addColumnInputCheckbox('<i title="' . $this->translator->translate('admin.hidden', 'Skryto') . '" class="far fa-eye-slash"></i>', 'hidden', '', '', 'hidden');
	}

	public function addColumnLinkDetail(string $destination = 'detail', array $arguments = []): Column
	{
		return $this->addColumn('', function ($object, $datagrid) use ($destination, $arguments) {
			return $datagrid->getPresenter()->link($destination, [$object instanceof Entity ? $object : \call_user_func($this->idCallback, $object)] + $arguments);
		}, '<a class="btn btn-primary btn-sm text-xs" href="%s" title="' . $this->translator->translate('admin.edit', 'Upravit') . '"><i class="far fa-edit"></i></a>', null, ['class' => 'minimal']);
	}

	public function addColumnLink(string $destination, string $label = '', ?string $th = null, $wrappers = ['class' => 'minimal']): Column
	{
		return $this->addColumn($th, function ($object, $datagrid) use ($destination, $label) {
			return $datagrid->getPresenter()->link($destination, $object instanceof Entity ? $object : \call_user_func($this->idCallback, $object));
		}, '<a class="btn btn-outline-primary btn-sm text-xs" style="white-space: nowrap" href="%s">' . $label . '</a>', null, $wrappers);
	}

	public function addColumnMutations(string $property, bool $nullable = true)
	{
		$this->addColumn($this->translator->translate('admin.mutations', 'Mutace'), function (Entity $object) use ($property, $nullable) {
			$img = [];
			$baseUrl = $this->getPresenter()->getHttpRequest()->getUrl()->getBaseUrl();

			foreach ($this->formFactory->formFactory->getDefaultMutations() as $mutation) {
				[$flagsPath, $flagsExt, $flagsMap] = $this->formFactory->formFactory->getDefaultFlagsConfiguration();

				if ($nullable) {
					$style = $object->getValue($property, $mutation) === null ? 'filter: grayscale(100%);' : '';
				} else {
					$style = !$object->getValue($property, $mutation) ? 'filter: grayscale(100%);' : '';
				}

				$img[] = "<img class='mutation-flag' style='$style' src='$baseUrl$flagsPath/$flagsMap[$mutation].$flagsExt' alt='$mutation' title='$mutation'>";
			}

			return \implode('&nbsp;', $img);
		}, '%s', null, ['class' => 'fit']);
	}

	/**
	 * @param callable|null $beforeDeleteCallback Callback that will be called before object removal. If callback throw exception, it will catch it and object will not be deleted.
	 * @param bool $override If true, object will not be automatically delete. You can delete it manually in callback.
	 * @param callable|null $condition Condition callback, must return boolean
	 * @return \Grid\Column
	 */
	public function addColumnActionDelete(?callable $beforeDeleteCallback = null, bool $override = false, ?callable $condition = null)
	{
		return $this->addColumnAction('', "<a href=\"%s\" class='btn btn-danger btn-sm text-xs' title='" . $this->translator->translate('admin.remove', 'Smazat') . "' onclick=\"return confirm('" . $this->translator->translate('admin.really', 'Opravdu?') . "')\"><i class='far fa-trash-alt'></i></a>",
			function ($object) use ($beforeDeleteCallback, $override, $condition): void {
				try {
					$this->getPresenter()->flashMessage($this->translator->translate('admin.done', 'Provedeno'), 'success');

					if ($condition) {
						$allowed = $condition($object);
					}

					if (!$condition || (isset($allowed) && $allowed == true)) {
						if ($beforeDeleteCallback) {
							$beforeDeleteCallback($object);
						}

						if (isset($this->onDelete)) {
							$this->onDelete($object);
						}

						$this->onDeleteRow($object);

						if (!$override) {
							$this->getSource()->where($this->getSource(false)->getPrefix() . $this->getSourceIdName(), \call_user_func($this->idCallback, $object))->setOrderBy([])->delete();
						}
					} else {
						$this->getPresenter()->flashMessage($this->translator->translate('admin.unableToRemoveItem', 'Chyba: Je zákázáno tuto položku smazat!'), 'error');
						$this->getPresenter()->redirect('this');
					}
				} catch (\Exception $e) {
					$this->getPresenter()->flashMessage($this->translator->translate('admin.unableToRemove', 'Chyba: Nelze smazat.'), 'error');
				}
				$this->getPresenter()->redirect('this');
			}, [], null, ['class' => 'minimal']);
	}

	public function addColumnActionDeleteSystemic(?callable $beforeDeleteCallback = null, bool $override = false)
	{
		$column = $this->addColumnActionDelete($beforeDeleteCallback, $override, function (Entity $object) {
			return !$object->isSystemic();
		});

		$column->onRenderCell[] = function (\Nette\Utils\Html $td, Entity $object) {
			if ($object->isSystemic()) {
				$td[0] = "<button type='button' class='btn btn-sm btn-danger disabled' title='Systémová stránka'><i class='far fa-trash-alt'></i></button>";
			}
		};

		return $column;
	}

	/**
	 * @param array $processNullColumns Simple array with names of columns to be nulled if empty.
	 * @param array $processTypes Asociative array where key is name of column and value desired type.
	 * @param string|null $sourceIdName Name of source primary key
	 * @param bool $ignore add ignore to update expression
	 * @param callable|null $onProcessType Callback called for every column in $processTypes to do custom processing
	 * @param callable|null $onRowUpdate Called on every row update with new data
	 * @param bool $diff Check diff on every row
	 */
	public function addButtonSaveAll(array $processNullColumns = [], array $processTypes = [], ?string $sourceIdName = null, bool $ignore = false, ?callable $onProcessType = null, ?callable $onRowUpdate = null, bool $diff = true)
	{
		$grid = $this;
		$defaults = $this->getForm()->addHidden('_defaults')->setNullable(true)->setOmitted(true);

		$submit = $this->getForm()->addSubmit('submit', $this->translator->translate('admin.save', 'Uložit'));
		$submit->setHtmlAttribute('class', 'btn btn-sm btn-primary');

		$grid->onRender[] = function () use ($defaults, $grid) {
			$defaults->setDefaultValue(\json_encode($grid->inputsValues));
		};

		$submit->onClick[] = function ($button) use ($grid, $defaults, $processNullColumns, $processTypes, $sourceIdName, $ignore, $onProcessType, $onRowUpdate, $diff) {
			$array = \json_decode($defaults->getValue(), true);

			foreach ($grid->getInputData() as $id => $data) {
				$object = $grid->getSource()->where($sourceIdName ?? $grid->getSource(false)->getPrefix() . $grid->getSourceIdName(), $id)->first();

				// filter data
				if ($diff && isset($array[$id])) {
					$data = \array_diff($data, $array[$id]);

					if (!$data) {
						continue;
					}
				}

				foreach ($processNullColumns as $column) {
					$data[$column] = $data[$column] ?? null;
				}

				foreach ($processTypes as $key => $value) {
					if (\array_search($key, $processNullColumns) !== false && $data[$key] === null) {
						continue;
					}

					if ($onProcessType) {
						$onProcessType($key, $data, $value, $object);
					} else {
						$newValue = $data[$key] ?? null;

						if ($value == 'float') {
							$data[$key] = \floatval(\str_replace(',', '.', $newValue));
							continue;
						}

						$data[$key] = \settype($newValue, $value) ? $newValue : null;
					}
				}

				if ($onRowUpdate) {
					$onRowUpdate($id, $data);
				}

				$this->onUpdateRow($id, $data);

				/*$this->changelogRepository->createOne([
					'user' => $grid->getPresenter()->admin->getIdentity()->getAccount()->login,
					'entity' => $grid->entityName,
					'objectId' => $id,
					'type' => 'grid-edit',
				]);*/

				$grid->getSource()->where($sourceIdName ?? $grid->getSource(false)->getPrefix() . $grid->getSourceIdName(), $id)->setGroupBy([])->update($data, $ignore, $grid->getSource(false)->getPrefix(false));
			}

			$grid->getPresenter()->flashMessage($this->translator->translate('admin.saved', 'Uloženo'), 'success');
			$grid->getPresenter()->redirect('this');
		};
	}

	/**
	 * @param callable|null $beforeDeleteCallback Callback that will be called before object removal. If callback throw exception, it will catch it and object will not be deleted.
	 * @param bool $override If true, object will not be automatically delete. You can delete it manually in callback.
	 * @param callable|null $condition Condition callback, must return boolean
	 * @param string|null $sourceIdName Name of source primary key
	 */
	public function addButtonDeleteSelected(?callable $beforeDeleteCallback = null, bool $override = false, ?callable $condition = null, ?string $sourceIdName = null)
	{
		$grid = $this;
		$submit = $this->getForm()->addSubmit('deleteAll', $this->translator->translate('admin.remove', 'Smazat'));
		$submit->setHtmlAttribute('class', 'btn btn-sm btn-danger');
		$submit->setHtmlAttribute('onClick', 'return confirm("' . $this->translator->translate('admin.really', 'Opravdu?') . '")');
		$submit->onClick[] = function ($button) use ($grid, $beforeDeleteCallback, $override, $condition, $sourceIdName) {
			$warning = false;
			foreach ($grid->getSelectedIds() as $id) {
				$object = $grid->getSource()->where($sourceIdName ?? $grid->getSourceIdName(), $id)->first();

				if ($condition) {
					$allowed = $condition($object);
					if (!$allowed) {
						$warning = true;
						continue;
					}
				}

				if (!$condition || (isset($allowed) && $allowed == true)) {
					if ($beforeDeleteCallback) {
						$beforeDeleteCallback($object);
					}

					if (isset($this->onDelete)) {
						$this->onDelete($object);
					}

					if (!$override && $object) {
						$object->delete();
					}
				}
			}

			if ($warning) {
				$grid->getPresenter()->flashMessage($this->translator->translate('admin.cantBeRemoved', 'Varování: Některé položky nebylo možné smazat!'), 'warning');
			}

			$grid->getPresenter()->flashMessage($this->translator->translate('admin.done', 'Provedeno'), 'success');
			$grid->getPresenter()->redirect('this');
		};
	}

	public function decoratorEmpty(Html $td, $object)
	{
		if (!\trim(\strip_tags($td->getHtml()))) {
			$td->setHtml('');
		}
	}

	public function decoratorNowrap(Html $td, $object)
	{
		$td->addAttributes(['style' => 'white-space: nowrap;']);
	}

	public function decoratorNumber(Html $td, $object)
	{
		$td->addAttributes(['style' => 'white-space: nowrap; text-align: right;']);
	}

	/**
	 * @param array|string[] $resetLink First item is link as string. Second argument to link.
	 */
	public function addFilterButtons(array $resetLink = ['default'])
	{
		$grid = $this;
		$grid->getFilterForm()->addSubmit('submit', $this->translator->translate('admin.filter', 'Filtrovat'))->setHtmlAttribute('class', 'btn btn-sm btn-primary form-control-sm');

		$grid->getFilterForm()->onSuccess[] = function (Form $form) {
			$this->setPage(1);
		};

		$reset = $grid->getFilterForm()->addSubmit('reset', $this->translator->translate('admin.cancelFilter', 'Zrušit'))->setHtmlAttribute('class', 'btn btn-sm btn-secondary form-control-sm');
		$reset->onClick[] = function () use ($grid, $resetLink) {
			// for persistance session storage
			$grid->setFilters(null);
			$grid->setPage(1);

			if (isset($resetLink[1])) {
				$grid->getPresenter()->redirect($resetLink[0], $resetLink[1]);
			} else {
				$grid->getPresenter()->redirect($resetLink[0]);
			}

		};
	}

	public function addFilterTextInput(string $name, array $columns, ?string $label = null, ?string $placeholder = null, ?string $defaultValue = null, string $likeFormat = '%%%s%%')
	{
		$query = '';

		foreach ($columns as $column) {
			$query .= " $column LIKE :$name OR";
		}

		$query = \substr($query, 0, -2);

		$input = $this->addFilterText(function (ICollection $source, $value) use ($name, $query, $likeFormat) {
			if (\strlen($value) == 0) {
				return;
			}

			$source->where($query, [$name => \vsprintf($likeFormat, [$value])]);

		}, $defaultValue, $name, $label);

		if ($placeholder) {
			$input->setHtmlAttribute('placeholder', $placeholder);
		}

		$input->setHtmlAttribute('class', 'form-control form-control-sm');
	}

	public function addFilterSelectInput(string $name, string $query, ?string $label = null, ?string $placeholder = null, ?string $defaultValue = null, ?array $items = null, string $variableName = 'q'): SelectBox
	{
		$input = $this->addFilterSelect(function (ICollection $source, $value) use ($query, $variableName) {
			if ($value !== '') {
				$source->where($query, [$variableName => "$value"]);
			}
		}, $defaultValue, $name, $label, $items);

		if ($placeholder) {
			$input->setPrompt($placeholder);
		}

		$input->setHtmlAttribute('class', 'form-control form-control-sm');

		return $input;
	}

	public function addFilterCheckboxInput(string $name, string $query, ?string $label = null)
	{
		$input = $this->addFilterCheckbox(function (ICollection $source, $value) use ($query) {
			$source->where($query);
		}, null, $name, $label);

		$input->setHtmlAttribute('class', 'mt-2 mr-1');
		$input->getLabelPrototype()->setAttribute('class', 'form-check-label');
	}

	public function addButtonBulkEdit(string $bulkFormId, array $inputs, string $gridId = 'grid', string $name = 'bulkEdit', string $label = 'Hromadná úprava', string $link = 'bulkEdit', string $defaultLink = 'default')
	{
		$this->setBulkForm($bulkFormId, $inputs, $defaultLink);

		$submit = $this->getForm()->addSubmit($name, $label)->setHtmlAttribute('class', 'btn btn-outline-primary btn-sm');
		$submit->onClick[] = function ($button) use ($link, $defaultLink, $gridId) {
			$params = [$this->getName() . '-selected' => $this->getSelectedIds()];
			$params['grid'] = $gridId;

			foreach ($this->getFilters() as $name => $value) {
				$params[$this->getName() . '-' . $name] = $value;
			}

			$this->getPresenter()->redirect($link, $params);
		};
	}

	public function setBulkForm(string $bulkFormId, array $input, string $defaultLink)
	{
		$this->bulkFormId = $bulkFormId;
		$this->bulkFormInputs = $input;
		$this->bulkFormDefaultLink = $defaultLink;
	}

	public function createComponentBulkForm(): Form
	{
		if (!$this->bulkFormId) {
			throw new ApplicationException('Bulk form is not set, call ->setBulkForm($bulkFormId, $inputs) or addButtonBulkEdit');
		}

		/** @var \Nette\Application\UI\Form $sourceForm */
		$sourceForm = $this->getPresenter()->getComponent($this->bulkFormId);

		if (!$sourceForm instanceof AdminForm) {
			$sourceForm = $sourceForm->getComponent('form');
		}

		$ids = $this->getParameter('selected') ?: [];
		$totalNo = $this->getFilteredSource()->setGroupBy([])->enum($this->getFilteredSource()->getPrefix(true) . $this->getSourceIdName(), true);
		$selectedNo = \count($ids);

		$form = $this->formFactory->create();
		$form->setAction($this->link('this', ['selected' => $this->getParameter('selected')]));
		$form->addRadioList('bulkType', 'Upravit', [
			'selected' => "vybrané ($selectedNo)",
			'all' => "celý výsledek ($totalNo)",
		])->setDefaultValue('selected');

		$keep = $form->addContainer('keep');
		$values = $form->addContainer('values');

		foreach ($this->bulkFormInputs as $key => $name) {
			$components = \is_array($name) ? $name : [$name];

			foreach ($components as $nameParsed) {
				$container = \is_string($key) ? $sourceForm[$key] : $sourceForm;

				try {
					$component = $container->getComponent($nameParsed);
				} catch (\Exception $e) {
					continue;
				}

				$container->removeComponent($component);

				$keep->addCheckbox($nameParsed, 'Původní')->setDefaultValue(true);

				//$rules = clone $component->getRules();
				$component->getRules()->reset();
				$component->setRequired(false);
				/*$newRules = $component->addConditionOn($keep[$nameParsed], $form::EQUAL, false);

				foreach ($rules->getIterator() as $rule) {
					try {
						$newRules->addRule($rule->validator, $rule->message, $rule->arg);
					} catch (\Exception $e) {
					}
				}*/

				$values->addComponent($component, $nameParsed);
			}
		}

		$form->addSubmit('submitAndBack', 'Uložit a zpět');

		$form->onSuccess[] = function (AdminForm $form) use ($ids) {
			$values = $form->getValues('array');
			$source = $this->getSource(false);
			$ids = $values['bulkType'] === 'selected' ? $ids : $this->getFilteredSource()->toArrayOf($this->getSourceIdName());

			foreach ($values['keep'] as $name => $keep) {
				if (!$keep) {
					continue;
				}

				$structure = $source instanceof Collection ? $source->getRepository()->getStructure() : null;

				if ($values['values'][$name] && $structure && $structure->getRelation($name) instanceof RelationNxN) {
					foreach ($ids as $id) {
						$relation = new RelationCollection($source->getRepository(), $structure->getRelation($name), $id);
						$relation->relate($values['values'][$name]);
					}
				}

				unset($values['values'][$name]);
			}

//			$structure = $source instanceof Collection ? $source->getRepository()->getStructure() : null;

//			foreach ($values['values'] as $key => $value) {
//				if ($structure) {
//					if ($structure->getRelation($key)) {
//						//@TODO ošetřit pokud má základní tabulka a join tabulky relaci stejného názvu
//					} else {
//						$values['values'][$this->getSource()->getPrefix() . $key] = $value;
//
//						unset($values['values'][$key]);
//					}
//				}
//			}

			$relations = [];

			foreach ($values['values'] as $key => $value) {
				if (isset($form['values'][$key]) && $form['values'][$key] instanceof MultiSelectBox) {
					$relations[$key] = $values['values'][$key];
					unset($values['values'][$key]);
				}
			}

			if (\count($values['values']) === 0) {
				return;
			}

			foreach ($ids as $id) {
				/** @var Entity $object */
				$object = $this->getSource()->where($this->getSource()->getPrefix() . $this->getSourceIdName(), $id)->setGroupBy([])->first();

				if ($object) {
					$updateKeys = [];

					foreach ($values['values'] as $key => $value) {
						try {
							$object->$key = $value;
							$updateKeys[] = $key;
						} catch (\TypeError $e) {
							try {
								$object->$key = NumbersHelper::strtoFloat($value);
								$updateKeys[] = $key;
							} catch (\TypeError $e) {
							}
						}
					}

					$object->updateAll($updateKeys);

					foreach ($relations as $key => $value) {
						try {
							$object->$key->unrelateAll();
							$object->$key->relate($value);
						} catch (\Exception $e) {

						}
					}
				}
			}

			$this->getPresenter()->flashMessage('Uloženo', 'success');
			$this->getPresenter()->redirect($this->bulkFormDefaultLink ?? 'this');
		};

		return $form;
	}

	public function addColumnInputTime($th, string $name, $setValueExpression = '', $defaultValue = '', ?string $orderExpression = null, array $wrapperAttributes = [])
	{
		$this->addColumnInput($th, $name, function ($object) {
			$textInput = new TextInput();
			$textInput->setHtmlAttribute('class', 'form-control form-control-sm');
			$textInput->setNullable();
			$textInput->setHtmlType('time');

			return $textInput;
		}, $setValueExpression, $defaultValue, $orderExpression, $wrapperAttributes);
	}

	public function render(): void
	{
		$this->template->setTranslator($this->translator);

		parent::render();
	}
}
