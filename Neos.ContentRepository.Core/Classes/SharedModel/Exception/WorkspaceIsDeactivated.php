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

use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @api because exception is thrown during invariant checks on command execution or when attempting to query a deactivated workspace
 */
final class WorkspaceIsDeactivated extends \DomainException
{
    public static function butWasSupposedToBeActivated(WorkspaceName $name): self
    {
        return new self(sprintf(
            'The workspace "%s" is deactivated',
            $name->value
        ), 1765977861);
    }
}
