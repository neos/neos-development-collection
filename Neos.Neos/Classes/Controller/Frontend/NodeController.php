<?php
namespace Neos\Neos\Controller\Frontend;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\NoSuchArgumentException;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Session\Exception\SessionNotStartedException;
use Neos\Flow\Session\SessionInterface;
use Neos\Neos\Controller\Exception\NodeNotFoundException;
use Neos\Neos\Controller\Exception\UnresolvableShortcutException;
use Neos\Neos\Domain\Service\NodeShortcutResolver;
use Neos\Neos\Exception as NeosException;
use Neos\Neos\TypeConverter\NodeConverter;
use Neos\Neos\View\FusionView;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

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
    protected $defaultViewObjectName = FusionView::class;

    /**
     * @var FusionView
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
     * Allow invisible nodes to be redirected to
     *
     * @return void
     */
    protected function initializeShowAction(): void
    {
        if ($this->arguments->hasArgument('node')
            && $this->request->hasArgument('showInvisible')
            && (bool)$this->request->getArgument('showInvisible')
            && $this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess')
        ) {
            $this->arguments->getArgument('node')->getPropertyMappingConfiguration()->setTypeConverterOption(NodeConverter::class, NodeConverter::INVISIBLE_CONTENT_SHOWN, true);
        }
    }

    /**
     * Shows the specified node and takes visibility and access restrictions into
     * account.
     *
     * @param NodeInterface $node
     * @return string View output for the specified node
     * @throws NodeNotFoundException | UnresolvableShortcutException | NeosException
     * We need to skip CSRF protection here because this action could be called with unsafe requests from widgets or plugins that are rendered on the node - For those the CSRF token is validated on the sub-request, so it is safe to be skipped here
     * @Flow\SkipCsrfProtection
     * @Flow\IgnoreValidation("node")
     */
    public function showAction(NodeInterface $node = null)
    {
        if ($node === null || !$node->getContext()->isLive()) {
            throw new NodeNotFoundException('The requested node does not exist or isn\'t accessible to the current user', 1430218623);
        }

        if ($node->getNodeType()->isOfType('Neos.Neos:Shortcut')) {
            $this->handleShortcutNode($node);
        }

        $this->view->assign('value', $node);
    }

    /**
     * Allow invisible nodes to be previewed
     *
     * @return void
     * @throws NoSuchArgumentException
     */
    protected function initializePreviewAction(): void
    {
        if ($this->arguments->hasArgument('node') && $this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess')) {
            $this->arguments->getArgument('node')->getPropertyMappingConfiguration()->setTypeConverterOption(NodeConverter::class, NodeConverter::INVISIBLE_CONTENT_SHOWN, true);
        }
    }

    /**
     * Previews a node that is not live (i.e. for the Backend Preview & Edit Mode)
     *
     * @param NodeInterface $node
     * @return string View output for the specified node
     * @throws NeosException | NodeNotFoundException | SessionNotStartedException | UnresolvableShortcutException
     * @Flow\IgnoreValidation("node")
     */
    public function previewAction(NodeInterface $node = null)
    {
        if ($node === null) {
            throw new NodeNotFoundException('The requested node does not exist or isn\'t accessible to the current user', 1430218623);
        }

        $inBackend = $node->getContext()->isInBackend();

        if ($node->getNodeType()->isOfType('Neos.Neos:Shortcut') && !$inBackend) {
            $this->handleShortcutNode($node);
        }

        $this->view->assign('value', $node);

        if ($inBackend) {
            $this->overrideViewVariablesFromInternalArguments();
            $this->response->setHttpHeader('Cache-Control', 'no-cache');
            if (!$this->view->canRenderWithNodeAndPath()) {
                $this->view->setFusionPath('rawContent');
            }
            if ($this->session->isStarted()) {
                $this->session->putData('lastVisitedNode', $node->getContextPath());
            }
        }
    }

    /**
     * Checks if the optionally given node context path, affected node context path and Fusion path are set
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
            $this->response->setHttpHeader('X-Neos-AffectedNodePath', $affectedNodeContextPath);
        }

        if (($fusionPath = $this->request->getInternalArgument('__fusionPath')) !== null) {
            $this->view->setFusionPath($fusionPath);
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
            throw new NodeNotFoundException('The requested node does not exist or isn\'t accessible to the current user', 1502793585);
        } elseif ($resolvedNode instanceof NodeInterface) {
            $this->redirect('show', null, null, ['node' => $resolvedNode]);
        } else {
            throw new UnresolvableShortcutException(sprintf('The shortcut node target of node "%s" resolves to an unsupported type "%s"', $node->getPath(), is_object($resolvedNode) ? get_class($resolvedNode) : gettype($resolvedNode)), 1430218738);
        }
    }
}
