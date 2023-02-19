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
use Neos\Fusion\Core\Runtime;

/** @Flow\Proxy(false) */
final class LazySelfReferentialProps implements \ArrayAccess, \Stringable
{
    /** @var array<string, LazyReference>  */
    private array $lazyReferences = [];

    public function __construct(
        private string $parentPath,
        private Runtime $runtime,
        private array $effectiveContext,
        private string $selfReferentialId
    ) {
        $this->effectiveContext[$this->selfReferentialId] = $this;
    }

    public function offsetGet($path): mixed
    {
        $fullyQualifiedPath = $this->parentPath . '/' . $path;

        $this->lazyReferences[$path] ??= new LazyReference(
            function () use ($fullyQualifiedPath) {
                $this->runtime->pushContextArray($this->effectiveContext);
                try {
                    return $this->runtime->evaluate($fullyQualifiedPath);
                } finally {
                    $this->runtime->popContext();
                }
            }
        );

        try {
            return $this->lazyReferences[$path]->deref();
        } catch (CircularReferenceException) {
            throw new \RuntimeException(
                'Circular reference detected while evaluating: "' . $this->selfReferentialId . '.' . $path . '"',
                1669654158
            );
        }
    }

    public function offsetExists($offset)
    {
        return $this->offsetGet($offset) !== null;
    }

    public function offsetSet($path, $value): void
    {
        throw new \BadMethodCallException('Lazy props can not be set.', 1669821835);
    }

    public function offsetUnset($path): void
    {
        throw new \BadMethodCallException('Lazy props can not be unset.', 1669821836);
    }

    public function __toString(): string
    {
        return "$this->selfReferentialId props [$this->parentPath]";
    }
}
