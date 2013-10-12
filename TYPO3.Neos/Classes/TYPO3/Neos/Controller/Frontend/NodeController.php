<?php
namespace TYPO3\Neos\Controller\Frontend;

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
 * Controller for displaying nodes in the frontend
 *
 * @Flow\Scope("singleton")
 */
class NodeController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * @var array
	 */
	protected $supportedFormats = array('html');

	/**
	 * @var array
	 */
	protected $defaultViewObjectName = 'TYPO3\Neos\View\TypoScriptView';

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Session\SessionInterface
	 */
	protected $session;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * Shows the specified node and takes visibility and access restrictions into
	 * account.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Node $node
	 * @return string View output for the specified node
	 */
	public function showAction(\TYPO3\TYPO3CR\Domain\Model\Node $node) {
		if ($node->getContext()->getWorkspace()->getName() !== 'live') {
				// TODO: Introduce check if workspace is visible or accessible to the user
			if ($this->hasAccessToBackend()) {
				$contextProperties = $node->getContext()->getProperties();
				$contextProperties['invisibleContentShown'] = TRUE;
				$contextProperties['removedContentShown'] = TRUE;
				$contextProperties['invisibleContentShown'] = TRUE;
				$context = $this->contextFactory->create($contextProperties);
				$node = $context->getNode($node->getPath());
			} else {
				$this->redirect('index', 'Login');
			}
		}
		if ($this->isWireframeModeEnabled($node)) {
			$this->forward('showWireframe', NULL, NULL, array('node' => $node->getContextPath()));
		}
		if (!$node->isAccessible()) {
			try {
				$this->authenticationManager->authenticate();
			} catch (\Exception $exception) {}
		}
		if (!$node->isAccessible() && !$node->getContext()->isInaccessibleContentShown()) {
			$this->throwStatus(403);
		}
		if (!$node->isVisible() && !$node->getContext()->isInvisibleContentShown()) {
			$this->throwStatus(404);
		}

		if ($node->getNodeType()->isOfType('TYPO3.Neos:Shortcut')) {
			if (!$this->hasAccessToBackend() || $node->getContext()->getWorkspace()->getName() === 'live') {
				while ($node->getNodeType()->isOfType('TYPO3.Neos:Shortcut')) {
					switch ($node->getProperty('targetMode')) {
						case 'selectedNode':
							$node = $node->getNode($node->getProperty('targetNode'));
						break;
						case 'parentNode':
							$node = $node->getParent();
						break;
						case 'firstChildNode':
						default:
							$childNodes = $node->getChildNodes('TYPO3.Neos:Document');
							$node = current($childNodes);
					}
				}
				$this->redirect('show', NULL, NULL, array('node' => $node));
			}
		}

		$this->view->assign('value', $node);

		$this->response->setHeader('Cache-Control', 'public, s-maxage=600', FALSE);

		if ($this->securityContext->isInitialized() && $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User') !== NULL) {
			$lastVisitedUri = $this->request->getHttpRequest()->getUri();
			$this->session->putData('lastVisitedUri', (string)$lastVisitedUri);
		}
	}

	/**
	 * Shows the specified node and takes visibility and access restrictions into
	 * account.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Node $node
	 * @return string View output for the specified node
	 */
	public function showWireframeAction(\TYPO3\TYPO3CR\Domain\Model\Node $node) {
		if (!$node->isAccessible()) {
			try {
				$this->authenticationManager->authenticate();
			} catch (\Exception $exception) {
			}
		}
		if (!$node->isAccessible() && !$node->getContext()->isInaccessibleContentShown()) {
			$this->throwStatus(403);
		}
		if (!$node->isVisible() && !$node->getContext()->isInvisibleContentShown()) {
			$this->throwStatus(404);
		}
		if ($node->getNodeType()->isOfType('TYPO3.Neos.NodeTypes:Shortcut')) {
			$this->view->assign('wireframeMode', $node);
		}

		$this->view->assign('value', $node);

		$this->view->setTypoScriptPath('wireframeMode');

		$this->response->setHeader('Cache-Control', 'public, s-maxage=600', FALSE);
	}

	/**
	 * Decide if wireframe mode should be enabled.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Node $node
	 * @return boolean
	 */
	protected function isWireframeModeEnabled(\TYPO3\TYPO3CR\Domain\Model\Node $node) {
		if ($this->securityContext->getParty() !== NULL) {
			if ($this->hasAccessToBackend()) {
				if (!$this->view->canRenderWithNodeAndPath($node, $this->view->getTypoScriptPath())) {
					return TRUE;
				}
				$user = $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');
				return $user->getPreferences()->get('contentEditing.wireframeMode') ? TRUE : FALSE;
			}
		}
		return FALSE;
	}

	/**
	 * @return boolean
	 */
	protected function hasAccessToBackend() {
		return $this->accessDecisionManager->hasAccessToResource('TYPO3_Neos_Backend_GeneralAccess');
	}

}
