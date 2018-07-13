<?php

namespace Neos\ContentRepository\DimensionSpace\Tests\Unit\DimensionSpace\Fixtures;

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\DimensionSpace\Dimension;

/**
 * The dimension source fixture with variation examples
 */
class VariationExampleDimensionSource implements Dimension\ContentDimensionSourceInterface
{
    /**
     * @var array|Dimension\ContentDimension[]
     */
    protected $dimensions;

    protected function initializeDimensions()
    {
        $dimensionAValue1 = new Dimension\ContentDimensionValue('value1', new Dimension\ContentDimensionValueSpecializationDepth(0));
        $dimensionAValue11 = new Dimension\ContentDimensionValue('value1.1', new Dimension\ContentDimensionValueSpecializationDepth(1));
        $dimensionAValue12 = new Dimension\ContentDimensionValue('value1.2', new Dimension\ContentDimensionValueSpecializationDepth(1));
        $dimensionAValue111 = new Dimension\ContentDimensionValue('value1.1.1', new Dimension\ContentDimensionValueSpecializationDepth(2));

        $dimensionBValue1 = new Dimension\ContentDimensionValue('value1', new Dimension\ContentDimensionValueSpecializationDepth(0));
        $dimensionBValue11 = new Dimension\ContentDimensionValue('value1.1', new Dimension\ContentDimensionValueSpecializationDepth(1));
        $dimensionBValue12 = new Dimension\ContentDimensionValue('value1.2', new Dimension\ContentDimensionValueSpecializationDepth(1));
        $dimensionBValue111 = new Dimension\ContentDimensionValue('value1.1.1', new Dimension\ContentDimensionValueSpecializationDepth(2));

        $this->dimensions = [
            'dimensionA' => new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('dimensionA'),
                [
                    $dimensionAValue1->getValue() => $dimensionAValue1,
                    $dimensionAValue11->getValue() => $dimensionAValue11,
                    $dimensionAValue12->getValue() => $dimensionAValue12,
                    $dimensionAValue111->getValue() => $dimensionAValue111
                ],
                $dimensionAValue1,
                [
                    new Dimension\ContentDimensionValueVariationEdge($dimensionAValue11, $dimensionAValue1),
                    new Dimension\ContentDimensionValueVariationEdge($dimensionAValue12, $dimensionAValue1),
                    new Dimension\ContentDimensionValueVariationEdge($dimensionAValue111, $dimensionAValue11)
                ]
            ),
            'dimensionB' => new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('dimensionB'),
                [
                    $dimensionBValue1->getValue() => $dimensionBValue1,
                    $dimensionBValue11->getValue() => $dimensionBValue11,
                    $dimensionBValue12->getValue() => $dimensionBValue12,
                    $dimensionBValue111->getValue() => $dimensionBValue111
                ],
                $dimensionBValue1,
                [
                    new Dimension\ContentDimensionValueVariationEdge($dimensionBValue11, $dimensionBValue1),
                    new Dimension\ContentDimensionValueVariationEdge($dimensionBValue12, $dimensionBValue1),
                    new Dimension\ContentDimensionValueVariationEdge($dimensionBValue111, $dimensionBValue11)
                ]
            )
        ];
    }

    /**
     * @param Dimension\ContentDimensionIdentifier $dimensionIdentifier
     * @return Dimension\ContentDimension|null
     */
    public function getDimension(Dimension\ContentDimensionIdentifier $dimensionIdentifier): ?Dimension\ContentDimension
    {
        if (!$this->dimensions) {
            $this->initializeDimensions();
        }

        return $this->dimensions[(string)$dimensionIdentifier] ?? null;
    }

    /**
     * @return array|Dimension\ContentDimension[]
     */
    public function getContentDimensionsOrderedByPriority(): array
    {
        if (!$this->dimensions) {
            $this->initializeDimensions();
        }

        return $this->dimensions;
    }
}
