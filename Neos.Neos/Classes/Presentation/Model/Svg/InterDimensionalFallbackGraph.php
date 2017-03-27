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

use Neos\ContentRepository\Domain\Model\InterDimension;
use Neos\ContentRepository\Domain\Model\IntraDimension;
use Neos\Flow\Annotations as Flow;

/**
 * The InterDimensionalFallbackGraph presentation model for SVG
 */
class InterDimensionalFallbackGraph
{
    /**
     * @var InterDimension\InterDimensionalFallbackGraph
     */
    protected $fallbackGraph;

    /**
     * @var IntraDimension\IntraDimensionalFallbackGraph
     */
    protected $intraDimensionalFallbackGraph;

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


    public function __construct(
        InterDimension\InterDimensionalFallbackGraph $fallbackGraph,
        IntraDimension\IntraDimensionalFallbackGraph $intraDimensionalFallbackGraph,
        string $rootSubgraphIdentifier = null
    ) {
        $this->fallbackGraph = $fallbackGraph;
        $this->intraDimensionalFallbackGraph = $intraDimensionalFallbackGraph;
        $this->rootSubgraphIdentifier = $rootSubgraphIdentifier;
    }


    public function getNodes(): array
    {
        if (is_null($this->nodes)) {
            $this->initialize();
        }

        return $this->nodes;
    }

    public function getEdges(): array
    {
        if (is_null($this->edges)) {
            $this->initialize();
        }

        return $this->edges;
    }

    public function getWidth(): int
    {
        if (is_null($this->width)) {
            $this->initialize();
        }

        return $this->width ?: 0;
    }

    public function getHeight(): int
    {
        if (is_null($this->height)) {
            $this->initialize();
        }

        return $this->height ?: 0;
    }

    protected function initialize()
    {
        $this->nodes = [];
        $this->edges = [];

        if ($this->rootSubgraphIdentifier) {
            $this->initializeSubgraph();
        } else {
            $this->initializeFullGraph();
        }
    }

    protected function initializeFullGraph()
    {
        $this->initializeOffsets();
        foreach ($this->fallbackGraph->getSubgraphs() as $subgraphIdentifier => $subgraph) {
            $this->initializeFullGraphNode($subgraph);
        }

        $this->initializeEdges();
    }

    protected function initializeOffsets()
    {
        foreach ($this->intraDimensionalFallbackGraph->getDimensions() as $contentDimension) {
            $horizontalOffset = 0;
            $this->offsets[$contentDimension->getName()] = [
                '_height' => 0,
                '_width' => 0
            ];
            foreach ($contentDimension->getRootValues() as $rootValue) {
                $this->traverseDimension($contentDimension->getName(), $rootValue, 0, $horizontalOffset, 0);
            }
        }
    }

    protected function traverseDimension(string $dimensionName, IntraDimension\ContentDimensionValue $value, int $depth, int & $horizontalOffset, int $baseOffset)
    {
        $leftOffset = $horizontalOffset;
        if ($value->getVariants()) {
            foreach ($value->getVariants() as $variant) {
                $this->traverseDimension($dimensionName, $variant, $depth + 1, $horizontalOffset, $baseOffset);
            }
            $horizontalOffset--;
        }
        $rightOffset = $horizontalOffset;

        $x = $baseOffset + $leftOffset + ($rightOffset - $leftOffset) / 2;
        $y = $depth;

        $this->offsets[$dimensionName]['_width'] = max($this->offsets[$dimensionName]['_width'], $x + 1);
        $this->offsets[$dimensionName]['_height'] = max($this->offsets[$dimensionName]['_height'], $y + 1);
        $this->offsets[$dimensionName][$value->getValue()] = [
            'x' => $x,
            'y' => $y
        ];

        $horizontalOffset++;
    }


    protected function initializeSubgraph()
    {
        $subgraph = $this->fallbackGraph->getSubgraph($this->rootSubgraphIdentifier);
        $horizontalOffset = 0;
        $y = 0;
        foreach ($subgraph->getFallback() as $fallbackSubgraph) {
            $this->initializeSubgraphNode($fallbackSubgraph, $horizontalOffset, $y);
        }
        $this->initializeSubgraphNode($subgraph, $horizontalOffset, $y);
        foreach ($subgraph->getVariants() as $variantSubgraph) {
            $this->initializeSubgraphNode($variantSubgraph, $horizontalOffset, $y);
        }

        $this->initializeEdges(false);
    }



