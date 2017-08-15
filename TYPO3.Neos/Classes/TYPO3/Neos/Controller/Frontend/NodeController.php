<?php
namespace TYPO3\Neos\Controller\Frontend;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use TYPO3\Flow\Session\SessionInterface;
use TYPO3\Neos\Controller\Exception\NodeNotFoundException;
use TYPO3\Neos\Controller\Exception\UnresolvableShortcutException;
use TYPO3\Neos\Domain\Model\UserInterfaceMode;
use TYPO3\Neos\Domain\Service\NodeShortcutResolver;
use TYPO3\Neos\View\TypoScriptView;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

/**
 * Controller for displaying nodes in the frontend
 *
 * @Flow\Scope("singleton")
 */
class NodeController extends ActionController
{
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
    protected $defaultViewObjectName = TypoScriptView::class;

    /**
     * @var TypoScriptView
     */
    protected $view;

    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

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
    public function showAction(NodeInterface $node = null)
    {
        if ($node === null) {
            throw new NodeNotFoundException('The requested node does not exist or isn\'t accessible to the current user', 1430218623);
        }

        $inBackend = $node->getContext()->isInBackend();

        if ($node->getNodeType()->isOfType('TYPO3.Neos:Shortcut') && !$inBackend) {
            $this->handleShortcutNode($node);
        }

        $this->view->assign('value', $node);

        if ($inBackend) {
            $this->overrideViewVariablesFromInternalArguments();

            /** @var UserInterfaceMode $renderingMode */
            $renderingMode = $node->getContext()->getCurrentRenderingMode();
            $this->response->setHeader('Cache-Control', 'no-cache');
            if ($renderingMode !== null) {
                // Deprecated TypoScript context variable from version 2.0.
                $this->view->assign('editPreviewMode', $renderingMode->getTypoScriptPath());
            }
            if (!$this->view->canRenderWithNodeAndPath()) {
                $this->view->setTypoScriptPath('rawContent');
            }
        }

        if ($this->session->isStarted() && $inBackend) {
            $this->session->putData('lastVisitedNode', $node->getContextPath());
        }
    }

    /**
     * Checks if the optionally given node context path, affected node context path and typoscript path are set
     * and overrides the rendering to use those. Will also add a "X-Neos-AffectedNodePath" header in case the
     * actually affected node is different from the one routing resolved.
     * This is used in out of band rendering for the backend.
     *
     * @return void
     * @throws NodeNotFoundException
     */
    protected function overrideViewVariablesFromInternalArguments()
    {
        if (($nodeContextPath = $this->request->getInternalArgument('__nodeContextPath')) !== null) {
            $node = $this->propertyMapper->convert($nodeContextPath, NodeInterface::class);
            if (!$node instanceof NodeInterface) {
                throw new NodeNotFoundException(sprintf('The node with context path "%s" could not be resolved', $nodeContextPath), 1437051934);
            }
            $this->view->assign('value', $node);
        }

        if (($affectedNodeContextPath = $this->request->getInternalArgument('__affectedNodeContextPath')) !== null) {
            $this->response->setHeader('X-Neos-AffectedNodePath', $affectedNodeContextPath);
        }

        if (($typoScriptPath = $this->request->getInternalArgument('__typoScriptPath')) !== null) {
            $this->view->setTypoScriptPath($typoScriptPath);
        }
    }

    /**
     * Handles redirects to shortcut targets in live rendering.
     *
     * @param NodeInterface $node
     * @return void
     * @throws NodeNotFoundException|UnresolvableShortcutException
     */
    protected function handleShortcutNode(NodeInterface $node)
    {
        $resolvedNode = $this->nodeShortcutResolver->resolveShortcutTarget($node);
        if ($resolvedNode === null) {
            throw new NodeNotFoundException(sprintf('The shortcut node target of node "%s" could not be resolved', $node->getPath()), 1430218730);
        } elseif (is_string($resolvedNode)) {
            $this->redirectToUri($resolvedNode);
        } elseif ($resolvedNode instanceof NodeInterface && $resolvedNode === $node) {
            throw new NodeNotFoundException('The requested node does not exist or isn\'t accessible to the current user', 1502793585229);
        } elseif ($resolvedNode instanceof NodeInterface) {
            $this->redirect('show', null, null, ['node' => $resolvedNode]);
        } else {
            throw new UnresolvableShortcutException(sprintf('The shortcut node target of node "%s" resolves to an unsupported type "%s"', $node->getPath(), is_object($resolvedNode) ? get_class($resolvedNode) : gettype($resolvedNode)), 1430218738);
        }
    }
}
