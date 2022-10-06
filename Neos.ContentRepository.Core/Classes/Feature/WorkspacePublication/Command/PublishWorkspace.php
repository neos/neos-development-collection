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

namespace Neos\ContentRepository\Core\Feature\WorkspacePublication\Command;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Publish a workspace
 *
 * @api commands are the write-API of the ContentRepository
 */
final class PublishWorkspace implements CommandInterface
{
    public function __construct(
        public readonly WorkspaceName $workspaceName,
    ) {
    }
}
