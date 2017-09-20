<?php
namespace Neos\ContentRepository\Domain\Context\DimensionSpace\Repository;

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
use Neos\ContentRepository\Domain;

/**
 * The content subgraph domain model
 */
class ContentSubgraph
{
    /**
     * @var array|Dimension\Model\ContentDimensionValue[]
     */
    protected $dimensionValues = [];

    /**
     * @var Domain\ValueObject\DimensionSpacePoint
     */
    protected $identifier;

    /**
     * @var array
     */
    protected $fallbackEdges = [];

    /**
     * @var array
     */
    protected $variantEdges = [];

    /**
     * @var array
     */
    protected $weight;


    /**
     * @param array|Dimension\Model\ContentDimensionValue[] $dimensionValues
     */
    public function __construct(array $dimensionValues)
    {
        $coordinates = [];
        foreach ($dimensionValues as $dimensionName => $dimensionValue) {
            $this->dimensionValues[$dimensionName] = $dimensionValue;
            $coordinates[$dimensionName] = $dimensionValue->getValue();
            $this->weight[$dimensionName] = $dimensionValue->getDepth();
        }
        $this->identifier = new Domain\ValueObject\DimensionSpacePoint($coordinates);
    }

    /**
     * @return Domain\ValueObject\DimensionSpacePoint
     */
    public function getIdentifier(): Domain\ValueObject\DimensionSpacePoint
    {
        return $this->identifier;
    }

    /**
     * @return array|Dimension\Model\ContentDimensionValue[]
     */
    public function getDimensionValues(): array
    {
        return $this->dimensionValues;
    }

    /**
     * @param string $dimensionName
     * @return Dimension\Model\ContentDimensionValue
     */
    public function getDimensionValue($dimensionName): Dimension\Model\ContentDimensionValue
    {
        return $this->dimensionValues[$dimensionName];
    }

    /**
     * @return string
     */
    public function getIdentityHash(): string
    {
        return $this->identifier->getHash();
    }

    /**
     * @return array
     */
    public function getWeight(): array
    {
        return $this->weight;
    }

    /**
     * @param VariationEdge $variant
     * @return void
     */
    public function registerVariantEdge(VariationEdge $variant)
    {
        $this->variantEdges[$variant->getVariant()->getIdentityHash()] = $variant;
    }

    /**
     * @return array|VariationEdge[]
     */
    public function getVariantEdges(): array
    {
        return $this->variantEdges;
    }

    /**
     * @param VariationEdge $fallback
     * @return void
     */
    public function registerFallbackEdge(VariationEdge $fallback)
    {
        $this->fallbackEdges[$fallback->getFallback()->getIdentityHash()] = $fallback;
    }

    /**
     * @return array|VariationEdge[]
     */
    public function getFallbackEdges(): array
    {
        return $this->fallbackEdges;
    }

    /**
     * @return array|ContentSubgraph[]
     */
    public function getVariants(): array
    {
        $variants = [];
        foreach ($this->getVariantEdges() as $variantEdge) {
            $variants[$variantEdge->getVariant()->getIdentityHash()] = $variantEdge->getVariant();
        }

        return $variants;
    }

    /**
     * @return array|ContentSubgraph[]
     */
    public function getFallback(): array
    {
        $fallback = [];
        foreach ($this->getFallbackEdges() as $fallbackEdge) {
            $fallback[$fallbackEdge->getFallback()->getIdentityHash()] = $fallbackEdge->getFallback();
        }

        return $fallback;
    }
}
