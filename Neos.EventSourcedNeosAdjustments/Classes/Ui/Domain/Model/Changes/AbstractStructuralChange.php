<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Changes;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorInterface;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorManager;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\Nodes;
use Neos\EventSourcedNeosAdjustments\FusionCaching\ContentCacheFlusher;
use Neos\EventSourcedNeosAdjustments\Ui\ContentRepository\Service\NodeService;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\AbstractChange;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\ReloadDocument;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\RenderContentOutOfBand;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\UpdateNodeInfo;
use Neos\EventSourcedNeosAdjustments\Ui\Fusion\Helper\NodeInfoHelper;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Ui\Domain\Model\RenderedNodeDomAddress;

/**
 * A change that performs structural actions like moving or creating nodes
 */
abstract class AbstractStructuralChange extends AbstractChange
{
    /**
     * The node dom address for the parent node of the created node
     *
     * @var RenderedNodeDomAddress
     */
    protected $parentDomAddress;

    /**
     * The node dom address for the referenced sibling node of the created node
     *
     * @var RenderedNodeDomAddress
     */
    protected $siblingDomAddress;

    /**
     * @Flow\Inject
     * @var NodeService
     */
    protected $nodeService;

    /**
     * @Flow\Inject
     * @var ContentCacheFlusher
     */
    protected $contentCacheFlusher;

    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @var NodeInterface
     */
    protected $cachedSiblingNode = null;

    /**
     * Used when creating nodes within non-default tree preset
     *
     * @var string|null
     */
    protected $baseNodeType = null;

    /**
     * Set the baseNodeType
     *
     * @param string $baseNodeType
     */
    public function setBaseNodeType(string $baseNodeType): void
    {
        $this->baseNodeType = $baseNodeType;
    }

    /**
     * Get the baseNodeType
     *
     * @return string|null
     */
    public function getBaseNodeType(): ?string
    {
        return $this->baseNodeType;
    }

    /**
     * Get the insertion mode (before|after|into) that is represented by this change
     *
     * @return string
     */
    abstract public function getMode();

    /**
     * Set the parent node dom address
     *
     * @param RenderedNodeDomAddress $parentDomAddress
     * @return void
     */
    public function setParentDomAddress(RenderedNodeDomAddress $parentDomAddress = null)
    {
        $this->parentDomAddress = $parentDomAddress;
    }

    /**
     * Get the DOM address of the closest RENDERED node in the DOM tree.
     *
     * DOES NOT HAVE TO BE THE PARENT NODE!
     *
     * @return RenderedNodeDomAddress
     */
    public function getParentDomAddress()
    {
        return $this->parentDomAddress;
    }

    /**
     * Set the sibling node dom address
     *
     * @param RenderedNodeDomAddress $siblingDomAddress
     * @return void
     */
    public function setSiblingDomAddress(RenderedNodeDomAddress $siblingDomAddress = null)
    {
        $this->siblingDomAddress = $siblingDomAddress;
    }

    /**
     * Get the sibling node dom address
     *
     * @return RenderedNodeDomAddress
     */
    public function getSiblingDomAddress()
    {
        return $this->siblingDomAddress;
    }

    /**
     * Get the sibling node
     *
     * @return NodeInterface
     */
    public function getSiblingNode()
    {
        if ($this->siblingDomAddress === null) {
            return null;
        }

        if ($this->cachedSiblingNode === null) {
            $this->cachedSiblingNode = $this->nodeService->getNodeFromContextPath(
                $this->siblingDomAddress->getContextPath()
            );
        }

        return $this->cachedSiblingNode;
    }

    /**
     * Perform finish tasks - needs to be called from inheriting class on `apply`
     *
     * @param NodeInterface $node
     * @return void
     */
    protected function finish(NodeInterface $node)
    {
        $updateNodeInfo = new UpdateNodeInfo();
        $updateNodeInfo->setNode($node);
        $updateNodeInfo->recursive();

        $updateParentNodeInfo = new UpdateNodeInfo();
        $nodeAccessor = $this->nodeAccessorManager->accessorFor($node->getContentStreamIdentifier(), $node->getDimensionSpacePoint(), VisibilityConstraints::withoutRestrictions());
        $parentNode = $nodeAccessor->findParentNode($node);
        $updateParentNodeInfo->setNode($parentNode);
        if ($this->baseNodeType) {
            $updateParentNodeInfo->setBaseNodeType($this->baseNodeType);
        }

        $this->feedbackCollection->add($updateNodeInfo);
        $this->feedbackCollection->add($updateParentNodeInfo);

        $this->updateWorkspaceInfo();

        if ($node->getNodeType()->isOfType('Neos.Neos:Content') && ($this->getParentDomAddress() || $this->getSiblingDomAddress())) {

            // we can ONLY render out of band if:
            // 1) the parent of our new (or copied or moved) node is a ContentCollection; so we can directly update an element of this content collection
            if ($parentNode->getNodeType()->isOfType('Neos.Neos:ContentCollection') &&

                // 2) the parent DOM address (i.e. the closest RENDERED node in DOM is actually the ContentCollection; and
                //    no other node in between
                $this->getParentDomAddress() &&
                $this->getParentDomAddress()->getFusionPath() &&
                $this->getParentDomAddress()->getContextPath() === $this->nodeAddressFactory->createFromNode($nodeAccessor->findParentNode($node))->serializeForUri()
            ) {
                $renderContentOutOfBand = new RenderContentOutOfBand();
                $renderContentOutOfBand->setNode($node);
                $renderContentOutOfBand->setParentDomAddress($this->getParentDomAddress());
                $renderContentOutOfBand->setSiblingDomAddress($this->getSiblingDomAddress());
                $renderContentOutOfBand->setMode($this->getMode());

                $this->feedbackCollection->add($renderContentOutOfBand);
            } else {
                $reloadDocument = new ReloadDocument();
                $reloadDocument->setNode($node);

                $this->feedbackCollection->add($reloadDocument);
            }
        }
    }

    protected function nodeAccessorFor(NodeInterface $node): NodeAccessorInterface
    {
        return $this->nodeAccessorManager->accessorFor(
            $node->getContentStreamIdentifier(),
            $node->getDimensionSpacePoint(),
            $node->getVisibilityConstraints()
        );
    }

    protected function findParentNode(NodeInterface $node): ?NodeInterface
    {
        return $this->nodeAccessorFor($node)->findParentNode($node);
    }

    protected function findChildNodes(NodeInterface $node): Nodes
    {
        return $this->nodeAccessorFor($node)->findChildNodes($node);
    }

    protected function isNodeTypeAllowedAsChildNode(NodeInterface $node, NodeType $nodeType)
    {
        $nodeAccessor = $this->nodeAccessorManager->accessorFor($node->getContentStreamIdentifier(), $node->getDimensionSpacePoint(), VisibilityConstraints::withoutRestrictions());
        if (NodeInfoHelper::isAutoCreated($node, $nodeAccessor)) {
            return $nodeAccessor->findParentNode($node)->getNodeType()->allowsGrandchildNodeType((string)$node->getNodeName(), $nodeType);
        } else {
            return $node->getNodeType()->allowsChildNodeType($nodeType);
        }
    }
}
