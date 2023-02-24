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
