<?php

namespace Neos\ContentRepository\Tests\Functional\Domain\Context\DimensionSpace;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain;
use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\ContentRepository\Domain\Context\DimensionSpace;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Functional test cases for the inter dimensional variation graph
 */
class InterDimensionalVariationGraphTest extends FunctionalTestCase
{
    /**
     * @var DimensionSpace\InterDimensionalVariationGraph|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * @test
     */
    public function initializeWeightedDimensionSpacePointsCorrectlyInitializesAllAvailableWeightedDimensionSpacePointsWithDimensionsWithVariationsGiven()
    {
        $this->setUpVariationExample();
        $this->subject->_call('initializeWeightedDimensionSpacePoints');

        $expectedWeightedDimensionSpacePointsCoordinates = [
            ['value1', 'value1', 0, 0],
            ['value1', 'value1.1', 0, 1],
            ['value1', 'value1.2', 0, 1],
            ['value1', 'value1.1.1', 0, 2],
            ['value1.1', 'value1', 1, 0],
            ['value1.1', 'value1.1', 1, 1],
            ['value1.1', 'value1.2', 1, 1],
            ['value1.1', 'value1.1.1', 1, 2],
            ['value1.2', 'value1', 1, 0],
            ['value1.2', 'value1.1', 1, 1],
            ['value1.2', 'value1.2', 1, 1],
            ['value1.2', 'value1.1.1', 1, 2],
            ['value1.1.1', 'value1', 2, 0],
            ['value1.1.1', 'value1.1', 2, 1],
            ['value1.1.1', 'value1.2', 2, 1],
            ['value1.1.1', 'value1.1.1', 2, 2]
        ];

        $expectedWeightedDimensionSpacePoints = [];
        foreach ($expectedWeightedDimensionSpacePointsCoordinates as $coordinates) {
            $weightedDimensionSpacePoints = new DimensionSpace\WeightedDimensionSpacePoint([
                'dimensionA' => new Dimension\ContentDimensionValue($coordinates[0], new Dimension\ContentDimensionValueSpecializationDepth($coordinates[2])),
                'dimensionB' => new Dimension\ContentDimensionValue($coordinates[1], new Dimension\ContentDimensionValueSpecializationDepth($coordinates[3]))
            ]);
            $expectedWeightedDimensionSpacePoints[$weightedDimensionSpacePoints->getIdentityHash()] = $weightedDimensionSpacePoints;
        }

        $this->assertEquals(
            $expectedWeightedDimensionSpacePoints,
            $this->subject->getWeightedDimensionSpacePoints()
        );

        foreach ($expectedWeightedDimensionSpacePoints as $weightedDimensionSpacePointsIdentifier => $weightedDimensionSpacePoints) {
            $this->assertEquals($weightedDimensionSpacePoints, $this->subject->getWeightedDimensionSpacePointByHash($weightedDimensionSpacePointsIdentifier));
        }
    }

    /**
     * @test
     */
    public function initializeWeightedDimensionSpacePointsCorrectlyInitializesSingularWeightedDimensionSpacePointsWithNoDimensionsGiven()
    {
        $this->setUpNullExample();
        $this->subject->_call('initializeWeightedDimensionSpacePoints');

        $singularWeightedDimensionSpacePoints = new DimensionSpace\WeightedDimensionSpacePoint([]);
        $this->assertEquals(
            [$singularWeightedDimensionSpacePoints->getIdentityHash() => $singularWeightedDimensionSpacePoints],
            $this->subject->getWeightedDimensionSpacePoints()
        );
    }

    /**
     * @test
     */
    public function getWeightedDimensionSpacePointsByDimensionSpacePointReturnsNullForPointOutsideTheAllowedDimensionSpace()
    {
        $this->setUpVariationExample();

        $this->assertSame(
            null,
            $this->subject->getWeightedDimensionSpacePointByDimensionSpacePoint(new Domain\ValueObject\DimensionSpacePoint([
                'undefinedDimension' => 'undefinedDimensionValue'
            ]))
        );
    }

