<?php

namespace Neos\Neos\Presentation\Model\Svg;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\ContentRepository\Domain\Context\DimensionSpace;

/**
 * The InterDimensionalFallbackGraph presentation model for SVG
 */
class InterDimensionalFallbackGraph
{
    /**
     * @var DimensionSpace\InterDimensionalVariationGraph
     */
    protected $interDimensionalVariationGraph;

    /**
     * @var Dimension\ContentDimensionSourceInterface
     */
    protected $contentDimensionSource;

    /**
     * @var string
     */
    protected $rootSubgraphIdentifier;

    /**
     * @var array
     */
    protected $offsets = [];

    /**
     * @var array
     */
    protected $nodes;

    /**
     * @var array
     */
    protected $edges;

    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * @param DimensionSpace\InterDimensionalVariationGraph $interDimensionalVariationGraph
     * @param Dimension\ContentDimensionSourceInterface $contentDimensionSource
     * @param string|null $rootSubgraphIdentifier
     */
    public function __construct(
        DimensionSpace\InterDimensionalVariationGraph $interDimensionalVariationGraph,
        Dimension\ContentDimensionSourceInterface $contentDimensionSource,
        string $rootSubgraphIdentifier = null
    ) {
        $this->interDimensionalVariationGraph = $interDimensionalVariationGraph;
        $this->contentDimensionSource = $contentDimensionSource;
        $this->rootSubgraphIdentifier = $rootSubgraphIdentifier;
    }

    /**
     * @return array
     */
    public function getNodes(): array
    {
        if (is_null($this->nodes)) {
            $this->initialize();
        }

        return $this->nodes;
    }

    /**
     * @return array
     */
    public function getEdges(): array
    {
        if (is_null($this->edges)) {
            $this->initialize();
        }

        return $this->edges;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        if (is_null($this->width)) {
            $this->initialize();
        }

        return $this->width ?: 0;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        if (is_null($this->height)) {
            $this->initialize();
        }

        return $this->height ?: 0;
    }

    /**
     * @return void
     */
    protected function initialize()
    {
        $this->nodes = [];
        $this->edges = [];

        if ($this->rootSubgraphIdentifier) {
            $this->initializeDimensionSpacePoints();
        } else {
            $this->initializeFullGraph();
        }
    }

    /**
     * @return void
     */
    protected function initializeFullGraph()
    {
        $this->initializeOffsets();
        foreach ($this->interDimensionalVariationGraph->getWeightedDimensionSpacePoints() as $subgraphIdentifier => $subgraph) {
            $this->initializeFullGraphNode($subgraph);
        }

        $this->initializeEdges();
    }

    /**
     * @return void
     */
    protected function initializeOffsets()
    {
        foreach ($this->contentDimensionSource->getContentDimensionsOrderedByPriority() as $contentDimension) {
            $horizontalOffset = 0;
            $this->offsets[(string) $contentDimension->getIdentifier()] = [
                '_height' => 0,
                '_width' => 0
            ];
            foreach ($contentDimension->getRootValues() as $rootValue) {
                $this->traverseDimension($contentDimension, $rootValue, 0, $horizontalOffset, 0);
            }
        }
    }

    /**
     * @param Dimension\ContentDimension $contentDimension
     * @param Dimension\ContentDimensionValue $value
     * @param int $depth
     * @param int $horizontalOffset
     * @param int $baseOffset
     * @return void
     */
    protected function traverseDimension(Dimension\ContentDimension $contentDimension, Dimension\ContentDimensionValue $value, int $depth, int & $horizontalOffset, int $baseOffset)
    {
        $leftOffset = $horizontalOffset;
        if (!empty($contentDimension->getSpecializations($value))) {
            foreach ($contentDimension->getSpecializations($value) as $specialization) {
                $this->traverseDimension($contentDimension, $specialization, $depth + 1, $horizontalOffset, $baseOffset);
            }
            $horizontalOffset--;
        }
        $rightOffset = $horizontalOffset;

        $x = $baseOffset + $leftOffset + ($rightOffset - $leftOffset) / 2;
        $y = $depth;

        $this->offsets[(string) $contentDimension->getIdentifier()]['_width'] = max($this->offsets[(string) $contentDimension->getIdentifier()]['_width'], $x + 1);
        $this->offsets[(string) $contentDimension->getIdentifier()]['_height'] = max($this->offsets[(string) $contentDimension->getIdentifier()]['_height'], $y + 1);
        $this->offsets[(string) $contentDimension->getIdentifier()][$value->getValue()] = [
            'x' => $x,
            'y' => $y
        ];

        $horizontalOffset++;
    }

    /**
     * @return void
     */
    protected function initializeDimensionSpacePoints()
    {
        $dimensionSpacePoint = $this->interDimensionalVariationGraph->getWeightedDimensionSpacePointByHash($this->rootSubgraphIdentifier);
        $horizontalOffset = 0;
        $y = 0;

        $generalizations = $this->interDimensionalVariationGraph->getWeightedGeneralizations($dimensionSpacePoint->getDimensionSpacePoint());
        ksort($generalizations);
        foreach ($generalizations as $generalization) {
            $this->initializeSubgraphNode($this->interDimensionalVariationGraph->getWeightedDimensionSpacePointByHash($generalization->getHash()), $horizontalOffset, $y);
        }

        $specializations = $this->interDimensionalVariationGraph->getWeightedSpecializations($dimensionSpacePoint->getDimensionSpacePoint());
        ksort($specializations);
        foreach ($specializations as $weight => $specializationsOfSameWeight) {
            foreach ($specializationsOfSameWeight as $specialization) {
                $this->initializeSubgraphNode($this->interDimensionalVariationGraph->getWeightedDimensionSpacePointByHash($specialization->getHash()), $horizontalOffset, $y);
            }
        }

        $this->initializeEdges(false);
    }

