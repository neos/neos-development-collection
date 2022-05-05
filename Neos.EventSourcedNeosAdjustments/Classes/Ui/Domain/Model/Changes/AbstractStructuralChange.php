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

use Neos\ContentRepository\NodeAccess\NodeAccessor\NodeAccessorInterface;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Projection\Content\Nodes;
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
     */
    protected ?RenderedNodeDomAddress $parentDomAddress = null;

    /**
     * The node dom address for the referenced sibling node of the created node
     */
    protected ?RenderedNodeDomAddress $siblingDomAddress = null;

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
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    protected ?NodeInterface $cachedSiblingNode = null;

    /**
     * Used when creating nodes within non-default tree preset
     */
    protected ?string $baseNodeType = null;

    public function setBaseNodeType(string $baseNodeType): void
    {
        $this->baseNodeType = $baseNodeType;
    }

    public function getBaseNodeType(): ?string
    {
        return $this->baseNodeType;
    }

    /**
     * Get the insertion mode (before|after|into) that is represented by this change
     */
    abstract public function getMode(): string;

    public function setParentDomAddress(RenderedNodeDomAddress $parentDomAddress = null): void
    {
        $this->parentDomAddress = $parentDomAddress;
    }

    /**
     * Get the DOM address of the closest RENDERED node in the DOM tree.
     *
     * DOES NOT HAVE TO BE THE PARENT NODE!
     */
    public function getParentDomAddress(): ?RenderedNodeDomAddress
    {
        return $this->parentDomAddress;
    }

    public function setSiblingDomAddress(RenderedNodeDomAddress $siblingDomAddress = null): void
    {
        $this->siblingDomAddress = $siblingDomAddress;
    }

    public function getSiblingDomAddress(): ?RenderedNodeDomAddress
    {
        return $this->siblingDomAddress;
    }

    /**
     * Get the sibling node
     */
    public function getSiblingNode(): ?NodeInterface
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
        $this->feedbackCollection->add($updateNodeInfo);

        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $node->getContentStreamIdentifier(),
            $node->getDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        );
        $parentNode = $nodeAccessor->findParentNode($node);
        if ($parentNode) {
            $updateParentNodeInfo = new UpdateNodeInfo();
            $updateParentNodeInfo->setNode($parentNode);
            if ($this->baseNodeType) {
                $updateParentNodeInfo->setBaseNodeType($this->baseNodeType);
            }
            $this->feedbackCollection->add($updateParentNodeInfo);
        }

        $this->updateWorkspaceInfo();

        if ($node->getNodeType()->isOfType('Neos.Neos:Content')
            && ($this->getParentDomAddress() || $this->getSiblingDomAddress())) {
            // we can ONLY render out of band if:
            // 1) the parent of our new (or copied or moved) node is a ContentCollection;
            // so we can directly update an element of this content collection
            if ($parentNode && $parentNode->getNodeType()->isOfType('Neos.Neos:ContentCollection') &&
                // 2) the parent DOM address (i.e. the closest RENDERED node in DOM is actually the ContentCollection;
                // and no other node in between
                $this->getParentDomAddress() &&
                $this->getParentDomAddress()->getFusionPath() &&
                $this->getParentDomAddress()->getContextPath() ===
                    $this->nodeAddressFactory->createFromNode($parentNode)->serializeForUri()
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

    protected function findChildNodes(NodeInterface $node): Nodes
    {
        return $this->nodeAccessorFor($node)->findChildNodes($node);
    }

    protected function isNodeTypeAllowedAsChildNode(NodeInterface $node, NodeType $nodeType): bool
    {
        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $node->getContentStreamIdentifier(),
            $node->getDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        );
        if (NodeInfoHelper::isAutoCreated($node, $nodeAccessor)) {
            $parentNode = $nodeAccessor->findParentNode($node);
            return !$parentNode || $parentNode->getNodeType()->allowsGrandchildNodeType(
                (string)$node->getNodeName(),
                $nodeType
            );
        } else {
            return $node->getNodeType()->allowsChildNodeType($nodeType);
        }
    }
}
