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
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Neos\Domain\Service\NodeShortcutResolver;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Controller for displaying nodes in the frontend
 *
 * @Flow\Scope("singleton")
 */
class NodeController extends ActionController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

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
	 * @var \TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface
	 */
	protected $privilegeManager;

	/**
	 * @Flow\Inject
	 * @var NodeShortcutResolver
	 */
	protected $nodeShortcutResolver;

	/**
	 * @var string
	 */
	protected $defaultViewObjectName = 'TYPO3\Neos\View\TypoScriptView';

	/**
	 * @var \TYPO3\Neos\View\TypoScriptView
	 */
	protected $view;

	/**
	 * Shows the specified node and takes visibility and access restrictions into
	 * account.
	 *
	 * @param Node $node
	 * @return string View output for the specified node
	 * @Flow\SkipCsrfProtection We need to skip CSRF protection here because this action could be called with unsafe requests from widgets or plugins that are rendered on the node - For those the CSRF token is validated on the sub-request, so it is safe to be skipped here
	 * @Flow\IgnoreValidation("node")
	 */
	public function showAction(Node $node) {
		if ($node->getContext()->getWorkspace()->getName() !== 'live') {
				// TODO: Introduce check if workspace is visible or accessible to the user
			if ($this->hasAccessToBackend() === FALSE) {
				$this->redirect('index', 'Login', NULL, array('unauthorized' => TRUE));
			}
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
				$node = $this->nodeShortcutResolver->resolveShortcutTarget($node);
				if ($node === NULL) {
					$this->throwStatus(404);
				} elseif (is_string($node)) {
					$this->redirectToUri($node);
				} elseif ($node instanceof NodeInterface) {
					$this->redirect('show', NULL, NULL, array('node' => $node));
				} else {
					$this->throwStatus(500, 'Shortcut resolved to an unsupported type.');
				}
			}
		}

		$this->view->assign('value', $node);

		if ($node->getContext()->getWorkspaceName() !== 'live' && $this->hasAccessToBackend()) {
			$this->response->setHeader('Cache-Control', 'no-cache');

			$editPreviewMode = $this->getEditPreviewModeTypoScriptRenderingPath($node);
			if ($editPreviewMode !== NULL) {
				$this->view->assign('editPreviewMode', $editPreviewMode);
			} else {
				if (!$this->view->canRenderWithNodeAndPath()) {
					$this->view->setTypoScriptPath('rawContent');
				}
			}
		}

		if ($this->session->isStarted() && $this->securityContext->isInitialized() && $this->hasAccessToBackend()) {
			$this->session->putData('lastVisitedNode', $node->getIdentifier());
		}
	}

	/**
	 * Return a specific rendering mode if set.
	 *
	 * @return string|NULL
	 */
	protected function getEditPreviewModeTypoScriptRenderingPath() {
		if ($this->securityContext->getParty() === NULL || !$this->hasAccessToBackend()) {
			return NULL;
		}
		/** @var \TYPO3\Neos\Domain\Model\User $user */
		$user = $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');
		$editPreviewMode = $user->getPreferences()->get('contentEditing.editPreviewMode');
		if ($editPreviewMode === NULL) {
			return NULL;
		}

		$editPreviewModeTypoScriptRenderingPath = Arrays::getValueByPath($this->settings, 'userInterface.editPreviewModes.' . $editPreviewMode . '.typoScriptRenderingPath');
		return strlen($editPreviewModeTypoScriptRenderingPath) > 0 ? $editPreviewModeTypoScriptRenderingPath : NULL;
	}

	/**
	 * @return boolean
	 */
	protected function hasAccessToBackend() {
		return $this->privilegeManager->isPrivilegeTargetGranted('TYPO3.Neos:Backend.GeneralAccess');
	}

}
