<?php

/*
 * This file is part of the Neos.ContentRepository.TestSuite package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers;

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\Dimension\ContentDimension;
use Neos\ContentRepository\Core\Dimension\ContentDimensionConstraintSet;
use Neos\ContentRepository\Core\Dimension\ContentDimensionId;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\Dimension\ContentDimensionValue;
use Neos\ContentRepository\Core\Dimension\ContentDimensionValues;
use Neos\ContentRepository\Core\Dimension\ContentDimensionValueSpecializationDepth;
use Neos\ContentRepository\Core\Dimension\ContentDimensionValueVariationEdge;
use Neos\ContentRepository\Core\Dimension\ContentDimensionValueVariationEdges;
use Neos\Utility\Arrays;

/**
 * The node creation trait for behavioral tests
 */
final class GherkinTableNodeBasedContentDimensionSource implements ContentDimensionSourceInterface
{
    private function __construct(
        /** @var array<string,ContentDimension> */
        private readonly array $contentDimensions
    ) {
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    public static function fromGherkinTableNode(TableNode $tableNode): self
    {
        $dimensions = [];
        foreach ($tableNode->getHash() as $row) {
            $rawGeneralizations = [];
            $specializationDepths = [];
            $dimensionValues = [];
            $variationEdges = [];
            foreach (Arrays::trimExplode(',', $row['Generalizations']) as $variationExpression) {
                $currentGeneralization = null;
                foreach (array_reverse(Arrays::trimExplode('->', $variationExpression)) as $specializationDepth => $rawDimensionValue) {
                    $specializationDepths[$rawDimensionValue] = $specializationDepth;
                    if ($currentGeneralization) {
                        $rawGeneralizations[$rawDimensionValue] = $currentGeneralization;
                    }
                    $currentGeneralization = $rawDimensionValue;
                }
            }

            foreach (Arrays::trimExplode(',', $row['Values']) as $rawDimensionValue) {
                $dimensionValueConfiguration = [];
                $dimensionValues[$rawDimensionValue] = new ContentDimensionValue(
                    $rawDimensionValue,
                    new ContentDimensionValueSpecializationDepth($specializationDepths[$rawDimensionValue] ?? 0),
                    ContentDimensionConstraintSet::createEmpty(),
                    $dimensionValueConfiguration
                );
            }

            foreach ($rawGeneralizations as $rawSpecializationValue => $rawGeneralizationValue) {
                $variationEdges[] = new ContentDimensionValueVariationEdge($dimensionValues[$rawSpecializationValue], $dimensionValues[$rawGeneralizationValue]);
            }

            $dimensionConfiguration = [];
            $dimensions[$row['Identifier']] = new ContentDimension(
                new ContentDimensionId($row['Identifier']),
                new ContentDimensionValues($dimensionValues),
                new ContentDimensionValueVariationEdges($variationEdges),
                $dimensionConfiguration
            );
        }

        return new self($dimensions);
    }

    public function getDimension(ContentDimensionId $dimensionId): ?ContentDimension
    {
        return $this->contentDimensions[$dimensionId->value] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getContentDimensionsOrderedByPriority(): array
    {
        return $this->contentDimensions;
    }
}