    /**
     * @test
     */
    public function initializeVariationsCorrectlyInitializesSpecializations()
    {
        $this->setUpVariationExample();
        $this->subject->_call('initializeVariations');

        foreach ([
                     [
                         ['value1', 'value1'],
                         [
                             ['value1', 'value1.1', 1],
                             ['value1', 'value1.2', 1],
                             ['value1', 'value1.1.1', 2],
                             ['value1.1', 'value1', 3],
                             ['value1.1', 'value1.1', 4],
                             ['value1.1', 'value1.2', 4],
                             ['value1.1', 'value1.1.1', 5],
                             ['value1.2', 'value1', 3],
                             ['value1.2', 'value1.1', 4],
                             ['value1.2', 'value1.2', 4],
                             ['value1.2', 'value1.1.1', 5],
                             ['value1.1.1', 'value1', 6],
                             ['value1.1.1', 'value1.1', 7],
                             ['value1.1.1', 'value1.2', 7],
                             ['value1.1.1', 'value1.1.1', 8]
                         ]
                     ],
                     [
                         ['value1', 'value1.1'],
                         [
                             ['value1', 'value1.1.1', 1],
                             ['value1.1', 'value1.1', 3],
                             ['value1.1', 'value1.1.1', 4],
                             ['value1.2', 'value1.1', 3],
                             ['value1.2', 'value1.1.1', 4],
                             ['value1.1.1', 'value1.1', 6],
                             ['value1.1.1', 'value1.1.1', 7]
                         ],
                     ],
                     [
                         ['value1', 'value1.2'],
                         [
                             ['value1.1', 'value1.2', 3],
                             ['value1.2', 'value1.2', 3],
                             ['value1.1.1', 'value1.2', 6]
                         ],
                     ],
                     [
                         ['value1', 'value1.1.1'],
                         [
                             ['value1.1', 'value1.1.1', 3],
                             ['value1.2', 'value1.1.1', 3],
                             ['value1.1.1', 'value1.1.1', 6]
                         ],
                     ],
                     [
                         ['value1.1', 'value1'],
                         [
                             ['value1.1', 'value1.1', 1],
                             ['value1.1', 'value1.2', 1],
                             ['value1.1', 'value1.1.1', 2],
                             ['value1.1.1', 'value1', 3],
                             ['value1.1.1', 'value1.1', 4],
                             ['value1.1.1', 'value1.2', 4],
                             ['value1.1.1', 'value1.1.1', 5]
                         ],
                     ],
                     [
                         ['value1.1', 'value1.1'],
                         [
                             ['value1.1', 'value1.1.1', 1],
                             ['value1.1.1', 'value1.1', 3],
                             ['value1.1.1', 'value1.1.1', 4]
                         ]
                     ],
                     [
                         ['value1.1', 'value1.2'],
                         [
                             ['value1.1.1', 'value1.2', 3]
                         ]
                     ],
                     [
                         ['value1.1', 'value1.1.1'],
                         [
                             ['value1.1.1', 'value1.1.1', 3]
                         ]
                     ],
                     [
                         ['value1.2', 'value1'],
                         [
                             ['value1.2', 'value1.1', 1],
                             ['value1.2', 'value1.2', 1],
                             ['value1.2', 'value1.1.1', 2]
                         ]
                     ],
                     [
                         ['value1.2', 'value1.1'],
                         [
                             ['value1.2', 'value1.1.1', 1]
                         ]
                     ],
                     [
                         ['value1.2', 'value1.2'],
                         []
                     ],
                     [
                         ['value1.2', 'value1.1.1'],
                         []
                     ],
                     [
                         ['value1.1.1', 'value1'],
                         [
                             ['value1.1.1', 'value1.1', 1],
                             ['value1.1.1', 'value1.2', 1],
                             ['value1.1.1', 'value1.1.1', 2]
                         ]
                     ],
                     [
                         ['value1.1.1', 'value1.1'],
                         [
                             ['value1.1.1', 'value1.1.1', 1]
                         ]
                     ],
                     [
                         ['value1.1.1', 'value1.2'],
                         []
                     ],
                     [
                         ['value1.1.1', 'value1.1.1'],
                         []
                     ]
                 ] as $variationData) {
            $generalizationCoordinates = $variationData[0];
            $specializationRecordSet = $variationData[1];

            $expectedIndexedSpecializations = [];
            $expectedWeightedSpecializations = [];
            foreach ($specializationRecordSet as $specializationRecord) {
                $specialization = new Domain\ValueObject\DimensionSpacePoint([
                    'dimensionA' => $specializationRecord[0],
                    'dimensionB' => $specializationRecord[1]
                ]);
                $expectedIndexedSpecializations[$specialization->getHash()] = $specialization;
                $expectedWeightedSpecializations[$specializationRecord[2]][$specialization->getHash()] = $specialization;
            }

            $generalization = new Domain\ValueObject\DimensionSpacePoint([
                'dimensionA' => $generalizationCoordinates[0],
                'dimensionB' => $generalizationCoordinates[1]
            ]);

            $this->assertEquals(
                $expectedIndexedSpecializations,
                $this->subject->getIndexedSpecializations($generalization)
            );

            $this->assertEquals(
                $expectedWeightedSpecializations,
                $this->subject->getWeightedSpecializations($generalization)
            );
        }
    }

