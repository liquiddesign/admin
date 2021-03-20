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
	
	public function create(): AdminForm
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->formFactory->create(AdminForm::class);
		
		$form->setPageRepository($this->pageRepository);
		$form->setRenderer(new BootstrapRenderer());
		$form->addHidden('uuid')->setNullable();
		$form->addGroup('HLAVNÍ ÚDAJE');
		
		$form->onError[] = function (AdminForm $form){
			$form->getPresenter()->flashMessage('Chybně vyplněný formulář!','error');
		};
		
		return $form;
	}
}