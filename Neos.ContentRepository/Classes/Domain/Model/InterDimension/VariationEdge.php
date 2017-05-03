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

/**
 * The variation edge domain model
 * May serve as a fallback edge for variants or as a variant edge for fallbacks
 */
class VariationEdge
{
    /**
     * @var ContentSubgraph
     */
    protected $variant;

    /**
     * @var ContentSubgraph
     */
    protected $fallback;

    /**
     * @var array
     */
    protected $weight;


    public function __construct(ContentSubgraph $variant, ContentSubgraph $fallback, array $weight)
    {
        $this->variant = $variant;
        $this->fallback = $fallback;
        $this->weight = $weight;
        $variant->registerFallbackEdge($this);
        $fallback->registerVariantEdge($this);
    }


    public function getVariant(): ContentSubgraph
    {
        return $this->variant;
    }

    public function getFallback(): ContentSubgraph
    {
        return $this->fallback;
    }

    public function getWeight(): array
    {
        return $this->weight;
    }
}