    /**
     * @test
     */
    public function initializeVariationsCorrectlyInitializesGeneralizations()
    {
        $this->setUpVariationExample();
        $this->subject->_call('initializeVariations');

        foreach ([
                     [
                         ['value1', 'value1'],
                         []
                     ],
                     [
                         ['value1', 'value1.1'],
                         [
                             ['value1', 'value1', 1]
                         ],
                     ],
                     [
                         ['value1', 'value1.2'],
                         [
                             ['value1', 'value1', 1]
                         ],
                     ],
                     [
                         ['value1', 'value1.1.1'],
                         [
                             ['value1', 'value1.1', 1],
                             ['value1', 'value1', 2]
                         ],
                     ],
                     [
                         ['value1.1', 'value1'],
                         [
                             ['value1', 'value1', 3]
                         ],
                     ],
                     [
                         ['value1.1', 'value1.1'],
                         [
                             ['value1.1', 'value1', 1],
                             ['value1', 'value1.1', 3],
                             ['value1', 'value1', 4]
                         ]
                     ],
                     [
                         ['value1.1', 'value1.2'],
                         [
                             ['value1.1', 'value1', 1],
                             ['value1', 'value1.2', 3],
                             ['value1', 'value1', 4]
                         ]
                     ],
                     [
                         ['value1.1', 'value1.1.1'],
                         [
                             ['value1.1', 'value1.1', 1],
                             ['value1.1', 'value1', 2],
                             ['value1', 'value1.1.1', 3],
                             ['value1', 'value1.1', 4],
                             ['value1', 'value1', 5]
                         ]
                     ],
                     [
                         ['value1.2', 'value1'],
                         [
                             ['value1', 'value1', 3]
                         ]
                     ],
                     [
                         ['value1.2', 'value1.1'],
                         [
                             ['value1.2', 'value1', 1],
                             ['value1', 'value1.1', 3],
                             ['value1', 'value1', 4]
                         ]
                     ],
                     [
                         ['value1.2', 'value1.2'],
                         [
                             ['value1.2', 'value1', 1],
                             ['value1', 'value1.2', 3],
                             ['value1', 'value1', 4]
                         ]
                     ],
                     [
                         ['value1.2', 'value1.1.1'],
                         [
                             ['value1.2', 'value1.1', 1],
                             ['value1.2', 'value1', 2],
                             ['value1', 'value1.1.1', 3],
                             ['value1', 'value1.1', 4],
                             ['value1', 'value1', 5]
                         ]
                     ],
                     [
                         ['value1.1.1', 'value1'],
                         [
                             ['value1.1', 'value1', 3],
                             ['value1', 'value1', 6],
                         ]
                     ],
                     [
                         ['value1.1.1', 'value1.1'],
                         [
                             ['value1.1.1', 'value1', 1],
                             ['value1.1', 'value1.1', 3],
                             ['value1.1', 'value1', 4],
                             ['value1', 'value1.1', 6],
                             ['value1', 'value1', 7]
                         ]
                     ],
                     [
                         ['value1.1.1', 'value1.2'],
                         [
                             ['value1.1.1', 'value1', 1],
                             ['value1.1', 'value1.2', 3],
                             ['value1.1', 'value1', 4],
                             ['value1', 'value1.2', 6],
                             ['value1', 'value1', 7]
                         ]
                     ],
                     [
                         ['value1.1.1', 'value1.1.1'],
                         [
                             ['value1.1.1', 'value1.1', 1],
                             ['value1.1.1', 'value1', 2],
                             ['value1.1', 'value1.1.1', 3],
                             ['value1.1', 'value1.1', 4],
                             ['value1.1', 'value1', 5],
                             ['value1', 'value1.1.1', 6],
                             ['value1', 'value1.1', 7],
                             ['value1', 'value1', 8]
                         ]
                     ]
                 ] as $variationData) {
            $specializationCoordinates = $variationData[0];
            $generalizationRecordSet = $variationData[1];

            $expectedIndexedGeneralizations = [];
            $expectedWeightedGeneralizations = [];
            foreach ($generalizationRecordSet as $generalizationRecord) {
                $generalization = new Domain\ValueObject\DimensionSpacePoint([
                    'dimensionA' => $generalizationRecord[0],
                    'dimensionB' => $generalizationRecord[1]
                ]);
                $expectedIndexedGeneralizations[$generalization->getHash()] = $generalization;
                $expectedWeightedGeneralizations[$generalizationRecord[2]] = $generalization;
            }

            $specializedDimensionSpacePoint = new Domain\ValueObject\DimensionSpacePoint([
                'dimensionA' => $specializationCoordinates[0],
                'dimensionB' => $specializationCoordinates[1]
            ]);

            $this->assertEquals(
                $expectedIndexedGeneralizations,
                $this->subject->getIndexedGeneralizations($specializedDimensionSpacePoint)
            );

            $this->assertEquals(
                $expectedWeightedGeneralizations,
                $this->subject->getWeightedGeneralizations($specializedDimensionSpacePoint)
            );
        }
    }

