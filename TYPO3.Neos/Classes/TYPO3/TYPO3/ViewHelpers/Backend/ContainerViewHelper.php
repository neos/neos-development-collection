<?php
namespace TYPO3\TYPO3\ViewHelpers\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * ViewHelper for the backend 'container'. Renders the required HTML to integrate
 * the Phoenix backend into a website.
 */
class ContainerViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return string
	 */
	public function render(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$view = new \TYPO3\Fluid\View\StandaloneView($this->controllerContext->getRequest());
		$view->setTemplatePathAndFilename('resource://TYPO3.TYPO3/Private/Templates/Backend/Content/Container.html');

		$view->assignMultiple(array(
			'node' => $node,
			'modules' => $this->settings['modules']
		));

		return $view->render();
	}
}
?>