<?php

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Dimension;

use Neos\ContentRepository\Dimension\Exception\ContentDimensionValuesAreInvalid;

/**
 * A collection of content dimension values, indexed by dimension identifier
 *
 * @implements \IteratorAggregate<string,ContentDimensionValue>
 * @internal
 */
final class ContentDimensionValues implements \IteratorAggregate
{
    /**
     * The actual values, indexed by string representation
     * @var array<string,ContentDimensionValue>
     */
    public readonly array $values;

    public readonly ContentDimensionValueSpecializationDepth $maximumDepth;

    /**
     * @param array<string,ContentDimensionValue> $values
     */
    public function __construct(array $values)
    {
        if (empty($values)) {
            throw ContentDimensionValuesAreInvalid::becauseTheyMustNotBeEmpty();
        }
        $maximumDepth = ContentDimensionValueSpecializationDepth::zero();
        $indexedValues = [];
        foreach ($values as $dimensionValue) {
            if (!$dimensionValue instanceof ContentDimensionValue) {
                throw new \InvalidArgumentException(
                    'ContentDimensionValues may only contain ContentDimensionValue objects',
                    1642855362
                );
            }
            $indexedValues[$dimensionValue->value] = $dimensionValue;
            if ($dimensionValue->specializationDepth->isGreaterThan($maximumDepth)) {
                $maximumDepth = $dimensionValue->specializationDepth;
            }
        }

        $this->values = $indexedValues;
        $this->maximumDepth = $maximumDepth;
    }

    /**
     * @return \ArrayIterator<string,ContentDimensionValue>|ContentDimensionValue[]
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->values);
    }

    public function getValue(string $value): ?ContentDimensionValue
    {
        return $this->values[$value] ?? null;
    }

    /**
     * @return array<string,ContentDimensionValue>
     */
    public function getRootValues(): array
    {
        return array_filter(
            $this->values,
            function (ContentDimensionValue $value): bool {
                return $value->specializationDepth->isZero();
            }
        );
    }
}
