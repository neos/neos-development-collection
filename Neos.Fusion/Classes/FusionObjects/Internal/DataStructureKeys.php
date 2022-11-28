<?php

namespace Neos\Fusion\FusionObjects\Internal;

class DataStructureKeys
{
    public function __construct(
        private array $keys
    ) {
    }

    public function getKeys(): array
    {
        return $this->keys;
    }
}
