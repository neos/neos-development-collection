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
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;

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
     * @var DimensionSpacePoint
     */
    protected $dimensionSpacePoint;

    /**
     * @var array
     */
    protected $generalizationEdges = [];

    /**
     * @var array
     */
    protected $specializationEdges = [];

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
        $this->dimensionSpacePoint = new DimensionSpacePoint($coordinates);
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getIdentifier(): DimensionSpacePoint
    {
        return $this->getDimensionSpacePoint();
    }

    /**
     * @return DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
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
        return $this->dimensionSpacePoint->getHash();
    }

    /**
     * @return array
     */
    public function getWeight(): array
    {
        return $this->weight;
    }

    /**
     * @param VariationEdge $specializationEdge
     * @return void
     */
    public function registerSpecializationEdge(VariationEdge $specializationEdge)
    {
        $this->specializationEdges[$specializationEdge->getSpecialization()->getIdentityHash()] = $specializationEdge;
    }

    /**
     * @return array|VariationEdge[]
     */
    public function getSpecializationEdges(): array
    {
        return $this->specializationEdges;
    }

    /**
     * @param VariationEdge $generalizationEdge
     * @return void
     */
    public function registerGeneralizationEdge(VariationEdge $generalizationEdge)
    {
        $this->generalizationEdges[$generalizationEdge->getGeneralization()->getIdentityHash()] = $generalizationEdge;
    }

    /**
     * @return array|VariationEdge[]
     */
    public function getGeneralizationEdges(): array
    {
        return $this->generalizationEdges;
    }

    /**
     * @return array|ContentSubgraph[]
     */
    public function getSpecializations(): array
    {
        $specializations = [];
        foreach ($this->getSpecializationEdges() as $specializationEdge) {
            $specializations[$specializationEdge->getSpecialization()->getIdentityHash()] = $specializationEdge->getSpecialization();
        }

        return $specializations;
    }

    /**
     * @return bool
     */
    public function hasSpecialization(ContentSubgraph $specialization): bool
    {
        return isset($this->specializationEdges[$specialization->getIdentityHash()]);
    }

    /**
     * @return array|ContentSubgraph[]
     */
    public function getGeneralizations(): array
    {
        $generalizations = [];
        foreach ($this->getGeneralizationEdges() as $generalizationEdge) {
            $generalizations[$generalizationEdge->getGeneralization()->getIdentityHash()] = $generalizationEdge->getGeneralization();
        }

        return $generalizations;
    }
}
