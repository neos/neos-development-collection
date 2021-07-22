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
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorInterface;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorManager;
use Neos\EventSourcedContentRepository\Domain\Context\ContentSubgraph\SubtreeInterface;
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
        /** @var NodeInterface $toggledNodes */
        list($baseNodeType, $loadingDepth, $toggledNodes, $clipboardNodesContextPaths) = $arguments;

        $nodeTypeConstraints = $this->nodeTypeConstraintFactory->parseFilterString($baseNodeType);

        $nodeAccessor = $this->nodeAccessorManager->accessorFor($documentNode->getContentStreamIdentifier(), $documentNode->getDimensionSpacePoint(), VisibilityConstraints::withoutRestrictions());

        // Collect all parents of documentNode up to siteNode
        $nodes = [
            ((string)$siteNode->getNodeAggregateIdentifier()) => $siteNode
        ];
        $currentNode = null;
        try {
            $currentNode = $nodeAccessor->findParentNode($documentNode);
        } catch (NodeException $ignored) {
            // parent does not exist
        }
        if ($currentNode) {
            $currentNodePath = $nodeAccessor->findNodePath($currentNode);
            $siteNodePath = $nodeAccessor->findNodePath($siteNode);
            $parentNodeIsUnderneathSiteNode = strpos((string)$currentNodePath, (string)$siteNodePath) === 0;
            while ((string)$currentNode->getNodeAggregateIdentifier() !== (string)$siteNode->getNodeAggregateIdentifier() && $parentNodeIsUnderneathSiteNode) {
                $nodes[(string)$currentNode->getNodeAggregateIdentifier()] = $currentNode;
                $currentNode = $nodeAccessor->findParentNode($currentNode);
            }
        }

        //                 in_array((string)$baseNode->getNodeAggregateIdentifier(), $toggledNodes) || // load toggled nodes


        if ($loadingDepth === 0) {
            throw new \RuntimeException('TODO: Loading Depth 0 not supported');
        }
        $subtree = $nodeAccessor->findSubtrees([$siteNode], $loadingDepth, $nodeTypeConstraints);
        $subtree = $subtree->getChildren()[0];
        $this->flattenSubtreeToNodeList($nodeAccessor, $subtree, $nodes);

        // TODO: Clipboard nodes

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
