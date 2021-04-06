<?php

declare(strict_types=1);

namespace Admin\Controls;

use Web\DB\PageRepository;
use Forms\FormFactory;

class AdminFormFactory
{
	private PageRepository $pageRepository;
	
	public FormFactory $formFactory;
	
	public function __construct(FormFactory $formFactory, PageRepository $pageRepository)
	{
		$this->formFactory = $formFactory;
		$this->pageRepository = $pageRepository;
	}
	
	public function create(bool $mutationSelector = false, bool $translatedCheckbox = true): AdminForm
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->formFactory->create(AdminForm::class);
		
		$form->setPageRepository($this->pageRepository);
		$form->setRenderer(new BootstrapRenderer());
		$form->addHidden('uuid')->setNullable();
		$form->addGroup('HLAVNÍ ÚDAJE');
		
		if ($mutationSelector && \count($form->getMutations()) > 1) {
			$form->addMutationSelector('Zvolte mutaci');
			if ($translatedCheckbox) {
				$form->addTranslatedCheckbox('Mutace je aktivní');
			}
			$form->addGroup();
		}
		
		$form->onError[] = function (AdminForm $form){
			$form->getPresenter()->flashMessage('Chybně vyplněný formulář!','error');
		};
		
		return $form;
	}
}