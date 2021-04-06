<?php

declare(strict_types=1);

namespace Admin\Controls;

use Forms\Controls\UploadImage;
use Forms\Controls\Wysiwyg;
use Forms\DefaultRenderer;
use Forms\Form;
use Nette;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Html;
use Nette\Utils\IHtmlString;

class BootstrapRenderer extends DefaultRenderer
{
	public function __construct()
	{
		$this->wrappers['controls']['container'] = 'table class="mt-1 mb-1"';
		$this->wrappers['pair']['container'] = 'tr class="m-0 mt-1"';
		$this->wrappers['pair']['.error'] = 'has-danger';
		$this->wrappers['control']['container'] = 'td class="align-middle"';
		$this->wrappers['label']['container'] = 'th class="col-label col-form-label col"';
		$this->wrappers['control']['description'] = 'span class=form-text';
		$this->wrappers['control']['errorcontainer'] = 'span class="form-control-feedback text-danger text-sm"';
		$this->wrappers['control']['.error'] = 'is-invalid';
		$this->wrappers['control']['.number'] = 'form-control form-control-sm max-number';
		$this->wrappers['control']['.submit'] = 'btn btn-primary btn-sm mr-1';
		$this->wrappers['control']['.color'] = 'form-control form-control-sm';
		$this->wrappers['control']['.password'] = 'form-control form-control-sm';
		$this->wrappers['control']['.email'] = 'form-control form-control-sm';
	}
	
	public function renderPair(\Nette\Forms\IControl $control): string
	{
		$pair = $this->getWrapper('pair container');
		
		if ($control instanceof BaseControl) {
			$pair->addHtml($this->renderLabel($control));
			$pair->addHtml($this->renderControl($control));
			$pair->class($this->getValue($control->isRequired() ? 'pair .required' : 'pair .optional'), true);
			$pair->class($control->hasErrors() ? $this->getValue('pair .error') : null, true);
			$pair->class($control->getOption('class'), true);
			
			if (++$this->counter % 2) {
				$pair->class($this->getValue('pair .odd'), true);
			}
			
			$pair->id = $control->getOption('id');
		}
		
		if ($control instanceof BaseControl && $form = $control->getForm()) {
			if ($form instanceof Form) {
				$controlMutation = $control->getControlPrototype()->getAttribute('data-mutation');
				$pair->id($control->getHtmlId() . '-toogle');
				
				if ($controlMutation) {
					$activeMutation = $form->getActiveMutation();
					$pair->setAttribute('data-mutation', $controlMutation);
					
					if (strpos($control->getHtmlId(), Form::MUTATION_TRANSLATOR_NAME) !== false) {
						$pair->setAttribute('class', $pair->getAttribute('class') . ' translated');
					}
					
					if ($controlMutation !== $activeMutation && $activeMutation !== null) {
						$pair->setAttribute('class', $pair->getAttribute('class') . ' inactive');
					}
				}
			}
			
			$dataInfo = $control->getControlPrototype()->getAttribute('data-info');
			if ($dataInfo) {
				$pair[1]->addHtml('<span id="data-info" class="text-gray text-sm">' . $dataInfo . '</span>');
			} elseif ($control instanceof Wysiwyg) {
				$pair[1]->addHtml('<span id="data-info" class="text-sm"><a href="https://paper.dropbox.com/doc/Navod-k-ovladani-obsahoveho-editoru-administrace-TinyMCE-editor--BDmzygnD1pN7ynfuDf5UtU1iAg-JVo4q5xWIEdUqOhWL1EYY" target="_blank">Návod k ovládání obsahového editoru administrace (TinyMCE editor)</a></span>');
			}
		}
		
		return $pair->render(0);
	}
	
	private function doRenderErrors(array $errors, bool $control): string
	{
		if (!$errors) {
			return '';
		}
		$container = $this->getWrapper($control ? 'control errorcontainer' : 'error container');
		$item = $this->getWrapper($control ? 'control erroritem' : 'error item');
		
		foreach ($errors as $error) {
			$item = clone $item;
			if ($error instanceof IHtmlString) {
				$item->addHtml($error);
			} else {
				$item->setText($error);
			}
			$container->addHtml($item);
		}
		
		return $control
			? "\n\t" . $container->render()
			: "\n" . $container->render(0);
	}
	
