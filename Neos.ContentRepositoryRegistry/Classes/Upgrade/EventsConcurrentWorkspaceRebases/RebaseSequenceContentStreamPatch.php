<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\EventsConcurrentWorkspaceRebases;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

final readonly class RebaseSequenceContentStreamPatch
{
    public function __construct(
        public RebaseEmptyWorkspaceSequence $rebaseSequence,
        public ContentStreamId $previousContentStreamIdPatch,
    ) {
    }
}
