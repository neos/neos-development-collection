<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\ReferenceLabel;

use Neos\ContentRepository\Core\ReferenceType\ReferenceMetadataInterface;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final readonly class ReferenceLabelMetadata implements ReferenceMetadataInterface
{
    /**
     * @param class-string<ReferenceLabelGeneratorInterface>|null $generatorClassName
     */
    private function __construct(
        public ?string $label,
        public ?string $generatorClassName,
    ) {
    }

    /**
     * @param class-string<ReferenceLabelGeneratorInterface>|null $generatorClassName
     */
    public static function create(
        ?string $label,
        ?string $generatorClassName,
    ): self {
        return new self(
            label: $label,
            generatorClassName: $generatorClassName,
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