	/**
	 * Renders 'control' part of visual row of controls.
	 */
	public function renderControl(Nette\Forms\IControl $control): Html
	{
		$body = $this->getWrapper('control container');
		if ($this->counter % 2) {
			$body->class($this->getValue('control .odd'), true);
		}
		if (!$this->getWrapper('pair container')->getName()) {
			$body->class($control->getOption('class'), true);
			$body->id = $control->getOption('id');
		}
		
		$description = $control->getOption('description');
		if ($description instanceof IHtmlString) {
			$description = ' ' . $description;
			
		} elseif ($description != null) { // intentionally ==
			if ($control instanceof Nette\Forms\Controls\BaseControl) {
				$description = $control->translate($description);
			}
			$description = ' ' . $this->getWrapper('control description')->setText($description);
			
		} else {
			$description = '';
		}
		
		if ($control->isRequired()) {
			$description = $this->getValue('control requiredsuffix') . $description;
		}
		
		$els = $errors = [];
		renderControl:
		$control->setOption('rendered', true);
		
		$el = Html::el();
		if ($control instanceof Nette\Forms\Controls\RadioList) {
			foreach ($control->getItems() as $key => $item) {
				$el->addHtml('<div class="form-check" style="display: inline-block; margin-right: 10px;">');
				$el->addHtml($control->getControlPart($key)->class('form-check-input'));
				$el->addHtml($control->getLabelPart($key)->class('form-check-label'));
				$el->addHtml('</div>');
			}
		} elseif ($control instanceof Nette\Forms\Controls\Checkbox) {
			$items = $control->getControl();
			$el->addHtml('<div class="form-check">');
			foreach ($items as $key => $item) {
				$el->addHtml($control->getControlPart($key));
				$el[1]->class('form-check-input');
				$el->addHtml($control->getLabelPart($key));
				$el[2]->class('form-check-label');
			}
			$el->addHtml('</div>');
			
		} elseif ($control instanceof Wysiwyg) {
			$el->addHtml($control->getControl());
			
		} elseif ($control instanceof Nette\Forms\Controls\Button) {
			$el->addHtml($control->getControl());
			
		} elseif ($control instanceof Nette\Forms\Controls\TextArea) {
			$el->addHtml($control->getControl());
			$el[0]->class('form-control form-control-sm');
			
		} elseif ($control instanceof Nette\Forms\Controls\SelectBox) {
			$el->addHtml($control->getControl());
			$el[0]->class('form-control form-control-sm col-label');
			
		} elseif ($control instanceof Nette\Forms\Controls\TextInput) {
			$el->addHtml($control->getControl());
			if ($control->getControlPrototype()->type == 'number') {
				$el[0]->class('form-control form-control-sm max-number ' . $el[0]->class);
			} else {
				$el[0]->class('form-control form-control-sm col-label ' . $el[0]->class);
			}
			
		} elseif (isset($control->getControl()->attrs['class']) && $control->getControl()->attrs['class'] == 'flatpicker') {
			$el->addHtml($control->getControl());
			$el[0]->class('form-control form-control-sm');
			
		} elseif ($control instanceof UploadImage) {
			$el->addHtml($control->getControl());
			if (isset($el[0][1]->value)) {
				$el[0][2]->class('btn btn-danger btn-sm ml-2');
				$el[0][2][0] = 'Smazat';
			} else {
				$el = Html::el();
				$el->addHtml('<div class="input-group col-label m-0 p-0">');
				$el->addHtml('<label class="input-group-btn">');
				$el->addHtml(' <span class="btn btn-outline-primary btn-sm">Procházet');
				$el->addHtml($control->getControl());
				$el[3]->style('display: none;');
				$el->addHtml('</span>');
				$el->addHtml('</label>');
				$el->addHtml('<input type="text" class="form-control form-control-sm" readonly="">');
				$el->addHtml('</div>');
			}
			
		} elseif ($control instanceof Nette\Forms\Controls\UploadControl) {
			$el = Html::el();
			$el->addHtml('<div class="input-group col-label m-0 p-0">');
			$el->addHtml('<label class="input-group-btn">');
			$el->addHtml(' <span class="btn btn-outline-primary btn-sm">Procházet');
			$el->addHtml($control->getControl());
			$el[3]->style('display: none;');
			$el->addHtml('</span>');
			$el->addHtml('</label>');
			$el->addHtml('<input type="text" class="form-control form-control-sm" readonly="">');
			$el->addHtml('</div>');
		} else {
			$el = $control->getControl();
		}
		
		if ($el instanceof Html) {
			if ($el->getName() === 'input') {
				$el->class($this->getValue("control .$el->type"), true);
			}
			
			$el->class($this->getValue('control .error'), $control->hasErrors());
		}
		
		$errors = array_merge($errors, $control->getErrors());
		if (\count($errors) > 0) {
			if ($el[0] instanceof Html) {
				$el[0]->class($el[0]->class . ' is-invalid');
			} else {
				try {
					$el[6] = '<input type="text" class="form-control form-control-sm is-invalid" readonly="">';
				} catch (\Exception $e) {
				
				}
			}
		}
		
		$els[] = $el;
		
		if ($nextTo = $control->getOption('nextTo')) {
			$control = $control->getForm()->getComponent($nextTo);
			$body->class($this->getValue('control .multi'), true);
			goto renderControl;
		}
		
		return $body->setHtml(implode('', $els) . $description . $this->doRenderErrors($errors, true));
	}
	
