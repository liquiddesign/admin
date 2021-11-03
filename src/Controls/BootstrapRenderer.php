<?php

declare(strict_types=1);

namespace Admin\Controls;

use Forms\Controls\UploadFile;
use Forms\Controls\UploadImage;
use Forms\Controls\Wysiwyg;
use Forms\DefaultRenderer;
use Forms\Form;
use Nette;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Html;

class BootstrapRenderer extends DefaultRenderer
{
	private const WYSIWYG_INFO =
		'<a href="https://paper.dropbox.com/doc/Navod-k-ovladani-obsahoveho-editoru-administrace-TinyMCE-editor--BDmzygnD1pN7ynfuDf5UtU1iAg-JVo4q5xWIEdUqOhWL1EYY" target="_blank">
			Návod k ovládání obsahového editoru administrace (TinyMCE editor)
		</a>';
	
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
			$controlPart = $this->renderControl($control);
			$pair->addHtml($controlPart);
			$pair->appendAttribute('class', $this->getValue($control->isRequired() ? 'pair .required' : 'pair .optional'));
			$pair->appendAttribute('class', $control->hasErrors() ? $this->getValue('pair .error') : null);
			$pair->appendAttribute('class', $control->getOption('class'));
			
			if (++$this->counter % 2) {
				$pair->appendAttribute('class', $this->getValue('pair .odd'));
			}
			
			$pair->setAttribute('id', $control->getOption('id'));
			
			if ($dataInfo = $control->getControlPrototype()->getAttribute('data-info') ?: ($control instanceof Wysiwyg ? self::WYSIWYG_INFO : null)) {
				$controlPart->addHtml('<span id="data-info" class="text-sm"> ' . $dataInfo . ' </span>');
			}
			
			if ($dataUrlLink = $control->getControlPrototype()->getAttribute('data-url-link-'. ($controlMutation ?? ''))) {
				$controlPart->addHtml($dataUrlLink);
			}
			
