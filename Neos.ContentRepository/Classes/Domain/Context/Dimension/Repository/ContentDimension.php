<?php
namespace Neos\ContentRepository\Domain\Context\Dimension\Repository;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\Dimension;

/**
 * The content dimension domain model
 */
class ContentDimension
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var array
     */
    protected $valueRegistry = [];

    /**
     * @var int
     */
    protected $depth = 0;

    /**
     * @param string $name
     * @param null $label
     */
    public function __construct(string $name, $label = null)
    {
        $this->name = $name;
        $this->label = $label ?: $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $value
     * @param Dimension\Model\ContentDimensionValue|null $fallback
     * @return Dimension\Model\ContentDimensionValue
     */
    public function createValue(string $value, Dimension\Model\ContentDimensionValue $fallback = null): Dimension\Model\ContentDimensionValue
    {
        $contentDimensionValue = new Dimension\Model\ContentDimensionValue($value, $fallback);
        if ($fallback) {
            $fallback->registerVariant($contentDimensionValue);
            $this->depth = max($this->depth, $contentDimensionValue->getDepth());
        }
        $this->valueRegistry[$contentDimensionValue->getValue()] = $contentDimensionValue;

        return $contentDimensionValue;
    }

    /**
     * @return array|Dimension\Model\ContentDimensionValue[]
     */
    public function getValues(): array
    {
        return $this->valueRegistry;
    }

    /**
     * @param string $value
     * @return Dimension\Model\ContentDimensionValue|null
     */
    public function getValue(string $value)
    {
        return isset($this->valueRegistry[$value]) ? $this->valueRegistry[$value] : null;
    }

    /**
     * @return array|Dimension\Model\ContentDimensionValue[]
     */
    public function getRootValues(): array
    {
        return array_filter($this->valueRegistry, function (Dimension\Model\ContentDimensionValue $dimensionValue) {
            return $dimensionValue->getDepth() === 0;
        });
    }

    /**
     * @return int
     */
    public function getDepth(): int
    {
        return $this->depth;
    }
}
