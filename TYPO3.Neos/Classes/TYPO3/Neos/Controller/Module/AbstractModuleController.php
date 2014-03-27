<?php
namespace TYPO3\Neos\Controller\Module;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;

/**
 * The TYPO3 Abstract module controller
 *
 * @Flow\Scope("singleton")
 */
abstract class AbstractModuleController extends ActionController {

	/**
	 * @var array
	 */
	protected $moduleConfiguration;

	/**
	 * @return void
	 */
	protected function initializeAction() {
		$this->moduleConfiguration = $this->request->getInternalArgument('__moduleConfiguration');
	}

	/**
	 * @param \TYPO3\Flow\Mvc\View\ViewInterface $view
	 * @return void
	 */
	protected function initializeView(\TYPO3\Flow\Mvc\View\ViewInterface $view) {
		$view->assign('moduleConfiguration', $this->moduleConfiguration);
	}

	/**
	 * Use this method to set an alternative title than the module label
	 *
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->request->setArgument('title', $title);
	}

	/**
	 * @return void
	 */
	public function indexAction() {
	}

	/**
	 * Display no flash message at all on errors.
	 *
	 * @return \TYPO3\Flow\Error\Message returns FALSE
	 */
	protected function getErrorFlashMessage() {
		return FALSE;
	}

}
