<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\EventsConcurrentWorkspaceRebases;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\Event\Version;

final readonly class RebaseSequenceContentStreamPatch
{
    public function __construct(
        public RebaseEmptyWorkspaceSequence $rebaseSequence,
        public ContentStreamId $initialPreviousContentStreamId,
        public Version $initialContentStreamWasClosedVersion,
        public Version $initialContentStreamWasRemovedVersion,
    ) {
    }
}
