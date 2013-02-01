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
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * Shows the specified node and takes visibility and access restrictions into
	 * account.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @return string View output for the specified node
	 */
	public function showAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node) {
		if ($node->getContext()->getWorkspace()->getName() !== 'live') {
				// TODO: Introduce check if workspace is visible or accessible to the user
			try {
				$this->accessDecisionManager->decideOnResource('TYPO3_Neos_Backend_BackendController');
				$this->nodeRepository->getContext()->setInvisibleContentShown(TRUE);
				$this->nodeRepository->getContext()->setRemovedContentShown(TRUE);
			} catch (\TYPO3\Flow\Security\Exception\AccessDeniedException $exception) {
				$this->redirect('index', 'Login');
			}
		}
		if ($this->isWireframeModeEnabled($node)) {
			$this->forward('showWireframe', NULL, NULL, array('node' => $node->getPath()));
		}
		if (!$node->isAccessible()) {
			try {
				$this->authenticationManager->authenticate();
			} catch (\Exception $exception) {}
		}
		if (!$node->isAccessible() && !$this->nodeRepository->getContext()->isInaccessibleContentShown()) {
			$this->throwStatus(403);
		}
		if (!$node->isVisible() && !$this->nodeRepository->getContext()->isInvisibleContentShown()) {
			$this->throwStatus(404);
		}
		if ($node->getNodeType()->isOfType('TYPO3.Neos.ContentTypes:Shortcut')) {
			if ($node->getProperty('targetNode') !== NULL) {
				$node = $node->getNode($node->getProperty('targetNode'));
			} else {
				while ($node->getNodeType()->isOfType('TYPO3.Neos.ContentTypes:Shortcut')) {
					$childNodes = $node->getChildNodes('TYPO3.Neos.ContentTypes:Folder');
					$node = current($childNodes);
				}
			}
			$this->redirect('show', NULL, NULL, array('node' => $node));
		}

		$this->nodeRepository->getContext()->setCurrentNode($node);
		$this->view->assign('value', $node);

		$this->response->setHeader('Cache-Control', 'public, s-maxage=600', FALSE);
	}

	/**
	 * Shows the specified node and takes visibility and access restrictions into
	 * account.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @return string View output for the specified node
	 */
	public function showWireframeAction(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node) {
		if (!$node->isAccessible()) {
			try {
				$this->authenticationManager->authenticate();
			} catch (\Exception $exception) {
			}
		}
		if (!$node->isAccessible() && !$this->nodeRepository->getContext()->isInaccessibleContentShown()) {
			$this->throwStatus(403);
		}
		if (!$node->isVisible() && !$this->nodeRepository->getContext()->isInvisibleContentShown()) {
			$this->throwStatus(404);
		}
		if ($node->getNodeType()->isOfType('TYPO3.Neos.ContentTypes:Shortcut')) {
			$this->view->assign('wireframeMode', $node);
		}

		$this->nodeRepository->getContext()->setCurrentNode($node);
		$this->view->assign('value', $node);

		$this->view->setTypoScriptPath('wireframeMode');

		$this->response->setHeader('Cache-Control', 'public, s-maxage=600', FALSE);
	}

	/**
	 * Decide if wireframe mode should be enabled.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @return boolean
	 */
	protected function isWireframeModeEnabled(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node) {
		if ($this->securityContext->getParty() !== NULL) {
			try {
				$this->accessDecisionManager->decideOnResource('TYPO3_Neos_Backend_BackendController');
				if (!$this->view->canRenderWithNodeAndPath($node, $this->view->getTypoScriptPath())) {
					return TRUE;
				}
				$user = $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');
				return $user->getPreferences()->get('contentEditing.wireframeMode') ? TRUE : FALSE;
			} catch (\Exception $e) {}
		}
		return FALSE;
	}
}
?>