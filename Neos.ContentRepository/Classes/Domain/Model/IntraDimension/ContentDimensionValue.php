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
 * The content dimension value domain model
 */
class ContentDimensionValue
{
    /**
     * @var string
     */
    protected $value;

    /**
     * @var ContentDimensionValue
     */
    protected $fallback;

    /**
     * @var array
     */
    protected $variants = [];

    /**
     * @var int
     */
    protected $depth = 0;

    /**
     * @param string $value
     * @param ContentDimensionValue|null $fallback
     */
    public function __construct(string $value, ContentDimensionValue $fallback = null)
    {
        $this->value = $value;
        if ($fallback) {
            $this->fallback = $fallback;
            $this->depth = $fallback->getDepth() + 1;
        }
    }

    /**
     * @param ContentDimensionValue $variant
     * @return void
     */
    public function registerVariant(ContentDimensionValue $variant)
    {
        $this->variants[$variant->getValue()] = $variant;
    }

    /**
     * @return array|ContentDimensionValue[]
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

    /**
     * @return ContentDimensionValue|null
     */
    public function getFallback()
    {
        return $this->fallback;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return int
     */
    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * @param ContentDimensionValue $fallback
     * @return int
     * @throws Exception\InvalidFallbackException
     */
    public function calculateFallbackDepth(ContentDimensionValue $fallback): int
    {
        $fallbackDepth = 0;
        $fallbackFound = false;
        $currentFallback = $this;
        while ($currentFallback && !$fallbackFound) {
            if ($currentFallback === $fallback) {
                $fallbackFound = true;
            } else {
                $currentFallback = $currentFallback->getFallback();
                $fallbackDepth++;
            }
        }
        if (!$fallbackFound) {
            throw new Exception\InvalidFallbackException();
        }

        return $fallbackDepth;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