    protected function initializeFullGraphNode(InterDimension\ContentSubgraph $subgraph)
    {
        $x = 0;
        $y = 0;

        $previousDepthFactor = 1;
        $previousWidthFactor = 1;
        foreach (array_reverse($subgraph->getDimensionValues()) as $dimensionName => $dimensionValue) {
            /** @var IntraDimension\ContentDimensionValue $dimensionValue  */
            $y += $dimensionValue->getDepth() * $previousDepthFactor;
            $previousDepthFactor *= $this->offsets[$dimensionName]['_height'];

            $x += $this->offsets[$dimensionName][$dimensionValue->getValue()]['x'] * $previousWidthFactor;
            $previousWidthFactor *= $this->offsets[$dimensionName]['_width'];
        }

        $nameComponents = $subgraph->getDimensionValues();
        array_walk($nameComponents, function (IntraDimension\ContentDimensionValue &$value) {
            $value = $value->getValue();
        });

        $x *= 110;
        $y *= 110;

        $this->nodes[$subgraph->getIdentityHash()] = [
            'id' => $subgraph->getIdentityHash(),
            'name' => implode(', ', $nameComponents),
            'textX' => $x,
            'textY' => $y + 42 + 50, // 50 for padding
            'color' => $subgraph->getIdentityHash() === $this->rootSubgraphIdentifier ? '#00B5FF' : '#3F3F3F',
            'x' => $x + 42,
            'y' => $y + 42
        ];

        $this->width = max($this->width, $x + 110);
        $this->height = max($this->height, $y + 110);
    }

    protected function initializeSubgraphNode(InterDimension\ContentSubgraph $subgraph, int & $horizontalOffset, int & $y)
    {
        $nameComponents = $subgraph->getDimensionValues();
        array_walk($nameComponents, function (IntraDimension\ContentDimensionValue &$value) {
            $value = $value->getValue();
        });
        $depth = 0;
        foreach ($subgraph->getDimensionValues() as $dimensionValue) {
            $depth += $dimensionValue->getDepth();
        }
        $previousY = $y;
        $y = $depth * 110 + 42;
        if ($y <= $previousY) {
            $horizontalOffset += 110;
        }
        $x = $horizontalOffset + 42;
        $this->nodes[$subgraph->getIdentityHash()] = [
            'id' => $subgraph->getIdentityHash(),
            'name' => implode(', ', $nameComponents),
            'textX' => $x - 40,
            'textY' => $y - 5 + 50, // 50 for padding
            'color' => $subgraph->getIdentityHash() === $this->rootSubgraphIdentifier ? '#00B5FF' : '#3F3F3F',
            'x' => $x,
            'y' => $y
        ];

        $this->width = max($this->width, $x + 42 + 10);
        $this->height = max($this->height, $y + 42 + 10);
    }

    protected function initializeEdges($hideInactive = true)
    {
        $subgraphs = $this->fallbackGraph->getSubgraphs();
        usort($subgraphs, function(InterDimension\ContentSubgraph $subgraphA, InterDimension\ContentSubgraph $subgraphB) {
            return $subgraphB->getWeight() <=> $subgraphA->getWeight();
        });
        foreach ($subgraphs as $subgraph) {
            $fallback = $subgraph->getFallback();
            usort($fallback, function(InterDimension\ContentSubgraph $subgraphA, InterDimension\ContentSubgraph $subgraphB) {
                return $subgraphA->getWeight() <=> $subgraphB->getWeight();
            });
            $i = 1;
            foreach ($fallback as $fallbackSubgraph) {
                if (
                    isset($this->nodes[$fallbackSubgraph->getIdentityHash()])
                    && isset($this->nodes[$subgraph->getIdentityHash()])
                ) {
                    $isPrimary = ($fallbackSubgraph === $this->fallbackGraph->getPrimaryFallback($subgraph));
                    $edge = [
                        'x1' => $this->nodes[$subgraph->getIdentityHash()]['x'],
                        'y1' => $this->nodes[$subgraph->getIdentityHash()]['y'] - 40,
                        'x2' => $this->nodes[$fallbackSubgraph->getIdentityHash()]['x'],
                        'y2' => $this->nodes[$fallbackSubgraph->getIdentityHash()]['y'] + 40,
                        'color' => $isPrimary ? '#00B5FF' : '#FFFFFF',
                        'opacity' => round(($i / count($fallback)), 2)
                    ];
                    if ($hideInactive && !$isPrimary) {
                        $edge['from'] = $subgraph->getIdentityHash();
                        $edge['style'] = 'display: none';
                        $edge['to'] = $fallbackSubgraph->getIdentityHash();
                    }
                    $this->edges[] = $edge;
                }
                $i++;
            }
        }
    }
}