    /**
     * @param DimensionSpace\WeightedDimensionSpacePoint $weightedDimensionSpacePoint
     * @return void
     */
    protected function initializeFullGraphNode(DimensionSpace\WeightedDimensionSpacePoint $weightedDimensionSpacePoint)
    {
        $x = 0;
        $y = 0;

        $previousDepthFactor = 1;
        $previousWidthFactor = 1;
        foreach (array_reverse($weightedDimensionSpacePoint->getDimensionValues()) as $rawDimensionIdentifier => $dimensionValue) {
            /** @var Dimension\ContentDimensionValue $dimensionValue */
            $y += $dimensionValue->getSpecializationDepth()->getDepth() * $previousDepthFactor;
            $previousDepthFactor *= $this->offsets[$rawDimensionIdentifier]['_height'];

            $x += $this->offsets[$rawDimensionIdentifier][(string) $dimensionValue]['x'] * $previousWidthFactor;
            $previousWidthFactor *= $this->offsets[$rawDimensionIdentifier]['_width'];
        }

        $nameComponents = $weightedDimensionSpacePoint->getDimensionValues();
        array_walk($nameComponents, function (Dimension\ContentDimensionValue &$value) {
            $value = $value->getValue();
        });

        $x *= 110;
        $y *= 110;

        $this->nodes[$weightedDimensionSpacePoint->getIdentityHash()] = [
            'id' => $weightedDimensionSpacePoint->getIdentityHash(),
            'name' => implode(', ', $nameComponents),
            'textX' => $x,
            'textY' => $y + 42 + 50, // 50 for padding
            'color' => $weightedDimensionSpacePoint->getIdentityHash() === $this->rootSubgraphIdentifier ? '#00B5FF' : '#3F3F3F',
            'x' => $x + 42,
            'y' => $y + 42
        ];

        $this->width = max($this->width, $x + 110);
        $this->height = max($this->height, $y + 110);
    }

    /**
     * @param DimensionSpace\WeightedDimensionSpacePoint $weightedDimensionSpacePoint
     * @param int $horizontalOffset
     * @param int $y
     * @return void
     */
    protected function initializeSubgraphNode(DimensionSpace\WeightedDimensionSpacePoint $weightedDimensionSpacePoint, int & $horizontalOffset, int & $y)
    {
        $nameComponents = $weightedDimensionSpacePoint->getDimensionValues();
        array_walk($nameComponents, function (Dimension\ContentDimensionValue &$value) {
            $value = $value->getValue();
        });
        $depth = 0;
        foreach ($weightedDimensionSpacePoint->getDimensionValues() as $dimensionValue) {
            $depth += $dimensionValue->getSpecializationDepth()->getDepth();
        }
        $previousY = $y;
        $y = $depth * 110 + 42;
        if ($y <= $previousY) {
            $horizontalOffset += 110;
        }
        $x = $horizontalOffset + 42;
        $this->nodes[$weightedDimensionSpacePoint->getIdentityHash()] = [
            'id' => $weightedDimensionSpacePoint->getIdentityHash(),
            'name' => implode(', ', $nameComponents),
            'textX' => $x - 40,
            'textY' => $y - 5 + 50, // 50 for padding
            'color' => $weightedDimensionSpacePoint->getIdentityHash() === $this->rootSubgraphIdentifier ? '#00B5FF' : '#3F3F3F',
            'x' => $x,
            'y' => $y
        ];

        $this->width = max($this->width, $x + 42 + 10);
        $this->height = max($this->height, $y + 42 + 10);
    }

    /**
     * @param bool $hideInactive
     * @return void
     */
    protected function initializeEdges($hideInactive = true)
    {
        $weightedDimensionSpacePoints = $this->interDimensionalVariationGraph->getWeightedDimensionSpacePoints();
        usort($weightedDimensionSpacePoints, function (DimensionSpace\WeightedDimensionSpacePoint $subgraphA, DimensionSpace\WeightedDimensionSpacePoint $subgraphB) {
            return $subgraphB->getWeight() <=> $subgraphA->getWeight();
        });
        foreach ($weightedDimensionSpacePoints as $weightedDimensionSpacePoint) {
            $generalizations = $this->interDimensionalVariationGraph->getWeightedGeneralizations($weightedDimensionSpacePoint->getDimensionSpacePoint());
            ksort($generalizations);
            $i = 1;
            foreach ($generalizations as $generalization) {
                if (
                    isset($this->nodes[$generalization->getHash()])
                    && isset($this->nodes[$weightedDimensionSpacePoint->getIdentityHash()])
                ) {
                    $isPrimary = ($generalization === $this->interDimensionalVariationGraph->getPrimaryGeneralization($weightedDimensionSpacePoint->getDimensionSpacePoint()));
                    $edge = [
                        'x1' => $this->nodes[$weightedDimensionSpacePoint->getIdentityHash()]['x'],
                        'y1' => $this->nodes[$weightedDimensionSpacePoint->getIdentityHash()]['y'] - 40,
                        'x2' => $this->nodes[$generalization->getHash()]['x'],
                        'y2' => $this->nodes[$generalization->getHash()]['y'] + 40,
                        'color' => $isPrimary ? '#00B5FF' : '#FFFFFF',
                        'opacity' => round(($i / count($generalizations)), 2)
                    ];
                    if ($hideInactive && !$isPrimary) {
                        $edge['from'] = $weightedDimensionSpacePoint->getIdentityHash();
                        $edge['style'] = 'display: none';
                        $edge['to'] = $generalization->getHash();
                    }
                    $this->edges[] = $edge;
                }
                $i++;
            }
        }
    }
}
