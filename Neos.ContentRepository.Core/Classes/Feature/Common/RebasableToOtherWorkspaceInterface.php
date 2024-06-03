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

namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * This interface is implemented by **commands** which can be rebased to other Content Streams. This is basically all
 * node-based commands.
 *
 * Reminder: a rebase can fail, because the target content stream might contain conflicting changes.
 *
 * @internal used internally for the rebasing mechanism of content streams
 */
interface RebasableToOtherWorkspaceInterface
{
    public function createCopyForWorkspace(
        WorkspaceName $targetWorkspaceName,
    ): CommandInterface;

    /**
     * called during deserialization from metadata
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self;
}
