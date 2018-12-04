<?php
namespace Neos\ContentRepository\Domain\Model\IntraDimension;

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
     * @param ContentDimensionValue|null $fallback
     * @return ContentDimensionValue
     */
    public function createValue(string $value, ContentDimensionValue $fallback = null): ContentDimensionValue
    {
        $contentDimensionValue = new ContentDimensionValue($value, $fallback);
        if ($fallback) {
            $fallback->registerVariant($contentDimensionValue);
            $this->depth = max($this->depth, $contentDimensionValue->getDepth());
        }
        $this->valueRegistry[$contentDimensionValue->getValue()] = $contentDimensionValue;

        return $contentDimensionValue;
    }

    /**
     * @return array|ContentDimensionValue[]
     */
    public function getValues(): array
    {
        return $this->valueRegistry;
    }

    /**
     * @param string $value
     * @return ContentDimensionValue|null
     */
    public function getValue(string $value)
    {
        return $this->valueRegistry[$value] ?? null;
    }

    /**
     * @return array|ContentDimensionValue[]
     */
    public function getRootValues(): array
    {
        return array_filter($this->valueRegistry, function (ContentDimensionValue $dimensionValue) {
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
