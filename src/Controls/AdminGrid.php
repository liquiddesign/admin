<?php

declare(strict_types=1);

namespace Admin\Controls;

use Forms\Form;
use Grid\Column;
use Nette\Application\ApplicationException;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Html;
use StORM\Entity;
use StORM\ICollection;

/**
 * @method onDelete(Entity $object)
 */
class AdminGrid extends \Grid\Datagrid
{
	/**
	 * @var callable[]
	 */
	public array $onDelete = [];

	private ?string $bulkFormId = null;

	private ?array $bulkFormInputs = null;

	private ?string $bulkFormDefaultLink = null;

	private AdminFormFactory $formFactory;

	public function __construct(ICollection $source, ?int $defaultOnPage = null, ?string $defaultOrderExpression = null, ?string $defaultOrderDir = null, bool $encodeId = false)
	{
		parent::__construct($source, $defaultOnPage, $defaultOrderExpression, $defaultOrderDir, $encodeId);

		$this->onRender[] = function (\Nette\Utils\Html $tbody, array $columns) {
			if (\count($tbody) === 0) {
				$tbody->addHtml((\Nette\Utils\Html::el('tr')->addHtml(\Nette\Utils\Html::el('td', ['colspan' => \count($columns)])->setHtml('<i>Žádný výsledek. Zkuste změnit nebo vymazat filtry.</i>')->class('text-center p-2'))));
			}
		};

		$this->onAnchor[] = function (AdminGrid $grid) {
			$grid->template->setFile(__DIR__ . '/adminGrid.latte');
			$grid->template->paginator = $grid->getPaginator(true);
			$grid->template->onpage = $grid->getName() . '-onpage';
			$grid->template->page = $grid->getName() . '-page';

			[$orderColumn, $orderDirection] = \explode('-', $this->getOrderParameter());

			foreach ($this->columns as $column) {
				$column->onRender[] = function (\Nette\Utils\Html $th) use ($orderColumn, $orderDirection, $column) {
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
	}

	protected function createComponentFilterForm(): \Nette\Application\UI\Form
	{
		$form = $this->formFactory->create();
		$this->makeFilterForm($form);

		return $form;
	}

	public function setFormsFactory(AdminFormFactory $formFactory)
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

	public function addColumnSelector(array $wrapperAttributes = []): Column
	{
		return parent::addColumnSelector($wrapperAttributes + ['class' => 'fit']);
	}

	public function addColumnImage(string $expression, string $dir, string $subDir = 'thumb', string $th = '')
	{
		$baseUrl = $this->getPresenter()->getHttpRequest()->getUrl()->getBaseUrl();

		return $this->addColumn($th, function (Entity $entity) use ($baseUrl, $dir, $expression, $subDir) {

			foreach (\explode('.', $expression) as $property) {
				$entity = $entity->$property;
			}

			if ($entity === null) {
				$path = $baseUrl . '/public/admin/img/no-image-icon.png?t=' . \time();

				return "<img src='$path' style='height:32px;'>";
			}

			$path = $baseUrl . '/userfiles/' . $dir . '/' . $subDir . '/' . $entity . '?t=' . \time();

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
		}, $setValueExpression, $defaultValue, $orderExpression, ['class' => 'minimal'] + $wrapperAttributes);
	}

	public function addColumnLinkDetail(string $destination = 'detail', array $arguments = []): Column
	{
		return $this->addColumn('', function ($object, $datagrid) use ($destination, $arguments) {
			return $datagrid->getPresenter()->link($destination, [$object] + $arguments);
		}, '<a class="btn btn-primary btn-sm text-xs" href="%s" title="Upravit"><i class="far fa-edit"></i></a>', null, ['class' => 'minimal']);
	}

	public function addColumnLink(string $destination, string $label = '', ?string $th = null, $wrappers = ['class' => 'minimal']): Column
	{
		return $this->addColumn($th, function ($object, $datagrid) use ($destination, $label) {
			return $datagrid->getPresenter()->link($destination, $object);
		}, '<a class="btn btn-outline-primary btn-sm text-xs" style="white-space: nowrap" href="%s">' . $label . '</a>', null, $wrappers);
	}

	public function addColumnMutations(string $property)
	{
		$this->addColumn('Mutace', function (Entity $object) use ($property) {
			$img = [];
			$baseUrl = $this->getPresenter()->getHttpRequest()->getUrl()->getBaseUrl();

			foreach ($this->formFactory->getDefaultMutations() as $mutation) {
				[$flagsPath, $flagsExt, $flagsMap] = $this->formFactory->getDefaultFlagsConfiguration();
				$style = $object->getValue($property, $mutation) === null ? 'filter: grayscale(100%);' : '';
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
		return $this->addColumnAction('', "<a href=\"%s\" class='btn btn-danger btn-sm text-xs' title='Smazat' onclick=\"return confirm('Opravdu?')\"><i class='far fa-trash-alt'></i></a>",
			function (Entity $object) use ($beforeDeleteCallback, $override, $condition) {
				try {
					$this->getPresenter()->flashMessage('Provedeno', 'success');

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

						if (!$override) {
							$object->delete();
						}
					} else {
						$this->getPresenter()->flashMessage('Chyba: Je zákázáno tuto položku smazat!', 'error');
						$this->getPresenter()->redirect('this');
					}
				} catch (\Exception $e) {
					$this->getPresenter()->flashMessage('Chyba: Nelze smazat.', 'error');
				}
				$this->getPresenter()->redirect('this');
			}, [], null, ['class' => 'minimal']);
	}

	public function addColumnActionDeleteSystemic()
	{
		$column = $this->addColumnActionDelete(null, false, function (Entity $object) {
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
	 */
	public function addButtonSaveAll(array $processNullColumns = [], array $processTypes = [], ?string $sourceIdName = null)
	{
		$grid = $this;
		$submit = $this->getForm()->addSubmit('submit', 'Uložit');
		$submit->setHtmlAttribute('class', 'btn btn-sm btn-primary');
		$submit->onClick[] = function ($button) use ($grid, $processNullColumns, $processTypes, $sourceIdName) {
			foreach ($grid->getInputData() as $id => $data) {
				if (empty($data)) {
					continue;
				}

				foreach ($processNullColumns as $column) {
					$data[$column] = $data[$column] ?? null;
				}

				foreach ($processTypes as $key => $value) {
					if (\array_search($key, $processNullColumns) !== false && $data[$key] === null) {
						continue;
					}

					$newValue = $data[$key];

					if ($value == 'float') {
						$data[$key] = \floatval(\str_replace(',', '.', \str_replace('.', '', $newValue)));
						continue;
					}

					$data[$key] = \settype($newValue, $value) ? $newValue : null;
				}

				$grid->getSource()->where($sourceIdName ?? $grid->getSourceIdName(), $id)->update($data);
			}

			$grid->getPresenter()->flashMessage('Uloženo', 'success');
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
		$submit = $this->getForm()->addSubmit('deleteAll', 'Smazat');
		$submit->setHtmlAttribute('class', 'btn btn-sm btn-danger');
		$submit->setHtmlAttribute('onClick', 'return confirm("Opravdu?")');
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

					if (!$override) {
						$object->delete();
					}
				}
			}

			if ($warning) {
				$grid->getPresenter()->flashMessage('Varování: Některé položky nebylo možné smazat!', 'warning');
			}

			$grid->getPresenter()->flashMessage('Provedeno', 'success');
			$grid->getPresenter()->redirect('this');
		};
	}

	public function decoratorEmpty(Html $td, Entity $object)
	{
		if (!\trim(\strip_tags($td->getHtml()))) {
			$td->setHtml('');
		}
	}

	public function decoratorNowrap(Html $td, Entity $object)
	{
		$td->addAttributes(['style' => 'white-space: nowrap;']);
	}

	public function decoratorNumber(Html $td, Entity $object)
	{
		$td->addAttributes(['style' => 'white-space: nowrap; text-align: right;']);
	}

	/**
	 * @param array|string[] $resetLink First item is link as string. Second argument to link.
	 */
	public function addFilterButtons(array $resetLink = ['default'])
	{
		$grid = $this;
		$grid->getFilterForm()->addSubmit('submit', 'Filtrovat')->setHtmlAttribute('class', 'btn btn-sm btn-primary');

		$grid->getFilterForm()->onSuccess[] = function (Form $form) {
			$this->setPage(1);
		};

		$reset = $grid->getFilterForm()->addSubmit('reset', 'Zrušit')->setHtmlAttribute('class', 'btn btn-sm btn-secondary');
		$reset->onClick[] = function () use ($grid, $resetLink) {
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
		$totalNo = $this->getFilteredSource()->enum();
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

				$component = $container->getComponent($nameParsed);
				$container->removeComponent($component);

				$keep->addCheckbox($nameParsed, 'Původní')->setDefaultValue(true);
				$values->addComponent($component, $nameParsed);
			}
		}

		$form->addSubmit('submitAndBack', 'Uložit a zpět');

		$form->onSuccess[] = function (AdminForm $form) use ($ids) {
			$values = $form->getValues('array');

			foreach ($values['keep'] as $name => $keep) {
				if (!$keep) {
					continue;
				}

				unset($values['values'][$name]);
			}

			if (\count($values['values']) === 0) {
				return;
			}

			$ids = $values['bulkType'] === 'selected' ? $ids : $this->getFilteredSource()->toArrayOf($this->getSourceIdName());

			foreach ($ids as $id) {

				$this->getSource()->where($this->getSourceIdName(), $id)->update($values['values']);
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
}
