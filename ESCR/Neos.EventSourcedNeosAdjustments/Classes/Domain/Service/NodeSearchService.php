<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Domain\Service;

/*
 * This file is part of the Neos.EventSourcedNeosAdjustments package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraintFactory;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorManager;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\SearchTerm;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\NodeSearchServiceInterface;

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
        Context $context,
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
