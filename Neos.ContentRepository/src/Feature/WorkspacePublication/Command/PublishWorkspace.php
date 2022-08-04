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

namespace Neos\ContentRepository\Feature\WorkspacePublication\Command;

use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;

/**
 * Publish a workspace
 */
final class PublishWorkspace implements CommandInterface
{
    public function __construct(
        public readonly WorkspaceName $workspaceName,
        public readonly UserIdentifier $initiatingUserIdentifier
    ) {
    }
}
