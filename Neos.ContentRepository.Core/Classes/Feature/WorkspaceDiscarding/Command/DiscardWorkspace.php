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

namespace Neos\ContentRepository\Core\Feature\WorkspaceDiscarding\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Core\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Discard a workspace's changes
 *
 * @api commands are the write-API of the ContentRepository
 */
final class DiscardWorkspace implements CommandInterface
{
    private function __construct(
        public readonly WorkspaceName $workspaceName,
        public readonly UserIdentifier $initiatingUserIdentifier,
        /**
         * Content Stream Identifier of the newly created fork, which contains the remaining changes
         * which were not removed
         */
        public readonly ContentStreamIdentifier $newContentStreamIdentifier
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
