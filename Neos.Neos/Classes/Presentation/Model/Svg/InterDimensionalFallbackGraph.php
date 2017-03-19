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
     * @var string
     */
    protected $rootSubgraphIdentifier;

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


    public function __construct(InterDimension\InterDimensionalFallbackGraph $fallbackGraph, string $rootSubgraphIdentifier = null)
    {
        $this->fallbackGraph = $fallbackGraph;
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
        $horizontalOffset = 0;
        $y = 0;
        foreach ($this->fallbackGraph->getSubgraphs() as $subgraphIdentifier => $subgraph) {
            $this->initializeSubgraphNode($subgraph, $horizontalOffset, $y);
        }

        $this->initializeEdges();
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

        $this->initializeEdges();
    }

    protected function initializeSubgraphNode(InterDimension\ContentSubgraph $subgraph, int & $horizontalOffset, int & $y)
    {
        $nameComponents = $subgraph->getDimensionValues();
        array_walk($nameComponents, function (&$value) {
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

    protected function initializeEdges()
    {
        $subgraphs = $this->fallbackGraph->getSubgraphs();
        usort($subgraphs, function(InterDimension\ContentSubgraph $subgraphA, InterDimension\ContentSubgraph $subgraphB) {
            return $subgraphB->getWeight() <=> $subgraphA->getWeight();
        });
        foreach ($subgraphs as $subgraph) {
            foreach ($subgraph->getFallback() as $fallbackSubgraph) {
                if (
                    isset($this->nodes[$fallbackSubgraph->getIdentityHash()])
                    && isset($this->nodes[$subgraph->getIdentityHash()])
                ) {
                    $this->edges[] = [
                        'x1' => $this->nodes[$subgraph->getIdentityHash()]['x'],
                        'y1' => $this->nodes[$subgraph->getIdentityHash()]['y'] - 40,
                        'x2' => $this->nodes[$fallbackSubgraph->getIdentityHash()]['x'],
                        'y2' => $this->nodes[$fallbackSubgraph->getIdentityHash()]['y'] + 40,
                        'color' => $fallbackSubgraph === $this->fallbackGraph->getPrimaryFallback($subgraph) ? '#00B5FF' : '#FFFFFF'
                    ];
                }
            }
        }
    }
}
