<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\SubtreeTagging;

use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;

/**
 * Neos specific content repository visibility constraints {@see VisibilityConstraints}
 *
 * The visibility constraints define a context in which the content graph is accessed.
 *
 * By default, Neos provides two kinds of subtree tags:
 *
 * - `disabled` {@see NeosSubtreeTag::disabled()} which is used when disabling a node
 * - `removed` {@see NeosSubtreeTag::removed()} which is used when soft removing a node
 *
 * To control which nodes will be queried via the {@see ContentSubgraphInterface}, the visibility constraints can be used.
 *
 * The definitional here allow to implement the "frontend" and "backend" rendering use cases.
 *
 * To access all nodes, including soft removed nodes, please use {@see VisibilityConstraints::createEmpty()}
 *
 * Specifying the visibility constraints manually is only necessary when breaking away from the by default provided
 * constraints via {@see ContentRepository::getContentSubgraph()}. Circumventing the neos auth provider leads to no ReadNodePrivilege's being evaluated.
 * All custom tagged nodes are visible at all times unless excluded via {@see VisibilityConstraints::merge()}
 *
 * @api
 */
final class NeosVisibilityConstraints
{
    private function __construct()
    {
        // no instances
    }

    /**
     * Used for frontend rendering in combination with the removed constraint, ensuring that neither disabled nor soft removed nodes are visible
     *
     *     NeosVisibilityConstraints::excludeRemoved()
     *         ->merge(NeosVisibilityConstraints::excludeDisabled())
     *
     * @api
     */
    public static function excludeDisabled(): VisibilityConstraints
    {
        return VisibilityConstraints::excludeSubtreeTags(SubtreeTags::create(
            NeosSubtreeTag::disabled()
        ));
    }

    /**
     * Default constraints for the backend and cli, ensuring that disabled nodes are visible, but not soft removed nodes
     *
     * @api
     */
    public static function excludeRemoved(): VisibilityConstraints
    {
        return VisibilityConstraints::excludeSubtreeTags(SubtreeTags::create(
            NeosSubtreeTag::removed()
        ));
    }
}
