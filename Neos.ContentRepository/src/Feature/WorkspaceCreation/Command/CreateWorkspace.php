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

namespace Neos\ContentRepository\Feature\WorkspaceCreation\Command;

use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;

/**
 * Create a new workspace, based on an existing baseWorkspace
 */
final class CreateWorkspace implements CommandInterface
{
    public function __construct(
        public readonly WorkspaceName $workspaceName,
        public readonly WorkspaceName $baseWorkspaceName,
        public readonly WorkspaceTitle $workspaceTitle,
        public readonly WorkspaceDescription $workspaceDescription,
        public readonly UserIdentifier $initiatingUserIdentifier,
        /**
         * the content stream identifier for the content stream which is created together with the to-be-created workspace
         */
        public readonly ContentStreamIdentifier $newContentStreamIdentifier,
        public readonly ?UserIdentifier $workspaceOwner = null
    ) {
    }
}
