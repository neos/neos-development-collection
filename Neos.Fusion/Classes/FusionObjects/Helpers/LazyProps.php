<?php

namespace Neos\Fusion\FusionObjects\Helpers;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

/** @Flow\Proxy(false) */
final class LazyProps extends AbstractLazyProps
{
    private array $valueCache = [];

    public function __construct(
        private AbstractFusionObject $fusionObject,
        private string $parentPath,
        private Runtime $runtime,
        array $keys,
        private array $effectiveContext
    ) {
        parent::__construct($keys);
    }

    public function offsetGet($path): mixed
    {
        if (!array_key_exists($path, $this->valueCache)) {
            $this->runtime->pushContextArray($this->effectiveContext);
            try {
                $this->valueCache[$path] = $this->runtime->evaluate($this->parentPath . '/' . $path, $this->fusionObject);
            } finally {
                $this->runtime->popContext();
            }
        }
        return $this->valueCache[$path];
    }
}
