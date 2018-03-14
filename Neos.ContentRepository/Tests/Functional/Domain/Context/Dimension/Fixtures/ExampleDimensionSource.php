<?php
namespace Neos\ContentRepository\Tests\Functional\Domain\Context\Dimension\Fixtures;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\Flow\Annotations as Flow;

/**
 * The example dimension source fixture
 *
 * @Flow\Scope("singleton")
 */
class ExampleDimensionSource implements Dimension\ContentDimensionSourceInterface
{
    /**
     * @var array|Dimension\ContentDimension[]
     */
    protected $dimensions;


    protected function initializeDimensions()
    {
        $letzebuergesch = new Dimension\ContentDimensionValue('lb', new Dimension\ContentDimensionValueSpecializationDepth(0), [
            'market' => new Dimension\ContentDimensionConstraints()
        ]);
        $languageValues = [
            'de' => new Dimension\ContentDimensionValue('de', new Dimension\ContentDimensionValueSpecializationDepth(0), [
                'market' => new Dimension\ContentDimensionConstraints()
            ]),
            'fr' => new Dimension\ContentDimensionValue('fr', new Dimension\ContentDimensionValueSpecializationDepth(0), [
                'market' => new Dimension\ContentDimensionConstraints()
            ]),
            'it' => new Dimension\ContentDimensionValue('it', new Dimension\ContentDimensionValueSpecializationDepth(0), [
                'market' => new Dimension\ContentDimensionConstraints()
            ]),
            'lb' => $letzebuergesch,
            'en' => new Dimension\ContentDimensionValue('en', new Dimension\ContentDimensionValueSpecializationDepth(0), [
                'market' => new Dimension\ContentDimensionConstraints(true, [
                    'LU' => false,
                    'CH' => false
                ])
            ])
        ];

        $luxembourg = new Dimension\ContentDimensionValue('LU', new Dimension\ContentDimensionValueSpecializationDepth(0), [
            'language' => new Dimension\ContentDimensionConstraints(false, [
                'de' => true,
                'fr' => true,
                'lb' => true
            ])
        ]);
        $marketValues = [
            'CH' => new Dimension\ContentDimensionValue('CH', new Dimension\ContentDimensionValueSpecializationDepth(0), [
                'language' => new Dimension\ContentDimensionConstraints(false, [
                    'de' => true,
                    'fr' => true,
                    'it' => true
                ])
            ]),
            'LU' => $luxembourg
        ];

        $this->dimensions = [
            'market' => new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('market'),
                $marketValues,
                $luxembourg
            ),
            'language' => new Dimension\ContentDimension(
                new Dimension\ContentDimensionIdentifier('language'),
                $languageValues,
                $letzebuergesch
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
