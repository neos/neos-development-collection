<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\Declaration\ReferenceTypeDeclaration;

use Neos\ContentRepository\Core\ReferenceType\ReferenceMetadataInterface;

/**
 * @api to be implemented in userland
 */
interface ReferenceMetadataDeclarationInterface extends \JsonSerializable
{
    /**
     * Metadata declarations may be extracted from a declaration array
     *
     * @param array<string,mixed> $declaration
     */
    public static function tryExtractFromArray(array &$declaration): ?static;

    /**
     * Metadata declarations may be merged from supertype to subtype
     * @param self $subTypeMetadata
     */
    public function mergeToSubType($subTypeMetadata): static;

    public function toMetadata(): ReferenceMetadataInterface;
}
