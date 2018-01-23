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

use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\ContentRepository\Domain;

/**
 * The content subgraph domain model
 */
final class ContentSubgraph
{
    /**
     * @var array|Dimension\ContentDimensionValue[]
     */
    protected $dimensionValues = [];

    /**
     * @var Domain\ValueObject\DimensionSpacePoint
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
     * @param array|Dimension\ContentDimensionValue[] $dimensionValues
     */
    public function __construct(array $dimensionValues)
    {
        $coordinates = [];
        foreach ($dimensionValues as $dimensionName => $dimensionValue) {
            $this->dimensionValues[$dimensionName] = $dimensionValue;
            $coordinates[$dimensionName] = $dimensionValue->getValue();
            $this->weight[$dimensionName] = $dimensionValue->getSpecializationDepth();
        }
        $this->dimensionSpacePoint = new Domain\ValueObject\DimensionSpacePoint($coordinates);
    }

    /**
     * @return Domain\ValueObject\DimensionSpacePoint
     */
    public function getIdentifier():Domain\ValueObject\ DimensionSpacePoint
    {
        return $this->getDimensionSpacePoint();
    }

    /**
     * @return Domain\ValueObject\DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): Domain\ValueObject\DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    /**
     * @return array|Dimension\ContentDimensionValue[]
     */
    public function getDimensionValues(): array
    {
        return $this->dimensionValues;
    }

    /**
     * @param Dimension\ContentDimensionIdentifier $dimensionIdentifier
     * @return Dimension\ContentDimensionValue
     */
    public function getDimensionValue(Dimension\ContentDimensionIdentifier $dimensionIdentifier): ?Dimension\ContentDimensionValue
    {
        return $this->dimensionValues[(string) $dimensionIdentifier] ?? null;
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
     * @param ContentSubgraph $specialization
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
