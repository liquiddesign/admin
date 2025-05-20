<?php

namespace Admin\Controls;

use Forms\Container;

class AdminContainer extends Container
{
	use AjaxComponentsTrait;

	public function addContainer(int|string $name): Container
	{
		$control = new self();
		$control->currentGroup = $this->currentGroup;
		$this->currentGroup?->add($control);

		return $this[$name] = $control;
	}

	/**
	 * @return array<mixed>
	 */
	public function getValuesWithAjax(array $data): array
	{
		$values = $this->getValues('array');

		/**
		 * @var string $key
		 * @var array<mixed>|string $inputName
		 */
		foreach ($this->ajaxInputs[$this->getName()] ?? [] as $key => $inputName) {
			if (\is_array($inputName)) {
				$this->getValuesWithAjaxItem($values, $data[$key], $inputName);
			} elseif (isset($data[$inputName])) {
				$values[$inputName] = $data[$inputName];
			}
		}

		return $values;
	}
}
