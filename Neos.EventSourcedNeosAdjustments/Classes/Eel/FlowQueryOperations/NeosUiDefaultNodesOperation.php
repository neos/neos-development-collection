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
use Neos\ContentRepository\Exception\NodeException;
use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;
use Neos\ContentRepository\Intermediary\Domain\ReadModelFactory;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\EventSourcedContentRepository\Domain\Context\ContentSubgraph\SubtreeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\Flow\Annotations as Flow;

class NeosUiDefaultNodesOperation extends \Neos\Neos\Ui\FlowQueryOperations\NeosUiDefaultNodesOperation
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
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @Flow\Inject
     * @var NodeTypeConstraintFactory
     */
    protected $nodeTypeConstraintFactory;

    /**
     * @Flow\Inject
     * @var ReadModelFactory
     */
    protected $readModelFactory;

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        /** @var NodeBasedReadModelInterface $siteNode */
        /** @var NodeBasedReadModelInterface $documentNode */
        list($siteNode, $documentNode) = $flowQuery->getContext();
        /** @var NodeBasedReadModelInterface $toggledNodes */
        list($baseNodeType, $loadingDepth, $toggledNodes, $clipboardNodesContextPaths) = $arguments;

        $nodeTypeConstraints = $this->nodeTypeConstraintFactory->parseFilterString($baseNodeType);

        $subgraph = $this->contentGraph->getSubgraphByIdentifier($documentNode->getContentStreamIdentifier(), $documentNode->getDimensionSpacePoint(), VisibilityConstraints::withoutRestrictions());

        // Collect all parents of documentNode up to siteNode
        $nodes = [
            ((string)$siteNode->getNodeAggregateIdentifier()) => $siteNode
        ];
        $currentNode = null;
        try {
            $currentNode = $documentNode->findParentNode();
        } catch (NodeException $ignored) {
            // parent does not exist
        }
        if ($currentNode) {
            $parentNodeIsUnderneathSiteNode = strpos((string)$currentNode->findNodePath(), (string)$siteNode->findNodePath()) === 0;
            while ((string)$currentNode->getNodeAggregateIdentifier() !== (string)$siteNode->getNodeAggregateIdentifier() && $parentNodeIsUnderneathSiteNode) {
                $nodes[(string)$currentNode->getNodeAggregateIdentifier()] = $currentNode;
                $currentNode = $currentNode->findParentNode();
            }
        }

        //                 in_array((string)$baseNode->getNodeAggregateIdentifier(), $toggledNodes) || // load toggled nodes


        if ($loadingDepth === 0) {
            throw new \RuntimeException('TODO: Loading Depth 0 not supported');
        }
        $subtree = $subgraph->findSubtrees([$siteNode->getNodeAggregateIdentifier()], $loadingDepth, $nodeTypeConstraints);
        $subtree = $subtree->getChildren()[0];
        $this->flattenSubtreeToNodeList($subgraph, $subtree, $nodes);

        // TODO: Clipboard nodes

        $flowQuery->setContext($nodes);
    }


    private function flattenSubtreeToNodeList(ContentSubgraphInterface $subgraph, SubtreeInterface $subtree, array &$nodes)
    {
        $currentNode = $subtree->getNode();

        $nodes[(string)$currentNode->getNodeAggregateIdentifier()] = $this->readModelFactory->createReadModel($currentNode, $subgraph);

        foreach ($subtree->getChildren() as $childSubtree) {
            $this->flattenSubtreeToNodeList($subgraph, $childSubtree, $nodes);
        }
    }
}
