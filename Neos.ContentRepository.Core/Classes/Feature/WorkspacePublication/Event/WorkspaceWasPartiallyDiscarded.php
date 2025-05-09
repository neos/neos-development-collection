<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\WorkspacePublication\Event;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @deprecated This event will never be emitted, it is up-casted to a corresponding {@see WorkspaceWasDiscarded} event instead. This implementation is just kept for backwards-compatibility
 * @internal
 */
final readonly class WorkspaceWasPartiallyDiscarded implements EventInterface
{
    private function __construct()
    {
        // legacy event must not be instantiated
    }

    public static function fromArray(array $values): EventInterface
    {
        return new WorkspaceWasDiscarded(
            WorkspaceName::fromString($values['workspaceName']),
            ContentStreamId::fromString($values['newContentStreamId']),
            ContentStreamId::fromString($values['previousContentStreamId']),
            partial: true
        );
    }

    public function jsonSerialize(): array
    {
        throw new \RuntimeException('Legacy event instance must not exist.');
    }
}