			if ($control->getForm() instanceof Form) {
				$controlMutation = $control->getControlPrototype()->getAttribute('data-mutation');
				$pair->setAttribute('id', $control->getHtmlId() . '-toogle');
				
				if ($controlMutation) {
					$pair->setAttribute('data-mutation', $controlMutation);
					
					if (\strpos($control->getHtmlId(), Form::MUTATION_TRANSLATOR_NAME)) {
						$pair->appendAttribute('class', 'translated');
					}
					
					if ($controlMutation !== $control->getForm()->getActiveMutation() && $control->getForm()->getActiveMutation() !== null) {
						$pair->appendAttribute('class', 'inactive');
					}
				}
			}
		}
		
		return $pair->render(0);
	}
	
	public function renderControls($parent): string
	{
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
		
		if (\count($container)) {
			$s .= "\n" . $container . "\n";
		}
		
		return $s;
	}
	
	/**
	 * Renders 'control' part of visual row of controls.
	 */
	public function renderControl(Nette\Forms\IControl $control): Html
	{
		if (!$control instanceof BaseControl) {
			return Html::el();
		}
		
		$body = $this->getWrapper('control container');
		
		if ($this->counter % 2) {
			$body->appendAttribute('class', $this->getValue('control .odd'));
		}
		
		if (!$this->getWrapper('pair container')->getName()) {
			$body->appendAttribute('class', $control->getOption('class'));
			$body->setAttribute('id', $control->getOption('id'));
		}
		
		$description = $control->getOption('description');
		
		if ($description instanceof Nette\HtmlStringable) {
			$description = ' ' . $description;
		} elseif ($description) {
			$description = ' ' . $this->getWrapper('control description')->setText($control->translate($description));
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
			$el->addHtml('<div class="form-check">');
			
			for ($i = 0; $i !== \count($control->getControl()); $i++) {
				$el->addHtml($control->getControlPart()->class('form-check-input'));
				$el->addHtml($control->getLabelPart()->class('form-check-label'));
			}
			
			$el->addHtml('</div>');
		} elseif ($control instanceof Wysiwyg) {
			$el->addHtml($control->getControl());
		} elseif ($control instanceof Nette\Forms\Controls\Button) {
			$el->addHtml($control->getControl());
		} elseif ($control instanceof Nette\Forms\Controls\TextArea) {
			$el->addHtml($control->getControl()->appendAttribute('class', 'form-control form-control-sm'));
		} elseif ($control instanceof Nette\Forms\Controls\SelectBox) {
			$el->addHtml($control->getControl()->appendAttribute('class', 'form-control form-control-sm col-label'));
		} elseif ($control instanceof Nette\Forms\Controls\TextInput) {
			$class = $control->getControlPrototype()->type === 'number' ? 'form-control form-control-sm max-number' : 'form-control form-control-sm col-label';
			$el->addHtml($control->getControl()->appendAttribute('class', $class));
		} elseif ($control->getControl()->getAttribute('class')['flatpicker'] ?? false) {
			$el->addHtml($control->getControl()->class('form-control form-control-sm'));
		} elseif ($control instanceof UploadImage || $control instanceof UploadFile) {
			$el->addHtml($control->getControl());
			
			if (isset($el[0][1]->value)) {
				if (isset($el[0][2])) {
					$el[0][2]->class('btn btn-danger btn-sm ml-2');
					$el[0][2][0] = 'Smazat';
				}
			} else {
				$el = Html::el();
				$el->addHtml('<div class="input-group col-label m-0 p-0">');
				$el->addHtml('<label class="input-group-btn">');
				$el->addHtml(' <span class="btn btn-outline-primary btn-sm" style="">Procházet');
				$el->addHtml($control->getControl()->style('display: none;'));
				$el->addHtml('</span>');
				$el->addHtml('</label>');
				$el->addHtml('<input type="text" class="form-control form-control-sm" readonly="">');
				$el->addHtml('</div>');
			}
		} elseif ($control instanceof Nette\Forms\Controls\UploadControl) {
			if ($control->getControlPrototype()->class === 'dropzone') {
				$el = Html::el();
				$el->addHtml('<div id="' . $control->getHtmlId() . '">
					<div class="dz-default dz-message"><button class="dz-button" type="button">Přetáhněte soubory do tohoto pole</button></div>
				</div>');
			} else {
				$el = Html::el();
				$el->addHtml('<div class="input-group col-label m-0 p-0">');
				$el->addHtml('<label class="input-group-btn">');
				$el->addHtml(' <span class="btn btn-outline-primary btn-sm">Procházet');
				$el->addHtml($control->getControl()->style('display: none;'));
				$el->addHtml('</span>');
				$el->addHtml('</label>');
				$el->addHtml('<input type="text" class="form-control form-control-sm" readonly="">');
				$el->addHtml('</div>');
			}
		} else {
			$el = $control->getControl();
		}
		
		if ($el instanceof Html) {
			if ($el->getName() === 'input') {
				$el->class($this->getValue("control .$el->type"), true);
			}
			
			$el->class($this->getValue('control .error'), $control->hasErrors());
		}
		
		$errors = \array_merge($errors, $control->getErrors());

		if (\count($errors) > 0) {
			if ($el[0] instanceof Html) {
				$el[0]->class((\is_array($el[0]->class) ? \implode(' ', \array_keys(\array_filter($el[0]->class, function ($value) {
						return $value === true;
					}))) : $el[0]->class) . ' is-invalid');
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
		
		return $body->setHtml(\implode('', $els) . $description . $this->doRenderErrors($errors, true));
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
			$error instanceof Nette\HtmlStringable ? $item->addHtml($error) : $item->setText($error);
			$container->addHtml($item);
		}
		
		return $control ? "\n\t" . $container->render() : "\n" . $container->render(0);
	}
}
