<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\CriteriaParser;

/**
 * Parser token for the @see PropertyValueCriteriaParser
 *
 * @internal
 */
final class Token
{
    public function __construct(
        public readonly TokenType $type,
        public readonly string $value,
        public readonly int $offsetStart,
        public readonly int $offsetEnd,
    ) {
    }
}