	public function renderPairMulti(array $controls): string
	{
		$s = [];
		foreach ($controls as $control) {
			if (!$control instanceof Nette\Forms\IControl) {
				throw new Nette\InvalidArgumentException('Argument must be array of Nette\Forms\IControl instances.');
			}
			$description = $control->getOption('description');
			if ($description instanceof IHtmlString) {
				$description = ' ' . $description;
				
			} elseif ($description != null) { // intentionally ==
				if ($control instanceof Nette\Forms\Controls\BaseControl) {
					$description = $control->translate($description);
				}
				$description = ' ' . $this->getWrapper('control description')->setText($description);
				
			} else {
				$description = '';
			}
			
			$control->setOption('rendered', true);
			
			if ($control instanceof Nette\Forms\Controls\SubmitButton) {
				$el = Html::el();
				$el->addHtml('<div class="pl-0 m-0">');
				
				if ($control->getName() == 'submitAndBack') {
					$el->addHtml(Html::el('button type="submit" name="submitAndBack" class="btn btn-outline-primary btn-sm button m-1"'));
					$el[1]->setHtml('<i class="fa fa-sm fa-undo-alt"></i>&nbsp;Uložit a zpět');
					$el->addHtml('</button>');
				} else if ($control->getName() == 'submitAndContinue') {
					$el->addHtml(Html::el('button type="submit" name="submitAndContinue" class="btn btn-outline-primary btn-sm button m-1"'));
					$el[1]->setHtml('<i class="fa fa-sm fa-plus"></i>&nbsp;Uložit a vložit další');
					$el->addHtml('</button>');
				} else {
					$el->addHtml($control->getControl());
					
					if (!$el[1]->class) {
						$el[1]->class('btn btn-primary btn-sm ml-0 mt-1 mb-1 mr-1');
					}
				}
				
				$el->addHtml('</div>');
			} else {
				$el = $control->getControl();
			}
			
			if ($el instanceof Html) {
				if ($el->getName() === 'input') {
					$el->class($this->getValue("control .$el->type"), true);
				}
				$el->class($this->getValue('control .error'), $control->hasErrors());
			}
			$s[] = $el . $description;
		}
		$pair = $this->getWrapper('pair container');
		$pair->addHtml($this->renderLabel($control));
		$pair->addHtml($this->getWrapper('control container')->setHtml(implode(' ', $s)));
		$pair[1]->class('d-inline-flex align-middle');
		
		return $pair->render(0);
	}
	
	public function renderControls($parent): string
	{
		if (!($parent instanceof Nette\Forms\Container || $parent instanceof Nette\Forms\ControlGroup)) {
			throw new Nette\InvalidArgumentException('Argument must be Nette\Forms\Container or Nette\Forms\ControlGroup instance.');
		}
		
		$container = $this->getWrapper('controls container');
		
		$buttons = null;
		foreach ($parent->getControls() as $control) {
			
			if ($control->getOption('rendered') || $control->getOption('type') === 'hidden') {
				continue;
			}
			
			if ($control->getOption('type') === 'button') {
				$buttons[] = $control;
				
			} else {
				if ($buttons) {
					$container->addHtml($this->renderPairMulti($buttons));
					$buttons = null;
				}
				$container->addHtml($this->renderPair($control));
			}
		}
		
		if ($buttons) {
			$container->addHtml($this->renderPairMulti($buttons));
		}
		
		$s = '';
		if (count($container)) {
			$s .= "\n" . $container . "\n";
		}
		
		return $s;
	}
}
