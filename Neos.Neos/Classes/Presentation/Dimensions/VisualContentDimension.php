<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Presentation\Dimensions;

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimension;
use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionValue;
use Neos\Flow\Annotations as Flow;

/**
 * The visualization model for the interdimensional variation graph
 */
#[Flow\Proxy(false)]
final class VisualContentDimension
{
    /**
     * @var array<int,VisualIntraDimensionalNode>
     */
    public readonly array $nodes;

    /**
     * @var array<int,VisualIntraDimensionalEdge>
     */
    public readonly array $edges;

    /**
     * @param array<int,VisualIntraDimensionalNode> $nodes
     * @param array<int,VisualIntraDimensionalEdge> $edges
     */
    public function __construct(
        public readonly int $offset,
        public readonly string $name,
        public readonly string $label,
        array $nodes,
        array $edges
    ) {
        $this->nodes = $nodes;
        $this->edges = $edges;
    }

    public static function fromContentDimension(
        ContentDimension $contentDimension,
        int &$totalOffset,
        int &$counter,
        int &$width,
        int &$height
    ): self {
        $offset = $totalOffset;
        $nodes = [];
        $edges = [];

        foreach ($contentDimension->getRootValues() as $rootValue) {
            self::traverseDimension(
                $contentDimension,
                $rootValue,
                $counter,
                0,
                $totalOffset,
                $width,
                $height,
                0,
                $nodes,
                $edges,
            );
            $totalOffset += 30;
        }

        return new self(
            $offset,
            (string)$contentDimension->identifier,
            $contentDimension->getConfigurationValue('label') ?: (string)$contentDimension->identifier,
            $nodes,
            $edges
        );
    }

    /**
     * @param array<int,VisualIntraDimensionalNode> $nodes
     * @param array<int,VisualIntraDimensionalEdge> $edges
     */
    private static function traverseDimension(
        ContentDimension $contentDimension,
        ContentDimensionValue $value,
        int &$counter,
        int $depth,
        int &$horizontalOffset,
        int &$width,
        int &$height,
        int $parent,
        array &$nodes,
        array &$edges
    ): void {
        $counter++;
        $nodeId = $counter;
        $leftOffset = $horizontalOffset + 42;
        $specializations = $contentDimension->specializations[$value->value] ?? null;
        if ($specializations) {
            foreach ($specializations as $specialization) {
                self::traverseDimension(
                    $contentDimension,
                    $specialization,
                    $counter,
                    $depth + 1,
                    $horizontalOffset,
                    $width,
                    $height,
                    $nodeId,
                    $nodes,
                    $edges
                );
            }
            $horizontalOffset -= 110;
        }

        $rightOffset = $horizontalOffset + 42;

        $x = ($leftOffset + $rightOffset) / 2;
        $y = $depth * 110 + 42;
        $width = max($width, $x + 42 + 10);
        $height = max($height, $y + 42 + 10);

        $currentNode = new VisualIntraDimensionalNode(
            $nodeId,
            $value->value,
            $parent,
            $x,
            $y
        );

        $nodes[] = $currentNode;

        foreach ($nodes as $node) {
            if ($node->parent === $nodeId) {
                $edges[] = VisualIntraDimensionalEdge::forNodes(
                    $node,
                    $currentNode
                );
            }
        }

        $horizontalOffset += 110;
    }
}
