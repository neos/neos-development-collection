<?php
namespace Neos\Neos\Service;

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
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;

/**
 * The workspaces service adds some basic helper methods for getting workspaces,
 * unpublished nodes and methods for publishing nodes or whole workspaces.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class PublishingService extends \Neos\ContentRepository\Domain\Service\PublishingService
{
    /**
     * Publishes the given node to the specified target workspace. If no workspace is specified, the base workspace
     * is assumed.
     *
     * If the given node is a Document or has ContentCollection child nodes, these nodes are published as well.
     *
     * @param NodeInterface $node
     * @param Workspace $targetWorkspace If not set the base workspace is assumed to be the publishing target
     * @return void
     * @api
     */
    public function publishNode(NodeInterface $node, Workspace $targetWorkspace = null)
    {
        if ($targetWorkspace === null) {
            $targetWorkspace = $node->getWorkspace()->getBaseWorkspace();
        }
        if (!$targetWorkspace instanceof Workspace) {
            return;
        }
        $nodes = array($node);
        $nodeType = $node->getNodeType();

        if ($nodeType->isOfType('Neos.Neos:Document') || $nodeType->hasConfiguration('childNodes')) {
            $nodes = array_merge($nodes, $this->collectAllContentChildNodes($node));
        }
        $sourceWorkspace = $node->getWorkspace();
        $sourceWorkspace->publishNodes($nodes, $targetWorkspace);

        $this->emitNodePublished($node, $targetWorkspace);
    }

    /**
     * Discards the given node from its workspace.
     *
     * If the given node is a Document or has ContentCollection child nodes, these nodes are discarded as well.
     *
     * @param NodeInterface $node
     */
    public function discardNode(NodeInterface $node)
    {
        $nodes = array($node);
        $nodeType = $node->getNodeType();

        if ($nodeType->isOfType('Neos.Neos:Document') || $nodeType->hasConfiguration('childNodes')) {
            $nodes = array_merge($nodes, $this->collectAllContentChildNodes($node));
        }

        $this->discardNodes($nodes);
    }

    /**
     * @param NodeInterface $parentNode
     * @param array $collectedNodes
     * @return array
     */
    protected function collectAllContentChildNodes(NodeInterface $parentNode, $collectedNodes = [])
    {
        foreach ($parentNode->getChildNodes('!Neos.Neos:Document') as $contentNode) {
            $collectedNodes[] = $contentNode;
            $collectedNodes = array_merge($collectedNodes, $this->collectAllContentChildNodes($contentNode));
        }
        return $collectedNodes;
    }
}
