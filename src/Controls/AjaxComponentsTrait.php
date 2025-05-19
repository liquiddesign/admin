<?php

namespace Admin\Controls;

use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Forms\Controls\SelectBox;

trait AjaxComponentsTrait
{
	/** @var array<array<string>> */
	public array $ajaxInputs = [];

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
		mixed $name,
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
		$presenter = $this->lookup(Presenter::class, throw: false);

		if (!$presenter) {
			throw new \Exception('Missing Presenter');
		}

		$presenter->ajaxInputs[$this->getName()][] = $name;

		$link = $presenter->link('getAjaxArrayForSelect!', ['name' => $className,]);

		return $this->addSelect2Ajax($name, $link, $label, $configuration, $placeholder);
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
	public function addMultiSelectAjax(
		$name,
		?string $label = null,
		?string $placeholder = null,
		?string $className = null,
		?array $configuration = []
	): MultiSelectBox {
		if (!$className) {
			throw new \Exception('Missing DataSource');
		}

		$this->ajaxInputs[$this->getName()][] = $name;

		/** @var \Admin\BackendPresenter|null $presenter */
		$presenter = $this->lookup(Presenter::class, throw: false);

		if (!$presenter) {
			throw new \Exception('Missing Presenter');
		}

		$presenter->ajaxInputs[$this->getName()][] = $name;

		$link = $presenter->link('getAjaxArrayForSelect!', ['name' => $className,]);

		return $this->addMultiSelect2Ajax($name, $link, $label, $configuration, $placeholder);
	}

	private function getValuesWithAjaxItem(&$values, $data, $inputName): void
	{
		if (\is_array($inputName)) {
			/**
			 * @var string $key
			 * @var array<mixed>|string $subInputName
			 */
			foreach ($this->ajaxInputs[$this->getName()] ?? [] as $key => $subInputName) {
				if (\is_array($subInputName)) {
					$this->getValuesWithAjaxItem($values, $data[$key], $subInputName);
				} else {
					$values[$subInputName] = $data[$subInputName];
				}
			}
		} else {
			$values[$inputName] = $data[$inputName];
		}
	}

	private function getValuesWithAjaxContainer(array &$values, array $data, AdminContainer $component): void
	{
		foreach ($component->getComponents() as $subComponent) {
			if ($subComponent instanceof AdminContainer) {
				$values[$component->getName()] = [];

				$this->getValuesWithAjaxContainer($values[$component->getName()], $data[$component->getName()] ?? [], $subComponent);
			}
		}

		$values = $component->getValuesWithAjax($data);
	}
}
