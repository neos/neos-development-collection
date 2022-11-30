<?php

namespace Neos\Fusion\FusionObjects\Helpers;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\Runtime;

/** @Flow\Proxy(false) */
final class LazySelfReferentialProps implements \ArrayAccess, \Stringable
{
    private array $valueCache = [];

    private array $currentlyEvaluatingIndexes = [];

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
        if (!array_key_exists($path, $this->valueCache)) {
            if (isset($this->currentlyEvaluatingIndexes[$path])) {
                throw new \RuntimeException('Circular reference detected while evaluating: "' . $this->selfReferentialId . '.' . $path . '"', 1669654158);
            }
            $this->currentlyEvaluatingIndexes[$path] = true;
            $this->runtime->pushContextArray($this->effectiveContext);
            try {
                $this->valueCache[$path] = $this->runtime->evaluate($this->parentPath . '/' . $path);
            } finally {
                $this->runtime->popContext();
                unset($this->currentlyEvaluatingIndexes[$path]);
            }
        }
        return $this->valueCache[$path];
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
