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

namespace Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Rebase a workspace
 *
 * @api commands are the write-API of the ContentRepository
 */
final readonly class RebaseWorkspace implements CommandInterface
{
    /**
     * @param WorkspaceName $workspaceName Name of the workspace that should be rebased
     * @param ContentStreamId $rebasedContentStreamId The id of the new content stream which is created during the rebase
     */
    private function __construct(
        public WorkspaceName $workspaceName,
        public ContentStreamId $rebasedContentStreamId,
        public RebaseErrorHandlingStrategy $rebaseErrorHandlingStrategy
    ) {
    }

    public static function create(WorkspaceName $workspaceName): self
    {
        return new self($workspaceName, ContentStreamId::create(), RebaseErrorHandlingStrategy::STRATEGY_FAIL);
    }

    /**
     * Call this method if you want to run this command fully deterministically, f.e. during test cases
     */
    public function withRebasedContentStreamId(ContentStreamId $newContentStreamId): self
    {
        return new self($this->workspaceName, $newContentStreamId, $this->rebaseErrorHandlingStrategy);
    }

    /**
     * Call this method if you want to run this command with a specific error handling strategy like force
     */
    public function withErrorHandlingStrategy(RebaseErrorHandlingStrategy $rebaseErrorHandlingStrategy): self
    {
        return new self($this->workspaceName, $this->rebasedContentStreamId, $rebaseErrorHandlingStrategy);
    }
}
