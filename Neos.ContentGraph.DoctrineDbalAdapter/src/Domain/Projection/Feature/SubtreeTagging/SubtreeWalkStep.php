<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\SubtreeTagging;

/**
 * Control signal returned by a {@see \Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\SubtreeTagging::walkSubtreeDescendants()}
 * visitor to stop the walk at the current node: no write, and no descent into its children.
 *
 * Any other return value (a {@see \Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation})
 * is the (possibly retagged) child to persist - if its tags actually changed - and to descend into.
 *
 * @internal
 */
enum SubtreeWalkStep
{
    case StopHere;
}
