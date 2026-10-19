<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Property;

/**
 * @api to be implemented in userland
 */
interface PropertyMetadataInterface extends \JsonSerializable
{
    /**
     * Metadata may be merged from supertype to subtype
     * @param static $subTypeMetadata
     */
    public function mergeToSubType($subTypeMetadata): static;
}
