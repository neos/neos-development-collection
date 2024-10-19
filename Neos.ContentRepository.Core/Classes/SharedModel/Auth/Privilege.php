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

namespace Neos\ContentRepository\Core\SharedModel\Auth;

/**
 * A privilege that is returned by the {@see AuthProviderInterface}
 * @api
 */
final readonly class Privilege
{
    private function __construct(
        public bool $granted,
        public string $reason,
    ) {
    }

    public static function granted(string $reason): self
    {
        return new self(true, $reason);
    }

    public static function denied(string $reason): self
    {
        return new self(false, $reason);
    }
}
