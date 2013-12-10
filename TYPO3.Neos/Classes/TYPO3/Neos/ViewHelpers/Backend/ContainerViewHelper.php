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
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Neos\Exception as NeosException;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\TypoScriptObjects\Helpers\FluidView;
use TYPO3\TypoScript\TypoScriptObjects\Helpers\TypoScriptAwareViewInterface;

/**
 * ViewHelper for the backend 'container'. Renders the required HTML to integrate
 * the Neos backend into a website.
 */
class ContainerViewHelper extends AbstractViewHelper {

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
	 * @param NodeInterface $node
	 * @return string
	 * @throws NeosException
	 */
	public function render(NodeInterface $node) {
		if ($this->accessDecisionManager->hasAccessToResource('TYPO3_Neos_Backend_GeneralAccess') === FALSE) {
			return '';
		}

		$view = $this->viewHelperVariableContainer->getView();
		if (!$view instanceof TypoScriptAwareViewInterface) {
			throw new NeosException('This ViewHelper can only be used inside a view implementing the TypoScriptAwareInterface, such as inside a TYPO3.TypoScript:Template implementation.', 1386554873);
		}

		/** @var $actionRequest ActionRequest */
		$actionRequest = $this->controllerContext->getRequest();
		$innerView =  new FluidView($view->getTypoScriptObject(), $actionRequest);
		$innerView->setTemplatePathAndFilename('resource://TYPO3.Neos/Private/Templates/Backend/Content/Container.html');
		$innerView->setPartialRootPath('resource://TYPO3.Neos/Private/Partials');

		$user = $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');

		$sites = $this->menuHelper->buildSiteList($this->controllerContext);

		$innerView->assignMultiple(array(
			'node' => $node,
			'modules' => $this->settings['modules'],
			'sites' => $sites,
			'user' => $user
		));

		return $innerView->render();
	}

}
