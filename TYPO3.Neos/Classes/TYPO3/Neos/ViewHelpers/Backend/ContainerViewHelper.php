<?php
namespace TYPO3\Neos\ViewHelpers\Backend;

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
 * ViewHelper for the backend 'container'. Renders the required HTML to integrate
 * the Neos backend into a website.
 */
class ContainerViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @var \TYPO3\Neos\Controller\Backend\MenuHelper
	 * @Flow\Inject
	 */
	protected $menuHelper;

	/**
	 * @var \TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface
	 * @Flow\Inject
	 */
	protected $accessDecisionManager;

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
		try {
			$this->accessDecisionManager->decideOnResource('TYPO3_Neos_Backend_GeneralAccess');
		} catch (\TYPO3\Flow\Security\Exception\AccessDeniedException $e) {
			return '';
		}

		$view = new \TYPO3\Fluid\View\StandaloneView($this->controllerContext->getRequest());
		$view->setTemplatePathAndFilename('resource://TYPO3.Neos/Private/Templates/Backend/Content/Container.html');
		$view->setPartialRootPath('resource://TYPO3.Neos/Private/Partials');

		$user = $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');

		$sites = $this->menuHelper->buildSiteList($this->controllerContext);

		$view->assignMultiple(array(
			'node' => $node,
			'modules' => $this->settings['modules'],
			'sites' => $sites,
			'user' => $user,
			'fluidTemplateTsObject' => $this->templateVariableContainer->get('fluidTemplateTsObject')

		));

		return $view->render();
	}

}
?>