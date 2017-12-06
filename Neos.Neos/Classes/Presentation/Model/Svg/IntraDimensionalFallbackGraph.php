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

use Neos\ContentRepository\Domain\Model\IntraDimension;

/**
 * The IntraDimensionalFallbackGraph presentation model for SVG
 */
class IntraDimensionalFallbackGraph
{
    /**
     * @var IntraDimension\IntraDimensionalFallbackGraph
     */
    protected $fallbackGraph;

    /**
     * @var array
     */
    protected $dimensions;

    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * @param IntraDimension\IntraDimensionalFallbackGraph $fallbackGraph
     */
    public function __construct(IntraDimension\IntraDimensionalFallbackGraph $fallbackGraph)
    {
        $this->fallbackGraph = $fallbackGraph;
    }

    /**
     * @return array
     */
    public function getDimensions(): array
    {
        if (is_null($this->dimensions)) {
            $this->initialize();
        }

        return $this->dimensions;
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
        $this->dimensions = [];
        $counter = 0;
        $horizontalOffset = 0;
        $parent = 0;
        foreach ($this->fallbackGraph->getDimensions() as $contentDimension) {
            $this->dimensions[$contentDimension->getName()] = [
                'offset' => $horizontalOffset,
                'name' => $contentDimension->getName(),
                'label' => $contentDimension->getLabel(),
                'nodes' => [],
                'edges' => []
            ];
            foreach ($contentDimension->getRootValues() as $rootValue) {
                $this->traverseDimension($contentDimension->getName(), $rootValue, $counter, 0, $horizontalOffset, $parent);
                $horizontalOffset += 30;
            }
            $horizontalOffset += 30;
        }
    }

    /**
     * @param string $dimensionName
     * @param IntraDimension\ContentDimensionValue $value
     * @param int $counter
     * @param int $depth
     * @param int $horizontalOffset
     * @param int $parent
     * @return void
     */
    protected function traverseDimension(string $dimensionName, IntraDimension\ContentDimensionValue $value, int & $counter, int $depth, int & $horizontalOffset, int $parent)
    {
        $counter++;
        $nodeId = $counter;
        $leftOffset = $horizontalOffset + 42;
        if ($value->getVariants()) {
            foreach ($value->getVariants() as $variant) {
                $this->traverseDimension($dimensionName, $variant, $counter, $depth + 1, $horizontalOffset, $nodeId);
            }
            $horizontalOffset -= 110;
        }
        $rightOffset = $horizontalOffset + 42;

        $x = ($leftOffset + $rightOffset) / 2;
        $y = $depth * 110 + 42;
        $this->width = max($this->width, $x + 42 + 10);
        $this->height = max($this->height, $y + 42 + 10);

        $currentNode = [
            'id' => $nodeId,
            'name' => $value->getValue(),
            'textX' => $x - 40,
            'textY' => $y - 5 + 50, // 50 for padding
            'parent' => $parent,
            'color' => '#3F3F3F',
            'x' => $x,
            'y' => $y
        ];

        $this->dimensions[$dimensionName]['nodes'][] = $currentNode;

        foreach ($this->dimensions[$dimensionName]['nodes'] as $node) {
            if ($node['parent'] === $nodeId) {
                $this->dimensions[$dimensionName]['edges'][] = [
                    'x1' => $node['x'],
                    'y1' => $node['y'] - 40,
                    'x2' => $currentNode['x'],
                    'y2' => $currentNode['y'] + 40
                ];
            }
        }

        $horizontalOffset += 110;
    }
}
