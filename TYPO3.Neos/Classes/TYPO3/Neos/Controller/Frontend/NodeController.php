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
use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use TYPO3\Flow\Session\SessionInterface;
use TYPO3\Neos\Controller\Exception\NodeNotFoundException;
use TYPO3\Neos\Controller\Exception\UnresolvableShortcutException;
use TYPO3\Neos\Domain\Model\UserInterfaceMode;
use TYPO3\Neos\Domain\Service\NodeShortcutResolver;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

/**
 * Controller for displaying nodes in the frontend
 *
 * @Flow\Scope("singleton")
 */
class NodeController extends ActionController {

	/**
	 * @Flow\Inject
	 * @var ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var SessionInterface
	 */
	protected $session;

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
	 * @Flow\Inject
	 * @var PrivilegeManagerInterface
	 */
	protected $privilegeManager;

	/**
	 * Shows the specified node and takes visibility and access restrictions into
	 * account.
	 *
	 * @param NodeInterface $node
	 * @return string View output for the specified node
	 * @Flow\SkipCsrfProtection We need to skip CSRF protection here because this action could be called with unsafe requests from widgets or plugins that are rendered on the node - For those the CSRF token is validated on the sub-request, so it is safe to be skipped here
	 * @Flow\IgnoreValidation("node")
	 * @throws NodeNotFoundException
	 */
	public function showAction(NodeInterface $node = NULL) {
		if ($node === NULL) {
			throw new NodeNotFoundException('The requested node does not exist or isn\'t accessible to the current user', 1430218623);
		}
		if (!$node->getContext()->isLive() && !$this->privilegeManager->isPrivilegeTargetGranted('TYPO3.Neos:Backend.GeneralAccess')) {
			$this->redirect('index', 'Login', NULL, array('unauthorized' => TRUE));
		}

		$inBackend = $node->getContext()->isInBackend();

		if ($node->getNodeType()->isOfType('TYPO3.Neos:Shortcut') && !$inBackend) {
			$this->handleShortcutNode($node);
		}

		$this->view->assign('value', $node);

		if ($inBackend) {
			/** @var UserInterfaceMode $renderingMode */
			$renderingMode = $node->getContext()->getCurrentRenderingMode();
			$this->response->setHeader('Cache-Control', 'no-cache');
			if ($renderingMode !== NULL) {
				// Deprecated TypoScript context variable from version 2.0.
				$this->view->assign('editPreviewMode', $renderingMode->getTypoScriptPath());
			}
			if (!$this->view->canRenderWithNodeAndPath()) {
				$this->view->setTypoScriptPath('rawContent');
			}
		}

		if ($this->session->isStarted() && $inBackend) {
			$this->session->putData('lastVisitedNode', $node->getIdentifier());
		}
	}

	/**
	 * Handles redirects to shortcut targets in live rendering.
	 *
	 * @param NodeInterface $node
	 * @return void
	 * @throws NodeNotFoundException|UnresolvableShortcutException
	 */
	protected function handleShortcutNode(NodeInterface $node) {
		$resolvedNode = $this->nodeShortcutResolver->resolveShortcutTarget($node);
		if ($resolvedNode === NULL) {
			throw new NodeNotFoundException(sprintf('The shortcut node target of node "%s" could not be resolved', $node->getPath()), 1430218730);
		} elseif (is_string($resolvedNode)) {
			$this->redirectToUri($resolvedNode);
		} elseif ($resolvedNode instanceof NodeInterface) {
			$this->redirect('show', NULL, NULL, array('node' => $resolvedNode));
		} else {
			throw new UnresolvableShortcutException(sprintf('The shortcut node target of node "%s" resolves to an unsupported type "%s"', $node->getPath(), is_object($resolvedNode) ? get_class($resolvedNode) : gettype($resolvedNode)), 1430218738);
		}
	}
}
