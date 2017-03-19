<?php
namespace Neos\ContentRepository\Domain\Model\InterDimension;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\IntraDimension;
use Neos\ContentRepository\Domain\Utility\SubgraphUtility;
use Neos\Flow\Annotations as Flow;

/**
 * The content subgraph domain model
 */
class ContentSubgraph
{
    /**
     * @var array|IntraDimension\ContentDimensionValue[]
     */
    protected $dimensionValues = [];

    /**
     * @var string
     */
    protected $identityHash;

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
     * @param array|IntraDimension\ContentDimensionValue[] $dimensionValues
     */
    public function __construct(array $dimensionValues)
    {
        $identityComponents = [];
        foreach ($dimensionValues as $dimensionName => $dimensionValue) {
            $this->dimensionValues[$dimensionName] = $dimensionValue;
            $identityComponents[$dimensionName] = $dimensionValue->getValue();
            $this->weight[$dimensionName] = $dimensionValue->getDepth();
        }
        $this->identityHash = SubgraphUtility::hashIdentityComponents($identityComponents);
    }


    /**
     * @return array|IntraDimension\ContentDimensionValue[]
     */
    public function getDimensionValues(): array
    {
        return $this->dimensionValues;
    }

    public function getDimensionValue($dimensionName): IntraDimension\ContentDimensionValue
    {
        return $this->dimensionValues[$dimensionName];
    }

    public function getIdentityHash(): string
    {
        return $this->identityHash;
    }

    public function getWeight(): array
    {
        return $this->weight;
    }

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
