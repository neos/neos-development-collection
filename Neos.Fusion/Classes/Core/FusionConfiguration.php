<?php
declare(strict_types=1);

namespace Neos\Fusion\Core;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * This holds the parsed Fusion Configuration and can be used to pass it to the Runtime via
 * {@see RuntimeFactory::createFromConfiguration()}
 * The contents of this DTO are internal and can change at any time!
 */
final class FusionConfiguration
{
    /** @internal */
    protected function __construct(
        private array $fusionConfiguration
    ) {
    }

    /** @internal */
    public static function fromArray(array $fusionConfiguration)
    {
        return new static($fusionConfiguration);
    }

    /** @internal */
    public function toArray()
    {
        return $this->fusionConfiguration;
    }
}
