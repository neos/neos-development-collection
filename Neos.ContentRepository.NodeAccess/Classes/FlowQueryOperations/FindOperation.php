<?php
namespace Neos\ContentRepository\NodeAccess\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\NodeType\NodeTypeConstraintParser;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\FlowQuery\FizzleParser;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Neos\FrontendRouting\NodeAddress;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;

/**
 * "find" operation working on ContentRepository nodes. This operation allows for retrieval
 * of nodes specified by a path, identifier or node type (recursive).
 *
 * Example (node name):
 *
 *  q(node).find('main')
 *
 * Example (relative path):
 *
 *  q(node).find('main/text1')
 *
 * Example (absolute path):
 *
 *  q(node).find('/sites/my-site/home')
 *
 * Example (identifier):
 *
 *  q(node).find('#30e893c1-caef-0ca5-b53d-e5699bb8e506')
 *
 * Example (node type):
 *
 *  q(node).find('[instanceof Neos.NodeTypes:Text]')
 *
 * Example (multiple node types):
 *
 *  q(node).find('[instanceof Neos.NodeTypes:Text],[instanceof Neos.NodeTypes:Image]')
 *
 * Example (node type with filter):
 *
 *  q(node).find('[instanceof Neos.NodeTypes:Text][text*="Neos"]')
 *
 */
class FindOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'find';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 100;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * {@inheritdoc}
     *
     * @param array<int,mixed> $context (or array-like object) onto which this operation should be applied
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function canEvaluate($context)
    {
        foreach ($context as $contextNode) {
            if (!$contextNode instanceof Node) {
                return false;
            }
        }

        return true;
    }
    /**
     * This operation operates rather on the given Context object than on the given node
     * and thus may work with the legacy node interface until subgraphs are available
     * {@inheritdoc}
     *
     * @param FlowQuery<int,mixed> $flowQuery the FlowQuery object
     * @param array<int,mixed> $arguments the arguments for this operation
     * @throws FlowQueryException
     * @throws \Neos\Eel\Exception
     * @throws \Neos\Eel\FlowQuery\FizzleException
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments): void
    {
        /** @var array<int,Node> $contextNodes */
        $contextNodes = $flowQuery->getContext();
        if (count($contextNodes) === 0 || empty($arguments[0])) {
            return;
        }

        /** @var Node[] $result */
        $result = [];
        $selectorAndFilter = $arguments[0];

        $parsedFilter = null;
        $parsedFilter = FizzleParser::parseFilterGroup($selectorAndFilter);

        /** @todo fetch them $elsewhere (fusion runtime?) */
        $firstContextNode = reset($contextNodes);
        assert($firstContextNode instanceof Node);
        $contentRepository = $this->contentRepositoryRegistry->get($firstContextNode->subgraphIdentity->contentRepositoryId);

        $entryPoints = $this->getEntryPoints($contextNodes);
        foreach ($parsedFilter['Filters'] as $filter) {
            $filterResults = [];
            $generatedNodes = false;
            if (isset($filter['IdentifierFilter'])) {
                $nodeAggregateId = NodeAggregateId::fromString($filter['IdentifierFilter']);
                $filterResults = $this->addNodesById($nodeAggregateId, $entryPoints, $filterResults);
                $generatedNodes = true;
            } elseif (isset($filter['PropertyNameFilter']) || isset($filter['PathFilter'])) {
                $nodePath = NodePath::fromString($filter['PropertyNameFilter'] ?? $filter['PathFilter']);
                $filterResults = $this->addNodesByPath($nodePath, $entryPoints, $filterResults);
                $generatedNodes = true;
            }

            if (isset($filter['AttributeFilters']) && $filter['AttributeFilters'][0]['Operator'] === 'instanceof') {
                $nodeTypeName = NodeTypeName::fromString($filter['AttributeFilters'][0]['Operand']);
                $filterResults = $this->addNodesByType($nodeTypeName, $entryPoints, $filterResults, $contentRepository);
                unset($filter['AttributeFilters'][0]);
                $generatedNodes = true;
            }
            if (isset($filter['AttributeFilters']) && count($filter['AttributeFilters']) > 0) {
                if (!$generatedNodes) {
                    throw new FlowQueryException(
                        'find() needs an identifier, path or instanceof filter for the first filter part',
                        1436884196
                    );
                }
                $filterQuery = new FlowQuery($filterResults);
                foreach ($filter['AttributeFilters'] as $attributeFilter) {
                    $filterQuery->pushOperation('filter', [$attributeFilter['text']]);
                }
                /** @var array<int,mixed> $filterResults */
                $filterResults = $filterQuery->getContext();
            }
            $result = array_merge($result, $filterResults);
        }

        $uniqueResult = [];
        $usedKeys = [];
        foreach ($result as $item) {
            $identifier = (string) new NodeAddress(
                $item->subgraphIdentity->contentStreamId,
                $item->subgraphIdentity->dimensionSpacePoint,
                $item->nodeAggregateId,
                null
            );
            if (!isset($usedKeys[$identifier])) {
                $uniqueResult[] = $item;
                $usedKeys[$identifier] = $identifier;
            }
        }

        $flowQuery->setContext($uniqueResult);
    }

    /**
     * @param array<int,Node> $contextNodes
     * @return array<string,mixed>
     */
    protected function getEntryPoints(array $contextNodes): array
    {
        $entryPoints = [];
        foreach ($contextNodes as $contextNode) {
            assert($contextNode instanceof Node);
            $subgraph = $this->contentRepositoryRegistry->subgraphForNode($contextNode);
            $subgraphIdentifier = md5($contextNode->subgraphIdentity->contentStreamId
                . '@' . $contextNode->subgraphIdentity->dimensionSpacePoint);
            if (!isset($entryPoints[(string) $subgraphIdentifier])) {
                $entryPoints[(string) $subgraphIdentifier] = [
                    'subgraph' => $subgraph,
                    'nodes' => []
                ];
            }
            $entryPoints[(string) $subgraphIdentifier]['nodes'][] = $contextNode;
        }

        return $entryPoints;
    }

    /**
     * @param array<string,mixed> $entryPoints
     * @param array<int,Node> $result
     * @return array<int,Node>
     */
    protected function addNodesById(
        NodeAggregateId $nodeAggregateId,
        array $entryPoints,
        array $result
    ): array {
        foreach ($entryPoints as $entryPoint) {
            /** @var ContentSubgraphInterface $subgraph */
            $subgraph = $entryPoint['subgraph'];
            $nodeByIdentifier = $subgraph->findNodeById($nodeAggregateId);
            if ($nodeByIdentifier) {
                $result[] = $nodeByIdentifier;
            }
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $entryPoints
     * @param array<int,Node> $result
     * @return array<int,Node>
     */
    protected function addNodesByPath(NodePath $nodePath, array $entryPoints, array $result): array
    {
        foreach ($entryPoints as $entryPoint) {
            /** @var ContentSubgraphInterface $subgraph */
            $subgraph = $entryPoint['subgraph'];
            foreach ($entryPoint['nodes'] as $node) {
                /** @var Node $node */
                if ($nodePath->isAbsolute()) {
                    $rootNode = $node;
                    while ($rootNode instanceof Node && !$rootNode->classification->isRoot()) {
                        $rootNode = $subgraph->findParentNode($rootNode->nodeAggregateId);
                    }
                    if ($rootNode instanceof Node) {
                        $nodeByPath = $subgraph->findNodeByPath($nodePath, $rootNode->nodeAggregateId);
                    }
                } else {
                    $nodeByPath = $subgraph->findNodeByPath($nodePath, $node->nodeAggregateId);
                }
                if (isset($nodeByPath)) {
                    $result[] = $nodeByPath;
                }
            }
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $entryPoints
     * @param array<int,Node> $result
     * @return array<int,Node>
     */
    protected function addNodesByType(NodeTypeName $nodeTypeName, array $entryPoints, array $result, ContentRepository $contentRepository): array
    {
        $nodeTypeFilter = NodeTypeConstraints::create(NodeTypeNames::with($nodeTypeName), NodeTypeNames::createEmpty());
        foreach ($entryPoints as $entryPoint) {
            /** @var ContentSubgraphInterface $subgraph */
            $subgraph = $entryPoint['subgraph'];

            /** @var Node $node */
            foreach ($entryPoints['nodes'] as $node) {
                foreach ($subgraph->findDescendantNodes($node->nodeAggregateId, FindDescendantNodesFilter::nodeTypeConstraints($nodeTypeFilter)) as $descendant) {
                    $result[] = $descendant;
                }
            }
        }

        return $result;
    }
}
