<?php

declare(strict_types=1);

namespace Admin\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminGrid;
use Admin\DB\Changelog;
use Admin\DB\ChangelogRepository;
use Nette\Utils\DateTime;

class LogPresenter extends BackendPresenter
{
	/** @inject */
	public ChangelogRepository $changelogRepository;
	
	public function renderDefault(): void
	{
		$tLog = $this->translator->translate('adminWebLog.logs', 'Log změn');
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
		$grid->addColumn($this->translator->translate('admin.created', 'Vytvořeno'), function (Changelog $changelog) {
			return DateTime::from($changelog->created)->format('d.m.Y G:i:s');
		}, '%s', 'created');
		$grid->addColumnText($this->translator->translate('admin.user', 'Uživatel'), 'user', '%s', 'user');
		$grid->addColumnText($this->translator->translate('adminWebLog.entity', 'Entita'), 'entity', '%s', 'entity');
		$grid->addColumnText($this->translator->translate('adminWebLog.objectId', 'Id záznamu'), 'objectId', '%s', 'objectId');
		$grid->addColumnText($this->translator->translate('adminWebLog.type', 'Typ změny'), 'type', '%s', 'type');
		
		return $grid;
	}
}