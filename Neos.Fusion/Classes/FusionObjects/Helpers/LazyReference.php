<?php
declare(strict_types=1);
namespace Neos\Fusion\FusionObjects\Helpers;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * @internal
 * @Flow\Proxy(false)
 */
final class LazyReference
{
    private bool $isLocked = false;

    private bool $hasBeenDereferenced = false;

    private mixed $value = null;

    public function __construct(
        private \Closure $calculateValueCallback
    ) {
    }

    public function deref(): mixed
    {
        if ($this->hasBeenDereferenced) {
            return $this->value;
        }

        if ($this->isLocked) {
            throw new CircularReferenceException("Reference is locked", 1676820077);
        }

        $this->isLocked = true;
        try {
            $this->value = ($this->calculateValueCallback)();
        } finally {
            $this->hasBeenDereferenced = true;
        }

        return $this->value;
    }
}
