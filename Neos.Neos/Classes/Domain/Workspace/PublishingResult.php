<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\Workspace;

use Neos\Flow\Annotations as Flow;

/**
 * The publishing result DTO
 *
 * @internal for communication within Neos only
 */
#[Flow\Proxy(false)]
final readonly class PublishingResult
{
    public function __construct(
        public int $numberOfPublishedChanges,
    ) {
    }
}
