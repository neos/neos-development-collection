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

namespace Neos\ContentRepository\Core\SharedModel\Exception;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @api because exception is thrown during invariant checks on command execution or when attempting to query a non-existing workspace
 */
final class WorkspaceDoesNotExist extends \RuntimeException
{
    public static function butWasSupposedTo(WorkspaceName $name): self
    {
        return new self(sprintf(
            'The workspace "%s" does not exist',
            $name->value
        ), 1513924741);
    }

    public static function butWasSupposedToInContentRepository(WorkspaceName $name, ContentRepositoryId $contentRepositoryId): self
    {
        return new self(sprintf(
            'The workspace "%s" does not exist in content repository "%s"',
            $name->value,
            $contentRepositoryId->value
        ), 1733737361);
    }
}
