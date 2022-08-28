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

/**
 * A set of content dimension constraints, indexed by dimension identifier
 *
 * @implements \IteratorAggregate<string,ContentDimensionConstraints>
 * @internal
 */
final class ContentDimensionConstraintSet implements \IteratorAggregate
{
    /**
     * @var array<string,ContentDimensionConstraints>
     */
    private array $constraints;

    /**
     * @param array<string,ContentDimensionConstraints> $array
     */
    public function __construct(array $array)
    {
        foreach ($array as $dimensionIdentifier => $constraints) {
            if (!is_string($dimensionIdentifier) || empty($dimensionIdentifier)) {
                throw new \InvalidArgumentException(
                    'ContentDimensionConstraintSets must be indexed by dimension identifier',
                    1639654304
                );
            }
            if (!$constraints instanceof ContentDimensionConstraints) {
                throw new \InvalidArgumentException(
                    'ContentDimensionConstraintSets may only contain ContentDimensionConstraints objects',
                    1639654348
                );
            }
        }

        $this->constraints = $array;
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    /**
     * @return \ArrayIterator<string,ContentDimensionConstraints>|ContentDimensionConstraints[]
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->constraints);
    }

    public function getConstraints(ContentDimensionIdentifier $dimensionIdentifier): ?ContentDimensionConstraints
    {
        return $this->constraints[(string)$dimensionIdentifier] ?? null;
    }

    public function allowsCombinationWith(
        ContentDimensionIdentifier $contentDimensionIdentifier,
        ContentDimensionValue $contentDimensionValue
    ): bool {
        return isset($this->constraints[(string)$contentDimensionIdentifier])
            ? $this->constraints[(string)$contentDimensionIdentifier]->allowsCombinationWith($contentDimensionValue)
            : true;
    }
}
