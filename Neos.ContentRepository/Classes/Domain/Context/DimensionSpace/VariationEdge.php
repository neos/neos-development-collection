<?php
namespace Neos\ContentRepository\Domain\Context\DimensionSpace;

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
 * May serve as a generalization edge for specializations or as a specialization edge for generalizations
 */
final class VariationEdge
{
    /**
     * @var ContentSubgraph
     */
    protected $specialization;

    /**
     * @var ContentSubgraph
     */
    protected $generalization;

    /**
     * @var array
     */
    protected $weight;

    /**
     * @param ContentSubgraph $specialization
     * @param ContentSubgraph $generalization
     * @param array $weight
     */
    public function __construct(ContentSubgraph $specialization, ContentSubgraph $generalization, array $weight)
    {
        $this->specialization = $specialization;
        $this->generalization = $generalization;
        $this->weight = $weight;
    }

    /**
     * @return ContentSubgraph
     */
    public function getSpecialization(): ContentSubgraph
    {
        return $this->specialization;
    }

    /**
     * @return ContentSubgraph
     */
    public function getGeneralization(): ContentSubgraph
    {
        return $this->generalization;
    }

    /**
     * @return array
     */
    public function getWeight(): array
    {
        return $this->weight;
    }
}
