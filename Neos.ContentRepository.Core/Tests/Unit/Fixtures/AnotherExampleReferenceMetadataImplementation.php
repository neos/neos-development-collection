<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\Fixtures;

use Neos\ContentRepository\Core\Infrastructure\Declaration\ReferenceTypeDeclaration\ReferenceMetadataDeclarationInterface;
use Neos\ContentRepository\Core\ReferenceType\ReferenceMetadataInterface;

final class AnotherExampleReferenceMetadataImplementation implements ReferenceMetadataDeclarationInterface
{
    public function mergeToSubType($subTypeMetadata): static
    {
        return new self();
    }

    public function tryExtractFromArray(array &$declaration): ?static
    {
        return new self();
    }

    public function toMetadata(): ReferenceMetadataInterface
    {
        return new class () implements ReferenceMetadataInterface {
            public function jsonSerialize(): mixed
            {
                return null;
            }
        };
    }

    public function jsonSerialize(): mixed
    {
        return null;
    }
}
