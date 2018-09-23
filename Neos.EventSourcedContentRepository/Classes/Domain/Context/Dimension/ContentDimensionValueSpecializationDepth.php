<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\Dimension;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * The content dimension value specialization depth value object
 */
final class ContentDimensionValueSpecializationDepth implements \JsonSerializable
{
    /**
     * @var int
     */
    protected $depth;

    /**
     * @param int $depth
     * @throws Exception\InvalidContentDimensionValueSpecializationDepthException
     */
    public function __construct(int $depth)
    {
        if ($depth < 0) {
            throw new Exception\InvalidContentDimensionValueSpecializationDepthException('Specialization depths cannot be negative.', 1516573132);
        }
        $this->depth = $depth;
    }

    /**
     * @param ContentDimensionValueSpecializationDepth $otherDepth
     * @return bool
     */
    public function isGreaterThan(ContentDimensionValueSpecializationDepth $otherDepth): bool
    {
        return $this->depth > $otherDepth->getDepth();
    }

    /**
     * @return bool
     */
    public function isZero(): bool
    {
        return $this->depth === 0;
    }

    /**
     * @return int
     */
    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * @return ContentDimensionValueSpecializationDepth
     */
    public function increment(): ContentDimensionValueSpecializationDepth
    {
        return new ContentDimensionValueSpecializationDepth($this->depth + 1);
    }

    /**
     * @param ContentDimensionValueSpecializationDepth $otherDepth
     * @return ContentDimensionValueSpecializationDepth
     */
    public function decreaseBy(ContentDimensionValueSpecializationDepth $otherDepth)
    {
        return new ContentDimensionValueSpecializationDepth($this->depth - $otherDepth->getDepth());
    }

    /**
     * @return int
     */
    public function jsonSerialize(): int
    {
        return $this->depth;
    }
}
