<?php
declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration;

use Neos\ContentRepository\Core\NodeType\NodeTypeName;

class RootNodeTypeMapping
{
    /**
     * @param array<string, string> $mapping
     */
    private function __construct(
        public readonly array $mapping,
    ) {
    }

    /**
     * @param array<string, string> $mapping
     * @return self
     */
    public static function fromArray(array $mapping): self
    {
        return new self($mapping);
    }

    public function getByPath(string $path): ?NodeTypeName
    {
        return isset($this->mapping[$path]) ? NodeTypeName::fromString($this->mapping[$path]) : null;
    }
}
