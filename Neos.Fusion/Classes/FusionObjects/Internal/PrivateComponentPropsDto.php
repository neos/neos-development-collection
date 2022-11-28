<?php

namespace Neos\Fusion\FusionObjects\Internal;

class PrivateComponentPropsDto
{
    private string $path;

    private array $propertyKeys;

    public function __construct(string $path, array $propertyKeys)
    {
        $this->path = $path;
        $this->propertyKeys = $propertyKeys;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getPropertyKeys(): array
    {
        return $this->propertyKeys;
    }
}
