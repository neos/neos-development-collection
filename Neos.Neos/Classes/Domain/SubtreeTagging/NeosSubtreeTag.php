<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\SubtreeTagging;

use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\UntagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;

/**
 * Neos specific content repository subtree tag definitions {@see SubtreeTag}
 *
 * A tag that can be added to Node aggregates that is inherited by all its descendants
 *
 * By default, Neos provides two kinds of subtree tags:
 *
 * - `disabled` {@see NeosSubtreeTag::disabled()} which is used when disabling a node
 * - `removed` {@see NeosSubtreeTag::removed()} which is used when soft removing a node
 *
 * The visibility constraints {@see NeosVisibilityConstraints} define which tagged nodes are queried.
 *
 * @api
 */
final readonly class NeosSubtreeTag
{
    private function __construct()
    {
        // no instances
    }

    /**
     * Content repository subtree tag which is used to denote that a node is disabled
     *
     * By default, disabled nodes never show up in the fronend rendering of a document.
     *
     * Disabling a node / its subtree is done via {@see TagSubtree}.
     * In reverse enabling a node {@see UntagSubtree} is to be used.
     *
     *     $contentRepository->handle(TagSubtree::create(
     *         $node->workspaceName,
     *         $node->aggregateId,
     *         $node->dimensionSpacePoint,
     *         NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS,
     *         NeosSubtreeTag::disabled()
     *     ));
     *
     * @api
     */
    public static function disabled(): SubtreeTag
    {
        return SubtreeTag::fromString('disabled');
    }

    /**
     * Content repository subtree tag which is used to denote that a node is soft removed
     *
     * Issuing 'hard' removals via {@see RemoveNodeAggregate} on a non-live workspace ist not desired in Neos
     * and comes with complications:
     *
     * - Hard removals destroy all hierarchy information immediately, making it impossible to locate where a removal took place.
     *   A special case imposes the deletion of newly created nodes.
     *   Associating content changes with its document is important when publishing via {@see WorkspacePublishingService::publishChangesInDocument()}
     *
     * - Hard removals easily cause conflicts:
     *   The removal of a selected node might not be published in that scope if a node was moved outwards.
     *   The rebase will cause conflicts if changes were made in a hierarchy of removed nodes, like moving nodes outwards.
     *
     * Instead, tagging a node / its subtree via {@see TagSubtree} will mark it as removed in Neos.
     *
     *     $contentRepository->handle(TagSubtree::create(
     *         $node->workspaceName,
     *         $node->aggregateId,
     *         $node->dimensionSpacePoint,
     *         NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS,
     *         NeosSubtreeTag::removed()
     *     ));
     *
     * Nodes tagged as removed will not show up in the frontend rendering nor in the backend by default.
     * Only subtracting this removed tag from the {@see VisibilityConstraints} or operating on the node aggregates directly
     * will make soft removed nodes available.
     *
     * @api
     */
    public static function removed(): SubtreeTag
    {
        return SubtreeTag::fromString('removed');
    }
}
