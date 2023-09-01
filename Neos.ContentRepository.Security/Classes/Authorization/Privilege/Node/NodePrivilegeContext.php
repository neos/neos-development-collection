<?php
namespace Neos\ContentRepository\Security\Authorization\Privilege\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Dimension\ContentDimensionId;
use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Neos\Utility\NodeTypeWithFallbackProvider;

/**
 * An Eel context matching expression for the node privileges
 */
class NodePrivilegeContext
{
    use NodeTypeWithFallbackProvider;

    /**
     * @Flow\Inject
     * @var SecurityContext
     */
    protected $securityContext;

    protected Node $node;

    protected ?ContentSubgraphInterface $subgraph;

    public function __construct(Node $node)
    {
        $this->node = $node;
    }

    /**
     * Matches if the selected node is an *ancestor* of the given node specified by $nodePathOrIdentifier
     *
     * Example: isAncestorNodeOf('/sites/some/path') matches for the nodes "/sites",
     *  "/sites/some" and "/sites/some/path" but not for "/sites/some/other"
     *
     * @param string $nodePathOrIdentifier The identifier or absolute path of the node to match
     * @return boolean true if the given node matches otherwise false
     */
    public function isAncestorNodeOf(string $nodePathOrIdentifier): bool
    {
        $referenceNodeAggregateId = $this->resolveNodeAggregateIdFromNodePathOrId($nodePathOrIdentifier);
        if (!$referenceNodeAggregateId) {
            return false;
        }

        if ($referenceNodeAggregateId->equals($this->node->nodeAggregateId)) {
            return true;
        }

        foreach ($this->getSubgraph()->findAncestorNodes(
            $referenceNodeAggregateId,
            FindAncestorNodesFilter::create()
        ) as $ancestorNode) {
            if ($ancestorNode->nodeAggregateId->equals($this->node->nodeAggregateId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Matches if the selected node is a *descendant* of the given node specified by $nodePathOrIdentifier
     *
     * Example: isDescendantNodeOf('/sites/some/path') matches for the nodes "/sites/some/path",
     * "/sites/some/path/subnode" but not for "/sites/some/other"
     *
     * @param string $nodePathOrIdentifier The identifier or absolute path of the node to match
     * @return bool true if the given node matches otherwise false
     */
    public function isDescendantNodeOf(string $nodePathOrIdentifier): bool
    {
        $referenceNodeAggregateId = $this->resolveNodeAggregateIdFromNodePathOrId($nodePathOrIdentifier);
        if (!$referenceNodeAggregateId) {
            return false;
        }

        if ($referenceNodeAggregateId->equals($this->node->nodeAggregateId)) {
            return true;
        }

        foreach ($this->getSubgraph()->findAncestorNodes(
            $this->node->nodeAggregateId,
            FindAncestorNodesFilter::create()
        ) as $ancestorNode) {
            if ($ancestorNode->nodeAggregateId->equals($referenceNodeAggregateId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Matches if the selected node is a *descendant* or *ancestor* of the given node specified by $nodePathOrIdentifier
     *
     * Example: isAncestorOrDescendantNodeOf('/sites/some') matches for the nodes "/sites", "/sites/some",
     * "/sites/some/sub" but not "/sites/other"
     *
     * @param string $nodePathOrIdentifier The identifier or absolute path of the node to match
     * @return bool true if the given node matches otherwise false
     */
    public function isAncestorOrDescendantNodeOf(string $nodePathOrIdentifier): bool
    {
        return $this->isAncestorNodeOf($nodePathOrIdentifier) || $this->isDescendantNodeOf($nodePathOrIdentifier);
    }

    /**
     * Matches if the selected node is of the given NodeType(s).
     * If multiple types are specified, only one entry has to match
     *
     * Example: nodeIsOfType(['Neos.ContentRepository:NodeType1', 'Neos.ContentRepository:NodeType2'] matches,
     * if the selected node is of (sub) type *Neos.ContentRepository:NodeType1* or *Neos.ContentRepository:NodeType1*
     *
     * @param string|array<int,string> $nodeTypes A single or an array of fully qualified NodeType name(s),
     * e.g. "Neos.Neos:Document"
     * @return bool true if the selected node matches the $nodeTypes, otherwise false
     */
    public function nodeIsOfType(string|array $nodeTypes): bool
    {
        if (!is_array($nodeTypes)) {
            $nodeTypes = [$nodeTypes];
        }

        foreach ($nodeTypes as $nodeType) {
            if ($this->getNodeType($this->node)->isOfType($nodeType)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Matches if the selected node belongs to one of the given $workspaceNames
     *
     * Example: isInWorkspace(['live', 'user-admin']) matches,
     * if the selected node is in one of the workspaces "user-admin" or "live"
     *
     * @param array<int,string> $workspaceNames An array of workspace names, e.g. ["live", "user-admin"]
     * @return bool true if the selected node matches the $workspaceNames, otherwise false
     */
    public function isInWorkspace(array $workspaceNames): bool
    {
        $contentRepository = $this->contentRepositoryRegistry->get($this->node->subgraphIdentity->contentRepositoryId);

        $workspace = $contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamId(
            $this->node->subgraphIdentity->contentStreamId
        );
        return !is_null($workspace) && in_array($workspace->workspaceName->value, $workspaceNames);
    }

    /**
     * Matches if the currently-selected preset in the passed $dimensionName is one of $presets.
     *
     * Example: isInDimensionPreset('language', 'de') checks whether the currently-selected language
     * preset (in the Neos backend) is "de".
     *
     * Implementation Note: We deliberately work on the Dimension Preset Name, and not on the
     * dimension values itself; as the preset is user-visible and the actual dimension-values
     * for a preset are just implementation details.
     *
     * @param string|array<int,string> $presets
     */
    public function isInDimensionPreset(string $dimensionName, string|array $presets): bool
    {
        if (!is_array($presets)) {
            $presets = [$presets];
        }

        return in_array(
            $this->node->subgraphIdentity->dimensionSpacePoint->getCoordinate(
                new ContentDimensionId($dimensionName)
            ),
            $presets
        );
    }

    /**
     * Resolves the given $nodePathOrIdentifier and returns its node aggregate id if possible
     *
     * @param string $nodePathOrIdentifier identifier or absolute path for the node to resolve
     * @return NodeAggregateId|null depending on whether the given string can be resolved or not
     */
    protected function resolveNodeAggregateIdFromNodePathOrId(string $nodePathOrIdentifier): ?NodeAggregateId
    {
        if (AbsoluteNodePath::patternIsMatchedByString($nodePathOrIdentifier)) {
            return $this->getSubgraph()->findNodeByAbsolutePath(
                AbsoluteNodePath::fromString($nodePathOrIdentifier)
            )?->nodeAggregateId;
        } else {
            return NodeAggregateId::fromString($nodePathOrIdentifier);
        }
    }

    private function getSubgraph(): ContentSubgraphInterface
    {
        if (is_null($this->subgraph)) {
            $this->subgraph = $this->contentRepositoryRegistry->subgraphForNode($this->node);
        }

        return $this->subgraph;
    }
}
