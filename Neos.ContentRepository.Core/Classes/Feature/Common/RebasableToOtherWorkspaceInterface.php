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
use Neos\ContentRepository\Core\CommandHandler\CommandSimulator;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Common (marker) interface for all **commands** that need to be serialized for rebasing to other workspaces
 *
 * If the api command {@see CommandInterface} is serializable on its own it will directly implement this interface.
 * For complex commands a serialized counterpart - which is not api - will be build which implements this interface.
 *
 * During a rebase, the command (either the original {@see CommandInterface} or its serialized counterpart) will be deserialized
 * from array {@see RebasableToOtherWorkspaceInterface::fromArray()} and reapplied via the {@see CommandSimulator}
 *
 * Reminder: a rebase can fail, because the target content stream might contain conflicting changes.
 *
 * @internal used internally for the rebasing mechanism of content streams
 */
interface RebasableToOtherWorkspaceInterface extends \JsonSerializable
{
    public function createCopyForWorkspace(
        WorkspaceName $targetWorkspaceName,
    ): self;

    /**
     * @param array<string,mixed> $array
     */
    public static function fromArray(array $array): self;
}
