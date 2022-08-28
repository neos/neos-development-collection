<?php

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Neos\ContentRepository\Tests\Unit\DimensionSpace\Fixtures;

use Neos\ContentRepository\Dimension;

/**
 * The dimension source fixture with variation examples
 */
class VariationExampleDimensionSource implements Dimension\ContentDimensionSourceInterface
{
    /**
     * @var array<string,Dimension\ContentDimension>
     */
    protected array $dimensions = [];

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
                new Dimension\ContentDimensionValues([
                    $dimensionAValue1->value => $dimensionAValue1,
                    $dimensionAValue11->value => $dimensionAValue11,
                    $dimensionAValue12->value => $dimensionAValue12,
                    $dimensionAValue111->value => $dimensionAValue111
                ]),
                new Dimension\ContentDimensionValueVariationEdges([
                    new Dimension\ContentDimensionValueVariationEdge($dimensionAValue11, $dimensionAValue1),
                    new Dimension\ContentDimensionValueVariationEdge($dimensionAValue12, $dimensionAValue1),
                    new Dimension\ContentDimensionValueVariationEdge($dimensionAValue111, $dimensionAValue11)
                ])
            ),
            'dimensionB' => new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('dimensionB'),
                new Dimension\ContentDimensionValues([
                    $dimensionBValue1->value => $dimensionBValue1,
                    $dimensionBValue11->value => $dimensionBValue11,
                    $dimensionBValue12->value => $dimensionBValue12,
                    $dimensionBValue111->value => $dimensionBValue111
                ]),
                new Dimension\ContentDimensionValueVariationEdges([
                    new Dimension\ContentDimensionValueVariationEdge($dimensionBValue11, $dimensionBValue1),
                    new Dimension\ContentDimensionValueVariationEdge($dimensionBValue12, $dimensionBValue1),
                    new Dimension\ContentDimensionValueVariationEdge($dimensionBValue111, $dimensionBValue11)
                ])
            )
        ];
    }

    public function getDimension(Dimension\ContentDimensionIdentifier $dimensionIdentifier): ?Dimension\ContentDimension
    {
        if (!$this->dimensions) {
            $this->initializeDimensions();
        }

        return $this->dimensions[(string)$dimensionIdentifier] ?? null;
    }

    /**
     * @return array<string,Dimension\ContentDimension>
     */
    public function getContentDimensionsOrderedByPriority(): array
    {
        if (!$this->dimensions) {
            $this->initializeDimensions();
        }

        return $this->dimensions;
    }
}