    /**
     * @test
     */
    public function initializeVariationsCorrectlyInitializesPrimaryGeneralizations()
    {
        $this->setUpVariationExample();
        $this->subject->_call('initializeVariations');

        foreach ([
                     [
                         ['value1', 'value1'],
                         null
                     ],
                     [
                         ['value1', 'value1.1'],
                         ['value1', 'value1'],
                     ],
                     [
                         ['value1', 'value1.2'],
                         ['value1', 'value1'],
                     ],
                     [
                         ['value1', 'value1.1.1'],
                         ['value1', 'value1.1'],
                     ],
                     [
                         ['value1.1', 'value1'],
                         ['value1', 'value1'],
                     ],
                     [
                         ['value1.1', 'value1.1'],
                         ['value1.1', 'value1'],
                     ],
                     [
                         ['value1.1', 'value1.2'],
                         ['value1.1', 'value1'],
                     ],
                     [
                         ['value1.1', 'value1.1.1'],
                         ['value1.1', 'value1.1'],
                     ],
                     [
                         ['value1.2', 'value1'],
                         ['value1', 'value1'],
                     ],
                     [
                         ['value1.2', 'value1.1'],
                         ['value1.2', 'value1'],
                     ],
                     [
                         ['value1.2', 'value1.2'],
                         ['value1.2', 'value1'],
                     ],
                     [
                         ['value1.2', 'value1.1.1'],
                         ['value1.2', 'value1.1'],
                     ],
                     [
                         ['value1.1.1', 'value1'],
                         ['value1.1', 'value1'],
                     ],
                     [
                         ['value1.1.1', 'value1.1'],
                         ['value1.1.1', 'value1'],
                     ],
                     [
                         ['value1.1.1', 'value1.2'],
                         ['value1.1.1', 'value1'],
                     ],
                     [
                         ['value1.1.1', 'value1.1.1'],
                         ['value1.1.1', 'value1.1'],
                     ],
                 ] as $variationData) {
            $specializationCoordinates = $variationData[0];
            $primaryGeneralizationCoordinates = $variationData[1];

            $specializationDimensionSpacePoint = new Domain\ValueObject\DimensionSpacePoint([
                'dimensionA' => $specializationCoordinates[0],
                'dimensionB' => $specializationCoordinates[1]
            ]);
            $expectedPrimaryGeneralizationSpacePoint = $primaryGeneralizationCoordinates
                ? new Domain\ValueObject\DimensionSpacePoint([
                    'dimensionA' => $primaryGeneralizationCoordinates[0],
                    'dimensionB' => $primaryGeneralizationCoordinates[1]
                ])
                : null;

            $this->assertEquals(
                $expectedPrimaryGeneralizationSpacePoint,
                $this->subject->getPrimaryGeneralization($specializationDimensionSpacePoint)
            );
        }
    }

    /**
     * @test
     * @expectedException \Neos\ContentRepository\Exception\DimensionSpacePointNotFound
     * @throws \Neos\ContentRepository\Exception\DimensionSpacePointNotFound
     */
    public function getSpecializationSetThrowsExceptionForDimensionSpacePointOutsideTheAllowedSubspace()
    {
        $this->setUpVariationExample();

        $this->subject->getSpecializationSet(new Domain\ValueObject\DimensionSpacePoint(['undefinedDimension' => 'undefinedDimensionValue']));
    }

