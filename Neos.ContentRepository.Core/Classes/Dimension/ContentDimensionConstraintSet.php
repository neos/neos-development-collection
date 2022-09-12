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

namespace Neos\ContentRepository\Core\Dimension;

/**
 * A set of content dimension constraints, indexed by dimension id
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
        foreach ($array as $dimensionId => $constraints) {
            if (!is_string($dimensionId) || empty($dimensionId)) {
                throw new \InvalidArgumentException(
                    'ContentDimensionConstraintSets must be indexed by dimension id',
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

    public function getConstraints(ContentDimensionId $dimensionId): ?ContentDimensionConstraints
    {
        return $this->constraints[(string)$dimensionId] ?? null;
    }

    public function allowsCombinationWith(
        ContentDimensionId $contentDimensionId,
        ContentDimensionValue $contentDimensionValue
    ): bool {
        return isset($this->constraints[(string)$contentDimensionId])
            ? $this->constraints[(string)$contentDimensionId]->allowsCombinationWith($contentDimensionValue)
            : true;
    }
}
