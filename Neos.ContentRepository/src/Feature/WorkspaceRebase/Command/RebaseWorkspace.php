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

namespace Neos\ContentRepository\Feature\WorkspaceRebase\Command;

use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;

/**
 * Rebase a workspace
 *
 * @api commands are the write-API of the ContentRepository
 */
final class RebaseWorkspace implements CommandInterface
{
    public function __construct(
        public readonly WorkspaceName $workspaceName,
        public readonly UserIdentifier $initiatingUserIdentifier,
        /**
         * Name of the new content stream which is created during the rebase
         */
        public readonly ContentStreamIdentifier $rebasedContentStreamIdentifier
    ) {
    }

    public static function create(WorkspaceName $workspaceName, UserIdentifier $initiatingUserIdentifier): self
    {
        return new self($workspaceName, $initiatingUserIdentifier, ContentStreamIdentifier::create());
    }

    /**
     * Call this method if you want to run this command fully deterministically, f.e. during test cases
     */
    public static function createFullyDeterministic(
        WorkspaceName $workspaceName,
        UserIdentifier $initiatingUserIdentifier,
        ContentStreamIdentifier $newContentStreamIdentifier
    ): self {
        return new self($workspaceName, $initiatingUserIdentifier, $newContentStreamIdentifier);
    }
}
