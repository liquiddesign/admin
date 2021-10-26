<?php

declare(strict_types=1);

namespace Admin\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminGrid;
use Admin\DB\ChangelogRepository;

class LogPresenter extends BackendPresenter
{
	/**
	 * @inject
	 */
	public ChangelogRepository $changelogRepository;
	
	public function renderDefault(): void
	{
		$tLog = $this->_('logs', 'Log změn');
		$this->template->headerLabel = $tLog;
		$this->template->headerTree = [
			[$tLog],
		];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function createComponentGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->changelogRepository->many());
		$grid->setDefaultOrder('created', 'DESC');
		$grid->addColumnText($this->_('.created', 'Vytvořeno'), "created|date:'d.m.Y G:i:s'", '%s', 'created');
		$grid->addColumnText($this->_('.user', 'Uživatel'), 'user', '%s', 'user');
		$grid->addColumnText($this->_('entity', 'Entita'), 'entity', '%s', 'entity');
		$grid->addColumnText($this->_('objectId', 'Id záznamu'), 'objectId', '%s', 'objectId');
		$grid->addColumnText($this->_('type', 'Typ změny'), 'type', '%s', 'type');
		
		return $grid;
	}
}
