<?php

namespace Neos\ContentRepository\Tests\Unit\DimensionSpace\Fixtures;

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Dimension;

/**
 * The example dimension source fixture
 */
class ExampleDimensionSource implements Dimension\ContentDimensionSourceInterface
{
    /**
     * @var array<string,Dimension\ContentDimension>
     */
    protected ?array $dimensions = null;

    protected function initializeDimensions(): void
    {
        $letzebuergesch = new Dimension\ContentDimensionValue(
            'lb',
            new Dimension\ContentDimensionValueSpecializationDepth(0),
            new Dimension\ContentDimensionConstraintSet([
                'market' => new Dimension\ContentDimensionConstraints()
            ])
        );
        $languageValues = new Dimension\ContentDimensionValues([
            'de' => new Dimension\ContentDimensionValue(
                'de',
                new Dimension\ContentDimensionValueSpecializationDepth(0),
                new Dimension\ContentDimensionConstraintSet([
                    'market' => new Dimension\ContentDimensionConstraints()
                ])
            ),
            'fr' => new Dimension\ContentDimensionValue(
                'fr',
                new Dimension\ContentDimensionValueSpecializationDepth(0),
                new Dimension\ContentDimensionConstraintSet([
                    'market' => new Dimension\ContentDimensionConstraints()
                ])
            ),
            'it' => new Dimension\ContentDimensionValue(
                'it',
                new Dimension\ContentDimensionValueSpecializationDepth(0),
                new Dimension\ContentDimensionConstraintSet([
                    'market' => new Dimension\ContentDimensionConstraints()
                ])
            ),
            'lb' => $letzebuergesch,
            'en' => new Dimension\ContentDimensionValue(
                'en',
                new Dimension\ContentDimensionValueSpecializationDepth(0),
                new Dimension\ContentDimensionConstraintSet([
                    'market' => new Dimension\ContentDimensionConstraints(true, [
                        'LU' => false,
                        'CH' => false
                    ])
                ])
            )
        ]);

        $luxembourg = new Dimension\ContentDimensionValue(
            'LU',
            new Dimension\ContentDimensionValueSpecializationDepth(0),
            new Dimension\ContentDimensionConstraintSet([
                'language' => new Dimension\ContentDimensionConstraints(false, [
                    'de' => true,
                    'fr' => true,
                    'lb' => true
                ])
            ])
        );
        $marketValues = new Dimension\ContentDimensionValues([
            'CH' => new Dimension\ContentDimensionValue(
                'CH',
                new Dimension\ContentDimensionValueSpecializationDepth(0),
                new Dimension\ContentDimensionConstraintSet([
                    'language' => new Dimension\ContentDimensionConstraints(false, [
                        'de' => true,
                        'fr' => true,
                        'it' => true
                    ])
                ])
            ),
            'LU' => $luxembourg
        ]);

        $this->dimensions = [
            'market' => new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('market'),
                $marketValues,
                Dimension\ContentDimensionValueVariationEdges::createEmpty()
            ),
            'language' => new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('language'),
                $languageValues,
                Dimension\ContentDimensionValueVariationEdges::createEmpty()
            )
        ];
    }

    public function getDimension(Dimension\ContentDimensionIdentifier $dimensionIdentifier): ?Dimension\ContentDimension
    {
        if (is_null($this->dimensions)) {
            $this->initializeDimensions();
        }

        return $this->dimensions[(string)$dimensionIdentifier] ?? null;
    }

    /**
     * @return array<string,Dimension\ContentDimension>
     */
    public function getContentDimensionsOrderedByPriority(): array
    {
        if (is_null($this->dimensions)) {
            $this->initializeDimensions();
        }

        return $this->dimensions;
    }
}
