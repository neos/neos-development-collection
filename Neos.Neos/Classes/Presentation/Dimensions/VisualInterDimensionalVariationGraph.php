<?php
namespace Neos\Neos\Presentation\Dimensions;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimension;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionValue;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;

/**
 * The visualization model for the interdimensional variation graph
 */
final class VisualInterDimensionalVariationGraph
{
    private function __construct(
        /** @var array<string,VisualWeightedDimensionSpacePoint> */
        public readonly array $nodes,
        /** @var array<string,VisualInterDimensionalEdge> */
        public readonly array $edges,
        public readonly int $width,
        public readonly int $height
    ) {
    }

    public static function forInterDimensionalVariationGraph(
        InterDimensionalVariationGraph $variationGraph,
        ContentDimensionSourceInterface $contentDimensionSource
    ): self {
        $nodes = [];
        $edges = [];
        $offsets = [];
        $width = 0;
        $height = 0;

        foreach ($contentDimensionSource->getContentDimensionsOrderedByPriority() as $contentDimension) {
            $identifier = (string)$contentDimension->identifier;
            $offsets[$identifier] = self::resolveOffsets($contentDimension);
        }

        $weightedDimensionSpacePoints = $variationGraph->getWeightedDimensionSpacePoints();
        foreach ($weightedDimensionSpacePoints as $dimensionSpacePoint) {
            $x = 0;
            $y = 0;

            $previousDepthFactor = 1;
            $previousWidthFactor = 1;
            foreach (array_reverse($dimensionSpacePoint->dimensionValues) as $dimensionIdentifier => $dimensionValue) {
                $y += $dimensionValue->specializationDepth->depth * $previousDepthFactor;
                $previousDepthFactor *= $offsets[$dimensionIdentifier]['_height'];

                $x += $offsets[$dimensionIdentifier][$dimensionValue->value]['x'] * $previousWidthFactor;
                $previousWidthFactor *= $offsets[$dimensionIdentifier]['_width'];
            }

            $nameComponents = $dimensionSpacePoint->dimensionSpacePoint->coordinates;

            $x *= 110;
            $y *= 110;

            $nodes[$dimensionSpacePoint->getIdentityHash()] = new VisualWeightedDimensionSpacePoint(
                $dimensionSpacePoint->getIdentityHash(),
                implode(', ', $nameComponents),
                $x + 42,
                $y + 42,
                '#00B5FF'
            );

            $width = max($width, $x + 110);
            $height = max($height, $y + 110);
        }

        foreach ($weightedDimensionSpacePoints as $weightedDimensionSpacePoint) {
            $generalizations = $variationGraph->getWeightedGeneralizations(
                $weightedDimensionSpacePoint->dimensionSpacePoint
            );
            $i = 1;
            foreach ($generalizations as $generalization) {
                $edges[] = VisualInterDimensionalEdge::forVisualDimensionSpacePoints(
                    $nodes[$weightedDimensionSpacePoint->getIdentityHash()],
                    $nodes[$generalization->hash],
                    '#00B5FF',
                    round(($i / count($generalizations)), 2),
                    false
                );
                $i++;
            }
        }

        return new self(
            $nodes,
            $edges,
            $width,
            $height
        );
    }

    public static function forInterDimensionalVariationSubgraph(
        InterDimensionalVariationGraph $variationGraph,
        DimensionSpacePoint $startingPoint
    ): self {
        $nodes = [];
        $edges = [];
        $width = 0;
        $height = 0;

        $horizontalOffset = 0;
        $y = 0;
        $dimensionSpacePoints = [$startingPoint->hash => $startingPoint];
        foreach ($variationGraph->getWeightedGeneralizations($startingPoint) as $generalization) {
            $dimensionSpacePoints[$generalization->hash] = $generalization;
            $nodes[$generalization->hash] = VisualWeightedDimensionSpacePoint::fromDimensionSpacePoint(
                $variationGraph->getWeightedDimensionSpacePointByDimensionSpacePoint($generalization),
                $startingPoint,
                $horizontalOffset,
                $y,
                $width,
                $height
            );
        }
        $nodes[$startingPoint->hash] = VisualWeightedDimensionSpacePoint::fromDimensionSpacePoint(
            $variationGraph->getWeightedDimensionSpacePointByDimensionSpacePoint($startingPoint),
            $startingPoint,
            $horizontalOffset,
            $y,
            $width,
            $height
        );
        foreach ($variationGraph->getWeightedSpecializations($startingPoint) as $weight => $specializations) {
            foreach ($specializations as $specialization) {
                $dimensionSpacePoints[$specialization->hash] = $specialization;
                $nodes[$specialization->hash] = VisualWeightedDimensionSpacePoint::fromDimensionSpacePoint(
                    $variationGraph->getWeightedDimensionSpacePointByDimensionSpacePoint($specialization),
                    $startingPoint,
                    $horizontalOffset,
                    $y,
                    $width,
                    $height
                );
            }
        }

        foreach ($dimensionSpacePoints as $dimensionSpacePoint) {
            $generalizations = $variationGraph->getWeightedGeneralizations($dimensionSpacePoint);
            $i = 1;
            foreach ($generalizations as $generalization) {
                $isPrimary = ($generalization === $variationGraph->getPrimaryGeneralization($startingPoint));
                $edges[] = VisualInterDimensionalEdge::forVisualDimensionSpacePoints(
                    $nodes[$dimensionSpacePoint->hash],
                    $nodes[$generalization->hash],
                    $isPrimary ? '#00B5FF' : '#FFFFFF',
                    round(($i / count($generalizations)), 2),
                    !$isPrimary
                );
                $i++;
            }
        }

        return new self(
            $nodes,
            $edges,
            $width,
            $height
        );
    }

    private static function resolveOffsets(ContentDimension $contentDimension): array
    {
        $horizontalOffset = 0;
        $offsets = [];
        foreach ($contentDimension->getRootValues() as $rootValue) {
            self::populateOffsets(
                $contentDimension,
                $rootValue,
                0,
                $horizontalOffset,
                0,
                $offsets
            );
        }

        return $offsets;
    }

    private static function populateOffsets(
        ContentDimension $contentDimension,
        ContentDimensionValue $value,
        int $depth,
        int &$horizontalOffset,
        int $baseOffset,
        array &$offsets
    ) {
        $leftOffset = $horizontalOffset;
        $specializations = $contentDimension->getSpecializations($value);
        if (!empty($specializations)) {
            foreach ($contentDimension->getSpecializations($value) as $specialization) {
                self::populateOffsets(
                    $contentDimension,
                    $specialization,
                    $depth + 1,
                    $horizontalOffset,
                    $baseOffset,
                    $offsets
                );
            }
            $horizontalOffset--;
        }
        $rightOffset = $horizontalOffset;

        $x = $baseOffset + $leftOffset + ($rightOffset - $leftOffset) / 2;
        $y = $depth;

        $offsets['_width'] = max($offsets['_width'] ?? 0, $x + 1);
        $offsets['_height'] = max($offsets['_height'] ?? 0, $y + 1);
        $offsets[$value->value] = [
            'x' => $x,
            'y' => $y
        ];

        $horizontalOffset++;
    }
}
