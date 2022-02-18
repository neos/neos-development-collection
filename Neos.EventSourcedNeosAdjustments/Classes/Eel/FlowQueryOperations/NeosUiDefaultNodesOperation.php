<?php
namespace Neos\EventSourcedNeosAdjustments\Eel\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraintFactory;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorInterface;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorManager;
use Neos\EventSourcedContentRepository\Domain\Context\ContentSubgraph\SubtreeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\Flow\Annotations as Flow;

class NeosUiDefaultNodesOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'neosUiDefaultNodes';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 110;

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
     * @var NodeTypeConstraintFactory
     */
    protected $nodeTypeConstraintFactory;

    /**
     * {@inheritdoc}
     *
     * @param array (or array-like object) $context onto which this operation should be applied
     * @return boolean TRUE if the operation can be applied onto the $context, FALSE otherwise
     */
    public function canEvaluate($context)
    {
        return isset($context[0]) && ($context[0] instanceof NodeInterface);
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        /** @var NodeInterface $siteNode */
        /** @var NodeInterface $documentNode */
        list($siteNode, $documentNode) = $flowQuery->getContext();
        /** @var string[] $toggledNodes Node Addresses */
        list($baseNodeType, $loadingDepth, $toggledNodes, $clipboardNodesContextPaths) = $arguments;

        $baseNodeTypeConstraints = $this->nodeTypeConstraintFactory->parseFilterString($baseNodeType);

        $nodeAccessor = $this->nodeAccessorManager->accessorFor($documentNode->getContentStreamIdentifier(), $documentNode->getDimensionSpacePoint(), VisibilityConstraints::withoutRestrictions());

        // Collect all parents of documentNode up to siteNode
        $parents = [];
        $currentNode = $nodeAccessor->findParentNode($documentNode);
        if ($currentNode) {
            $currentNodePath = $nodeAccessor->findNodePath($currentNode);
            $siteNodePath = $nodeAccessor->findNodePath($siteNode);
            $parentNodeIsUnderneathSiteNode = strpos((string)$currentNodePath, (string)$siteNodePath) === 0;
            while ((string)$currentNode->getNodeAggregateIdentifier() !== (string)$siteNode->getNodeAggregateIdentifier() && $parentNodeIsUnderneathSiteNode) {
                $parents[] = $currentNode->getNodeAggregateIdentifier()->jsonSerialize();
                $currentNode = $nodeAccessor->findParentNode($currentNode);
            }
        }

        $nodes = [
            ((string)$siteNode->getNodeAggregateIdentifier()) => $siteNode
        ];

        $gatherNodesRecursively = function (&$nodes, NodeInterface $baseNode, $level = 0) use (&$gatherNodesRecursively, $baseNodeTypeConstraints, $loadingDepth, $toggledNodes, $parents, $nodeAccessor) {
            $baseNodeAddress = $this->nodeAddressFactory->createFromNode($baseNode);

            if (
                $level < $loadingDepth || // load all nodes within loadingDepth
                $loadingDepth === 0 || // unlimited loadingDepth
                in_array($baseNodeAddress->serializeForUri(), $toggledNodes) || // load toggled nodes
                in_array((string)$baseNode->getNodeAggregateIdentifier(), $parents) // load children of all parents of documentNode
            ) {
                foreach ($nodeAccessor->findChildNodes($baseNode, $baseNodeTypeConstraints) as $childNode) {
                    $nodes[(string)$childNode->getNodeAggregateIdentifier()] = $childNode;
                    $gatherNodesRecursively($nodes, $childNode, $level + 1);
                }
            }
        };
        $gatherNodesRecursively($nodes, $siteNode);

        if (!isset($nodes[(string)$documentNode->getNodeAggregateIdentifier()])) {
            $nodes[(string)$documentNode->getNodeAggregateIdentifier()] = $documentNode;
        }

        foreach ($clipboardNodesContextPaths as $clipboardNodeContextPath) {
            $clipboardNodeAddress = $this->nodeAddressFactory->createFromUriString($clipboardNodeContextPath);
            $clipboardNode = $this->nodeAccessorManager->accessorFor(
                $clipboardNodeAddress->contentStreamIdentifier,
                $clipboardNodeAddress->dimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions()
            )->findByIdentifier($clipboardNodeAddress->getNodeAggregateIdentifier());
            if ($clipboardNode && !array_key_exists((string)$clipboardNode->getNodeAggregateIdentifier(), $nodes)) {
                $nodes[(string)$clipboardNode->getNodeAggregateIdentifier()] = $clipboardNode;
            }
        }

        /* TODO: we might use the Subtree as this may be more efficient - but the logic above mirrors the old behavior better.
        if ($loadingDepth === 0) {
            throw new \RuntimeException('TODO: Loading Depth 0 not supported');
        }
        $subtree = $nodeAccessor->findSubtrees([$siteNode], $loadingDepth, $nodeTypeConstraints);
        $subtree = $subtree->getChildren()[0];
        $this->flattenSubtreeToNodeList($nodeAccessor, $subtree, $nodes);*/

        $flowQuery->setContext($nodes);
    }


    private function flattenSubtreeToNodeList(NodeAccessorInterface $nodeAccessor, SubtreeInterface $subtree, array &$nodes)
    {
        $currentNode = $subtree->getNode();

        $nodes[(string)$currentNode->getNodeAggregateIdentifier()] = $currentNode;

        foreach ($subtree->getChildren() as $childSubtree) {
            $this->flattenSubtreeToNodeList($nodeAccessor, $childSubtree, $nodes);
        }
    }
}
