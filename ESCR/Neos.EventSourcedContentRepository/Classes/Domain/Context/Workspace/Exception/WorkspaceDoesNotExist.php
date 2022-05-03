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

namespace Neos\EventSourcedContentRepository\Domain\Context\Workspace\Exception;

use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Exception;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class WorkspaceDoesNotExist extends Exception
{
    public static function butWasSupposedTo(WorkspaceName $name): self
    {
        return new self(sprintf(
            'The source workspace %s does not exist',
            $name
        ), 1513924741);
    }
}
