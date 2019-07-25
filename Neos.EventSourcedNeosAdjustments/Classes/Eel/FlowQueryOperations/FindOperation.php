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

use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\NodeAggregate\Exception\NodeAggregateIdentifierIsInvalid;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodes;
use Neos\Eel\FlowQuery\FizzleParser;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode;

/**
 * "find" operation working on ContentRepository nodes. This operation allows for retrieval
 * of nodes specified by a path, identifier or node type (recursive).
 *
 * Example (node name):
 *
 * 	q(node).find('main')
 *
 * Example (relative path):
 *
 * 	q(node).find('main/text1')
 *
 * Example (absolute path):
 *
 * 	q(node).find('/sites/my-site/home')
 *
 * Example (identifier):
 *
 * 	q(node).find('#30e893c1-caef-0ca5-b53d-e5699bb8e506')
 *
 * Example (node type):
 *
 * 	q(node).find('[instanceof Neos.NodeTypes:Text]')
 *
 * Example (multiple node types):
 *
 * 	q(node).find('[instanceof Neos.NodeTypes:Text],[instanceof Neos.NodeTypes:Image]')
 *
 * Example (node type with filter):
 *
 * 	q(node).find('[instanceof Neos.NodeTypes:Text][text*="Neos"]')
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
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * {@inheritdoc}
     *
     * @param array (or array-like object) $context onto which this operation should be applied
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function canEvaluate($context)
    {
        foreach ($context as $contextNode) {
            if (!$contextNode instanceof TraversableNodeInterface) {
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
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation
     * @return void
     * @throws FlowQueryException
     * @throws \Neos\Eel\Exception
     * @throws \Neos\Eel\FlowQuery\FizzleException
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $contextNodes = TraversableNodes::fromArray($flowQuery->getContext());
        if ($contextNodes->isEmpty() || empty($arguments[0])) {
            return;
        }

        $result = [];
        $selectorAndFilter = $arguments[0];

        $parsedFilter = null;
        $parsedFilter = FizzleParser::parseFilterGroup($selectorAndFilter);

        /** @todo fetch them $elsewhere (fusion runtime?) */
        $visibilityConstraints = VisibilityConstraints::frontend();

        $entryPoints = [];
        foreach ($contextNodes as $contextNode) {
            $subgraph = $this->contentGraph->getSubgraphByIdentifier(
                $contextNode->getContentStreamIdentifier(),
                $contextNode->getDimensionSpacePoint(),
                $visibilityConstraints
            );
            $subgraphIdentifier = md5($subgraph->getContentStreamIdentifier() . '@' . $subgraph->getDimensionSpacePoint());
            if (!isset($entryPoints[(string) $subgraphIdentifier])) {
                $entryPoints[(string) $subgraphIdentifier] = [
                    'subgraph' => $subgraph,
                    'nodes' => []
                ];
            }
            $entryPoints[(string) $subgraphIdentifier]['nodes'][] = $contextNode;
        }

        foreach ($parsedFilter['Filters'] as $filter) {
            $filterResults = [];
            $generatedNodes = false;
            if (isset($filter['IdentifierFilter'])) {
                try {
                    $nodeAggregateIdentifier = NodeAggregateIdentifier::fromString($filter['IdentifierFilter']);
                } catch (NodeAggregateIdentifierIsInvalid $exception) {
                    throw new FlowQueryException('find() requires a valid node aggregate identifier, ' . $filter['IdentifierFilter'] . ' given.', 1489921359);
                }
                foreach ($entryPoints as $entryPoint) {
                    /** @var ContentSubgraphInterface $subgraph */
                    $subgraph = $entryPoint['subgraph'];
                    $nodeByIdentifier = $subgraph->findNodeByNodeAggregateIdentifier($nodeAggregateIdentifier);
                    if ($nodeByIdentifier) {
                        $filterResults[] = new TraversableNode($nodeByIdentifier, $subgraph);
                    }
                }
                $generatedNodes = true;
            } elseif (isset($filter['PropertyNameFilter']) || isset($filter['PathFilter'])) {
                $nodePath = NodePath::fromString(isset($filter['PropertyNameFilter']) ? $filter['PropertyNameFilter'] : $filter['PathFilter']);
                $rootNode = null;
                foreach ($entryPoints as $entryPoint) {
                    /** @var ContentSubgraphInterface $subgraph */
                    $subgraph = $entryPoint['subgraph'];
                    if ($nodePath->isAbsolute()) {
                        if (is_null($rootNode)) {
                            $rootNode = $this->contentGraph->findRootNodeAggregateByType($subgraph->getContentStreamIdentifier(), NodeTypeName::fromString('Neos.Neos:Sites'));
                        }
                        $nodeByPath = $subgraph->findNodeByPath($nodePath, $rootNode->getIdentifier());
                        if ($nodeByPath) {
                            $filterResults[] = new TraversableNode($nodeByPath, $subgraph);
                        }
                    } else {
                        foreach ($entryPoint['nodes'] as $node) {
                            /** @var TraversableNodeInterface $node */
                            $nodeByPath = $subgraph->findNodeByPath($nodePath, $node->getNodeAggregateIdentifier());
                            if ($nodeByPath) {
                                $filterResults[] = new TraversableNode($nodeByPath, $subgraph);
                            }
                        }
                    }
                }
                $generatedNodes = true;
            }

            if (isset($filter['AttributeFilters']) && $filter['AttributeFilters'][0]['Operator'] === 'instanceof') {
                $nodeTypeName = NodeTypeName::fromString($filter['AttributeFilters'][0]['Operand']);
                foreach ($entryPoints as $entryPoint) {
                    /** @var ContentSubgraphInterface $subgraph */
                    $subgraph = $entryPoint['subgraph'];
                    $entryIdentifiers = [];
                    foreach ($entryPoint['nodes'] as $node) {
                        /** @var TraversableNodeInterface $node */
                        $entryIdentifiers[] = $node->getNodeAggregateIdentifier();
                    }

                    foreach ($subgraph->findDescendants($entryIdentifiers) as $descendant) {
                        if ($descendant->getNodeType()->isOfType((string)$nodeTypeName)) {
                            $filterResults[] = new TraversableNode($descendant, $subgraph);
                        }
                    }
                }
                unset($filter['AttributeFilters'][0]);
                $generatedNodes = true;
            }
            if (isset($filter['AttributeFilters']) && count($filter['AttributeFilters']) > 0) {
                if (!$generatedNodes) {
                    throw new FlowQueryException('find() needs an identifier, path or instanceof filter for the first filter part', 1436884196);
                }
                $filterQuery = new FlowQuery($filterResults);
                foreach ($filter['AttributeFilters'] as $attributeFilter) {
                    $filterQuery->pushOperation('filter', [$attributeFilter['text']]);
                }
                $filterResults = $filterQuery->get();
            }
            $result = array_merge($result, $filterResults);
        }

        $flowQuery->setContext(array_unique($result));
    }
}