    /**
     * @test
     * @throws \Neos\ContentRepository\Exception\DimensionSpacePointNotFound
     */
    public function getSpecializationSetReturnsEmptySetForOriginWithoutSpecializationsAndWithoutOriginInclusion()
    {
        $this->setUpVariationExample();

        $origin = new Domain\ValueObject\DimensionSpacePoint([
            'dimensionA' => 'value1.1.1',
            'dimensionB' => 'value1.1.1'
        ]);

        $this->assertEquals(
            new Domain\ValueObject\DimensionSpacePointSet([]),
            $this->subject->getSpecializationSet($origin, false)
        );
    }

    /**
     * @test
     * @throws \Neos\ContentRepository\Exception\DimensionSpacePointNotFound
     */
    public function getSpecializationSetReturnsSetConsistingOnlyOfOriginForOriginWithoutSpecializationsAndWithOriginInclusion()
    {
        $this->setUpVariationExample();

        $origin = new Domain\ValueObject\DimensionSpacePoint([
            'dimensionA' => 'value1.1.1',
            'dimensionB' => 'value1.1.1'
        ]);

        $this->assertEquals(
            new Domain\ValueObject\DimensionSpacePointSet([$origin]),
            $this->subject->getSpecializationSet($origin, true)
        );
    }

    /**
     * @test
     * @throws \Neos\ContentRepository\Exception\DimensionSpacePointNotFound
     */
    public function getSpecializationSetReturnsCorrectSpecializationSetForOriginWithSpecializations()
    {
        $this->setUpVariationExample();

        $origin = new Domain\ValueObject\DimensionSpacePoint([
            'dimensionA' => 'value1.1',
            'dimensionB' => 'value1.1'
        ]);

        $this->assertEquals(
            new Domain\ValueObject\DimensionSpacePointSet([
                new Domain\ValueObject\DimensionSpacePoint([
                    'dimensionA' => 'value1.1',
                    'dimensionB' => 'value1.1'
                ]),
                new Domain\ValueObject\DimensionSpacePoint([
                    'dimensionA' => 'value1.1',
                    'dimensionB' => 'value1.1.1'
                ]),
                new Domain\ValueObject\DimensionSpacePoint([
                    'dimensionA' => 'value1.1.1',
                    'dimensionB' => 'value1.1'
                ]),
                new Domain\ValueObject\DimensionSpacePoint([
                    'dimensionA' => 'value1.1.1',
                    'dimensionB' => 'value1.1.1'
                ]),
            ]),
            $this->subject->getSpecializationSet($origin, true)
        );
    }

    protected function setUpVariationExample()
    {
        /** @var DimensionSpace\InterDimensionalVariationGraph $graph */
        $this->subject = $this->getAccessibleMock(DimensionSpace\InterDimensionalVariationGraph::class, ['dummy']);

        $source = new Fixtures\VariationExampleDimensionSource();
        $this->inject($this->subject, 'contentDimensionSource', $source);
        $zookeeper = new Dimension\ContentDimensionZookeeper();
        $this->inject($zookeeper, 'contentDimensionSource', $source);
        $this->inject($this->subject, 'contentDimensionZookeeper', $zookeeper);
        $subspace = new DimensionSpace\AllowedDimensionSubspace();
        $this->inject($subspace, 'contentDimensionZookeeper', $zookeeper);
        $this->inject($this->subject, 'allowedDimensionSubspace', $subspace);
    }

    protected function setUpNullExample()
    {
        /** @var DimensionSpace\InterDimensionalVariationGraph $graph */
        $this->subject = $this->getAccessibleMock(DimensionSpace\InterDimensionalVariationGraph::class, ['dummy']);

        $source = new Fixtures\NullExampleDimensionSource();
        $this->inject($this->subject, 'contentDimensionSource', $source);
        $zookeeper = new Dimension\ContentDimensionZookeeper();
        $this->inject($zookeeper, 'contentDimensionSource', $source);
        $this->inject($this->subject, 'contentDimensionZookeeper', $zookeeper);
        $subspace = new DimensionSpace\AllowedDimensionSubspace();
        $this->inject($subspace, 'contentDimensionZookeeper', $zookeeper);
        $this->inject($this->subject, 'allowedDimensionSubspace', $subspace);
    }
}
