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
 *
 * @internal The Fusion parsing is considered internal.
 */
final class FusionConfiguration
{
    /**
     * @internal
     * @param array<int|string, mixed> $fusionConfiguration The representation of the configuration as array is fully internal and only allowed to be accessed by the Runtime
     */
    private function __construct(
        private array $fusionConfiguration
    ) {
    }

    /**
     * @internal
     * @param array<int|string, mixed> $fusionConfiguration
     */
    public static function fromArray(array $fusionConfiguration): self
    {
        return new self($fusionConfiguration);
    }

    /**
     * @internal
     * @return array<int|string, mixed>
     */
    public function toArray(): array
    {
        return $this->fusionConfiguration;
    }
}
