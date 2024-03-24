<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\CriteriaParser;

/**
 * Parser token for the @see PropertyValueCriteriaParser
 *
 * @internal
 */
final readonly class Token
{
    public function __construct(
        public TokenType $type,
        public string $value,
        public int $offsetStart,
        public int $offsetEnd,
    ) {
    }
}
