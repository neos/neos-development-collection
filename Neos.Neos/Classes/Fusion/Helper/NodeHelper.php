<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Fusion\Helper;

use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Exception;
use Neos\Neos\Domain\NodeLabel\NodeLabelGeneratorInterface;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Presentation\VisualNodePath;

/**
 * Eel helper for ContentRepository Nodes
 */
class NodeHelper implements ProtectedContextAwareInterface
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected NodeLabelGeneratorInterface $nodeLabelGenerator;

    /**
     * Renders the actual node label based on the NodeType definition in Fusion.
     */
    public function label(Node $node): string
    {
        return $this->nodeLabelGenerator->getLabel($node);
    }

    /**
     * @param Node $node
     * @return int
     * @deprecated do not rely on this, as it is rather expensive to calculate
     */
    public function depth(Node $node): int
    {
        return $this->contentRepositoryRegistry->subgraphForNode($node)
            ->countAncestorNodes($node->aggregateId, CountAncestorNodesFilter::create());
    }

    /**
     * @deprecated do not rely on this, as it is rather expensive to calculate
     */
    public function path(Node $node): string
    {
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);
        $ancestors = $subgraph->findAncestorNodes(
            $node->aggregateId,
            FindAncestorNodesFilter::create()
        )->reverse();

        return AbsoluteNodePath::fromLeafNodeAndAncestors($node, $ancestors)->serializeToString();
    }

    /**
     * Retrieving the NodeType of the given Node.
     *
     * If the NodeType schema changed and the NodeType does not exist anymore, NULL is returned.
     *
     * @param Node $node
     * @return NodeType|null
     */
    public function nodeType(Node $node): ?NodeType
    {
        $contentRepository = $this->contentRepositoryRegistry->get($node->contentRepositoryId);
        return $contentRepository->getNodeTypeManager()
            ->getNodeType($node->nodeTypeName);
    }

    /**
     * If this node type or any of the direct or indirect super types
     * has the given name.
     */
    public function isOfType(Node $node, string $nodeType): bool
    {
        $contentRepository = $this->contentRepositoryRegistry->get($node->contentRepositoryId);
        return (bool)$contentRepository->getNodeTypeManager()
            ->getNodeType($node->nodeTypeName)?->isOfType($nodeType);
    }

    public function isDisabled(Node $node): bool
    {
        return $node->tags->contain(SubtreeTag::disabled());
    }

    /**
     * @internal should not be required to be used for integration
     */
    public function isNodeTypeExistent(Node $node): bool
    {
        $contentRepository = $this->contentRepositoryRegistry->get($node->contentRepositoryId);
        return $contentRepository->getNodeTypeManager()->hasNodeType($node->nodeTypeName);
    }

    /**
     * @internal experimental API without documentation and clear use-case
     */
    public function serializedNodeAddress(Node $node): string
    {
        return NodeAddress::fromNode($node)->toJson();
    }

    /**
     * @internal experimental API without documentation and clear use-case
     */
    public function subgraphForNode(Node $node): ContentSubgraphInterface
    {
        return $this->contentRepositoryRegistry->subgraphForNode($node);
    }

    /**
     * Check if the given node is already a collection, find collection by nodePath otherwise, throw exception
     * if no content collection could be found
     *
     * @throws Exception
     * @internal implementation detail of Neos.Neos:ContentCollection
     */
    public function nearestContentCollection(Node $node, ?string $nodePath): Node
    {
        $contentCollectionType = NodeTypeNameFactory::NAME_CONTENT_COLLECTION;
        if ($this->isOfType($node, $contentCollectionType)) {
            return $node;
        } else {
            if ($nodePath === null || $nodePath === '') {
                $nodePathOfNode = VisualNodePath::buildFromAncestors(
                    $node,
                    $this->contentRepositoryRegistry->get($node->contentRepositoryId),
                    $this->nodeLabelGenerator
                );
                throw new Exception(sprintf(
                    'No content collection of type %s could be found in the current node (%s) and no node path was provided.'
                    . ' You might want to configure the nodePath property'
                    . ' with a relative path to the content collection.',
                    $contentCollectionType,
                    $nodePathOfNode->value
                ), 1409300545);
            }
            $nodePath = NodePath::fromString($nodePath);
            $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);

            $subNode = $subgraph->findNodeByPath($nodePath, $node->aggregateId);

            if ($subNode !== null && $this->isOfType($subNode, $contentCollectionType)) {
                return $subNode;
            } else {
                $nodePathOfNode = VisualNodePath::buildFromAncestors(
                    $node,
                    $this->contentRepositoryRegistry->get($node->contentRepositoryId),
                    $this->nodeLabelGenerator
                );
                throw new Exception(sprintf(
                    'No content collection of type %s could be found in the current node (%s) or at the path "%s".'
                    . ' You might want to adjust your node type configuration and create the missing child node'
                    . ' through the "flow structureadjustments:fix --node-type %s" command.',
                    $contentCollectionType,
                    $nodePathOfNode->value,
                    $nodePath->serializeToString(),
                    $node->nodeTypeName->value
                ), 1389352984);
            }
        }
    }

    /**
     * Return a builder to generate a label for a node with a chaining mechanism. To be used in NodeType definition:
     *
     *     'Vendor.Site:MyContent':
     *       label: "${Neos.Node.labelForNode(node).prefix('foo')}"
     *
     * FIXME the method name is slightly ambiguous and not to confused with Neos.Node.label which renders the configured label from yaml
     */
    public function labelForNode(Node $node): NodeLabelToken
    {
        return new NodeLabelToken($node);
    }

    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
