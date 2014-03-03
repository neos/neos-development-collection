<?php
namespace TYPO3\Neos\Controller\Backend;

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

/**
 * @Flow\Scope("singleton")
 */
class MenuController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\Neos\Controller\Backend\MenuHelper
	 * @Flow\Inject
	 */
	protected $menuHelper;

	/**
	 * @return string
	 */
	public function indexAction() {
		$this->response->setHeader('Content-Type', 'application/json');

		$contentModuleUri = $this->getControllerContext()->getUriBuilder()
			->reset()
			->setCreateAbsoluteUri(TRUE)
			->uriFor('index', array(), 'Backend\Backend', 'TYPO3.Neos');
		return json_encode(array(
			'contentModuleUri' => $contentModuleUri,
			'sites' => $this->menuHelper->buildSiteList($this->controllerContext),
			'modules' => $this->menuHelper->buildModuleList($this->controllerContext)
		));
	}

}
