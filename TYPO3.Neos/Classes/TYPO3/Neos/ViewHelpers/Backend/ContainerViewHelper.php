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
	 * @var \TYPO3\Neos\Domain\Repository\SiteRepository
	 * @Flow\Inject
	 */
	protected $siteRepository;

	/**
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 * @Flow\Inject
	 */
	protected $propertyMapper;

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
		$view->setTemplatePathAndFilename('resource://TYPO3.Neos/Private/Templates/Backend/Content/Container.html');
		$view->setPartialRootPath('resource://TYPO3.Neos/Private/Partials');

		$user = $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');

		$sites = array();
		foreach ($this->siteRepository->findAll() as $site) {
			$siteNode = $this->propertyMapper->convert('/sites/' . $site->getNodeName(), 'TYPO3\TYPO3CR\Domain\Model\NodeInterface');
			$uri = $this->controllerContext->getUriBuilder()
				->reset()
				->setCreateAbsoluteUri(TRUE)
				->uriFor('show', array('node' => $siteNode), 'Frontend\Node', 'TYPO3.Neos');
			$sites[] = array(
				'name' => $site->getName(),
				'nodeName' => $site->getNodeName(),
				'uri' => $uri,
				'active' => stristr($uri, $_SERVER['HTTP_HOST']) !== FALSE ? TRUE : FALSE
			);
		}

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