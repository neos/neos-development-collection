<?php

namespace Neos\Fusion\FusionObjects\Helpers;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\Runtime;

/** @Flow\Proxy(false) */
final class LazySelfReferentialProps extends AbstractLazyProps
{
    private array $valueCache = [];

    private array $currentlyEvaluatingIndexes = [];

    public function __construct(
        private string $parentPath,
        array $keys,
        private Runtime $runtime,
        private array $effectiveContext,
        private string $selfReferentialId
    ) {
        parent::__construct($keys);
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
}
