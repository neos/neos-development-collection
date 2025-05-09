<?php

declare(strict_types=1);

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Neos\ContentRepository\Core\Feature\Security\Exception;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @api
 */
final class AccessDenied extends \Exception
{
    public static function becauseCommandIsNotGranted(CommandInterface $command, string $reason): self
    {
        return new self(sprintf('Command "%s" was denied: %s', $command::class, $reason), 1729086686);
    }

    public static function becauseWorkspaceCantBeRead(WorkspaceName $workspaceName, string $reason): self
    {
        return new self(sprintf('Read access denied for workspace "%s": %s', $workspaceName->value, $reason), 1729014760);
    }
}
