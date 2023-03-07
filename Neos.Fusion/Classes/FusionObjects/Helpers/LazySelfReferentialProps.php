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

/**
 * A proxy for Fusions private props.
 * It lazily evaluates a path when accessed via the passed $effectiveContext the context `this` is not set.
 * One can reference another private prop when evaluating - a circular reference will result in an Exception.
 * If a private prop is not defined but accessed an Exception will also be thrown.
 * The private props are not Traversable but only a Proxy as it's not trivial to know all the keys beforehand
 * and using a Neos.Fusion:DataStructure wouldn't be lazy.
 *
 * @internal
 * @Flow\Proxy(false)
 */
final class LazySelfReferentialProps implements \ArrayAccess, \Stringable
{
    /** @var array<string, LazyReference> */
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

        if ($path === "renderer") {
            // let's see what the future holds for this ;)
            throw new \RuntimeException(<<<MESSAGE
                Sorry, the prop name "$this->selfReferentialId.renderer" is reserved and cannot be used.
                At path "$fullyQualifiedPath".
                MESSAGE, 1677361824);
        }

        if (!$this->runtime->canRender($fullyQualifiedPath)) {
            throw new \RuntimeException(<<<MESSAGE
                Cannot evaluate prop: "$this->selfReferentialId.$path".
                No value found in path "$fullyQualifiedPath".
                MESSAGE, 1677344049);
        }

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
                'Circular reference detected while evaluating prop: "' . $this->selfReferentialId . '.' . $path . '"',
                1669654158
            );
        }
    }

    public function offsetExists($offset): bool
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
