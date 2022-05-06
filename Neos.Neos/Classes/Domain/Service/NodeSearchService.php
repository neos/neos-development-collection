<?php
namespace Neos\Neos\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraintFactory;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\Content\SearchTerm;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * Implementation of the NodeSearchServiceInterface for greater backwards compatibility
 *
 * Note: This implementation is meant to ease the transition to an event sourced content repository
 * but since it uses legacy classes (like \Neos\ContentRepository\Domain\Service\Context) it is
 * advised to use NodeAccessor::findDescendants() directly instead.
 *
 * @Flow\Scope("singleton")
 * @deprecated see above
 */
class NodeSearchService implements NodeSearchServiceInterface
{
    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @Flow\Inject
     * @var NodeTypeConstraintFactory
     */
    protected $nodeTypeConstraintFactory;

    /**
     * @param string $term
     * @param array<int,string> $searchNodeTypes
     * @return array<int,NodeInterface>
     */
    public function findByProperties(
        $term,
        array $searchNodeTypes,
        ?NodeInterface $startingPoint = null
    ): array {
        $workspace = $this->workspaceFinder->findOneByName(WorkspaceName::fromString($context->getWorkspaceName()));
        if ($workspace === null) {
            return [];
        }
        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $workspace->getCurrentContentStreamIdentifier(),
            DimensionSpacePoint::fromLegacyDimensionArray($context->getDimensions()),
            $context->isInvisibleContentShown()
                ? VisibilityConstraints::withoutRestrictions()
                : VisibilityConstraints::frontend()
        );
        if ($startingPoint !== null) {
            $entryNodeIdentifier = $startingPoint->getNodeAggregateIdentifier();
        } elseif ($context instanceof ContentContext) {
            $entryNodeIdentifier = NodeAggregateIdentifier::fromString($context->getCurrentSiteNode()->getIdentifier());
        } else {
            $entryNodeIdentifier = NodeAggregateIdentifier::fromString($context->getRootNode()->getIdentifier());
        }
        $entryNode = $nodeAccessor->findByIdentifier($entryNodeIdentifier);
        if (!is_null($entryNode)) {
            $nodes = $nodeAccessor->findDescendants(
                [$entryNode],
                $this->nodeTypeConstraintFactory->parseFilterString(implode(',', $searchNodeTypes)),
                SearchTerm::fulltext($term)
            );
            return iterator_to_array($nodes);
        }
        return [];
    }
}
