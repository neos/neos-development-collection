<?php
namespace Neos\ContentRepository\Eel\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Validation\Validator\NodeIdentifierValidator;
use Neos\Eel\FlowQuery\FizzleParser;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;

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
 * 	q(node).find('[instanceof Acme.Com:Text]')
 *
 * Example (multiple node types):
 *
 * 	q(node).find('[instanceof Acme.Com:Text],[instanceof Acme.Com:Image]')
 *
 * Example (node type with filter):
 *
 * 	q(node).find('[instanceof Acme.Com:Text][text*="Neos"]')
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
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * {@inheritdoc}
     *
     * @param array (or array-like object) $context onto which this operation should be applied
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function canEvaluate($context)
    {
        if (count($context) === 0) {
            return true;
        }

        foreach ($context as $contextNode) {
            if (!$contextNode instanceof NodeInterface) {
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
        $context = $flowQuery->getContext();
        if (!isset($context[0]) || empty($arguments[0])) {
            return;
        }

        $result = [];
        $selectorAndFilter = $arguments[0];

        $parsedFilter = null;
        $parsedFilter = FizzleParser::parseFilterGroup($selectorAndFilter);
        if (isset($parsedFilter['Filters']) && $this->hasOnlyInstanceOfFilters($parsedFilter['Filters'])) {
            $nodeTypes = [];
            foreach ($parsedFilter['Filters'] as $filter) {
                $nodeTypes[] = $filter['AttributeFilters'][0]['Operand'];
            }
            /** @var NodeInterface $contextNode */
            foreach ($context as $contextNode) {
                $result = array_merge($result, $this->nodeDataRepository->findByParentAndNodeTypeInContext($contextNode->getPath(), implode(',', $nodeTypes), $contextNode->getContext(), true));
            }
        } else {
            foreach ($parsedFilter['Filters'] as $filter) {
                $filterResults = [];
                $generatedNodes = false;
                if (isset($filter['IdentifierFilter'])) {
                    if (!preg_match(NodeIdentifierValidator::PATTERN_MATCH_NODE_IDENTIFIER, $filter['IdentifierFilter'])) {
                        throw new FlowQueryException('find() requires a valid node identifier', 1489921359);
                    }
                    foreach ($context as $contextNode) {
                        /** @var NodeInterface $contextNode */
                        $filterResults = [$contextNode->getContext()->getNodeByIdentifier($filter['IdentifierFilter'])];
                    }
                    $generatedNodes = true;
                } elseif (isset($filter['PropertyNameFilter']) || isset($filter['PathFilter'])) {
                    $nodePath = isset($filter['PropertyNameFilter']) ? $filter['PropertyNameFilter'] : $filter['PathFilter'];
                    foreach ($context as $contextNode) {
                        /** @var NodeInterface $contextNode */
                        $node = $contextNode->getNode($nodePath);
                        if ($node !== null) {
                            array_push($filterResults, $node);
                        }
                    }
                    $generatedNodes = true;
                }

                if (isset($filter['AttributeFilters']) && $filter['AttributeFilters'][0]['Operator'] === 'instanceof') {
                    foreach ($context as $contextNode) {
                        $filterResults = array_merge($filterResults, $this->nodeDataRepository->findByParentAndNodeTypeInContext($contextNode->getPath(), $filter['AttributeFilters'][0]['Operand'], $contextNode->getContext(), true));
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
        }

        $flowQuery->setContext(array_unique($result));
    }

    /**
     * Check if the parsed filters only contain instanceof filters (e.g. "[instanceof Foo],[instanceof Bar]")
     *
     * @param array $filters
     * @return boolean
     */
    protected function hasOnlyInstanceOfFilters(array $filters)
    {
        foreach ($filters as $filter) {
            if (!isset($filter['AttributeFilters']) || count($filter['AttributeFilters']) !== 1 || $filter['AttributeFilters'][0]['Operator'] !== 'instanceof') {
                return false;
            }
        }
        return true;
    }
}
