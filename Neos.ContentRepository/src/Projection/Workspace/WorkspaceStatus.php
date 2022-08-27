<?php

namespace Neos\ContentRepository\Projection\Workspace;

/**
 * @api
 */
enum WorkspaceStatus: string implements \JsonSerializable
{
    /**
     * UP TO DATE Example:
     *
     * Workspace Review <-- Workspace User-Foo
     *     |                    |
     *   Content Stream A <-- Content Stream B
     *
     * This is the case if the contentStream of the base workspace IS EQUAL TO the sourceContentStream
     * of this workspace's content stream.
     *
     * By definition, a base workspace (like "live") is ALWAYS UP_TO_DATE.
     */
    case UP_TO_DATE = 'UP_TO_DATE';

    /**
     * A workspace can be OUTDATED because of two reasons:
     *
     * REASON 1: The base content stream has been rebased
     *
     *     Workspace Review <------------ Workspace User-Foo
     *      .   |                                 |
     *      .   Content Stream A2 (current)       |
     *      Content Stream A1 (previous) <-- Content Stream B
     *
     *     This is the case if the contentStream of the base workspace IS NEWER THAN the sourceContentStream
     *     of this workspace's content stream.
     *
     *     In the example, Content Stream B would need to be rebased to Content stream A2.
     *
     *
     * REASON 2: The base content stream has new events
     *
     *     In case the base content stream (e.g. "Content Stream A" in the example)
     *     has events applied to it *AFTER* the fork-point (when "Content Stream B" is created), the workspace
     *     will also be marked as "outdated".
     */
    case OUTDATED = 'OUTDATED';

    /**
     * CONFLICT Example:
     *
     * CONFLICT is a special case of OUTDATED, but then an error happens during the rebasing.
     *
     * Workspace Review <----------------------------------- Workspace User-Foo
     *      |                                                .             |
     *      Content Stream A2 (current)  <-- Content Stream B2 (rebasing)  |
     *                                                        Content Stream B1
     */
    case OUTDATED_CONFLICT = 'OUTDATED_CONFLICT';

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
