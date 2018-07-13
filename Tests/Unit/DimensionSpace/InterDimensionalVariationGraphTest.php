<?php

namespace Neos\ContentRepository\DimensionSpace\Tests\Unit\DimensionSpace;

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\DimensionSpace\Dimension;
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\ObjectAccess;

/**
 * Unit test cases for the inter dimensional variation graph
 */
class InterDimensionalVariationGraphTest extends UnitTestCase
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
            $this->subject->getWeightedDimensionSpacePointByDimensionSpacePoint(new DimensionSpace\DimensionSpacePoint([
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
                $specialization = new DimensionSpace\DimensionSpacePoint([
                    'dimensionA' => $specializationRecord[0],
                    'dimensionB' => $specializationRecord[1]
                ]);
                $expectedIndexedSpecializations[$specialization->getHash()] = $specialization;
                $expectedWeightedSpecializations[$specializationRecord[2]][$specialization->getHash()] = $specialization;
            }

            $generalization = new DimensionSpace\DimensionSpacePoint([
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
                $generalization = new DimensionSpace\DimensionSpacePoint([
                    'dimensionA' => $generalizationRecord[0],
                    'dimensionB' => $generalizationRecord[1]
                ]);
                $expectedIndexedGeneralizations[$generalization->getHash()] = $generalization;
                $expectedWeightedGeneralizations[$generalizationRecord[2]] = $generalization;
            }

            $specializedDimensionSpacePoint = new DimensionSpace\DimensionSpacePoint([
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

            $specializationDimensionSpacePoint = new DimensionSpace\DimensionSpacePoint([
                'dimensionA' => $specializationCoordinates[0],
                'dimensionB' => $specializationCoordinates[1]
            ]);
            $expectedPrimaryGeneralizationSpacePoint = $primaryGeneralizationCoordinates
                ? new DimensionSpace\DimensionSpacePoint([
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
     */
    public function getSpecializationSetThrowsExceptionForDimensionSpacePointOutsideTheAllowedSubspace()
    {
        $this->setUpVariationExample();

        $this->subject->getSpecializationSet(new DimensionSpace\DimensionSpacePoint(['undefinedDimension' => 'undefinedDimensionValue']));
    }

    /**
     * @test
     */
    public function getSpecializationSetReturnsEmptySetForOriginWithoutSpecializationsAndWithoutOriginInclusion()
    {
        $this->setUpVariationExample();

        $origin = new DimensionSpace\DimensionSpacePoint([
            'dimensionA' => 'value1.1.1',
            'dimensionB' => 'value1.1.1'
        ]);

        $this->assertEquals(
            new DimensionSpace\DimensionSpacePointSet([]),
            $this->subject->getSpecializationSet($origin, false)
        );
    }

    /**
     * @test
     */
    public function getSpecializationSetReturnsSetConsistingOnlyOfOriginForOriginWithoutSpecializationsAndWithOriginInclusion()
    {
        $this->setUpVariationExample();

        $origin = new DimensionSpace\DimensionSpacePoint([
            'dimensionA' => 'value1.1.1',
            'dimensionB' => 'value1.1.1'
        ]);

        $this->assertEquals(
            new DimensionSpace\DimensionSpacePointSet([$origin]),
            $this->subject->getSpecializationSet($origin, true)
        );
    }

    /**
     * @test
     */
    public function getSpecializationSetReturnsCorrectSpecializationSetForOriginWithSpecializations()
    {
        $this->setUpVariationExample();

        $origin = new DimensionSpace\DimensionSpacePoint([
            'dimensionA' => 'value1.1',
            'dimensionB' => 'value1.1'
        ]);

        $this->assertEquals(
            new DimensionSpace\DimensionSpacePointSet([
                new DimensionSpace\DimensionSpacePoint([
                    'dimensionA' => 'value1.1',
                    'dimensionB' => 'value1.1'
                ]),
                new DimensionSpace\DimensionSpacePoint([
                    'dimensionA' => 'value1.1',
                    'dimensionB' => 'value1.1.1'
                ]),
                new DimensionSpace\DimensionSpacePoint([
                    'dimensionA' => 'value1.1.1',
                    'dimensionB' => 'value1.1'
                ]),
                new DimensionSpace\DimensionSpacePoint([
                    'dimensionA' => 'value1.1.1',
                    'dimensionB' => 'value1.1.1'
                ]),
            ]),
            $this->subject->getSpecializationSet($origin, true)
        );
    }

    protected function setUpVariationExample()
    {
        $dimensionSource = new Fixtures\VariationExampleDimensionSource();
        $dimensionZookeeper = new Dimension\ContentDimensionZookeeper($dimensionSource);
        $allowedSubspace = new DimensionSpace\AllowedDimensionSubspace($dimensionZookeeper);
        $this->subject = $this->getAccessibleMock(DimensionSpace\InterDimensionalVariationGraph::class, ['dummy'], [
            $dimensionSource,
            $dimensionZookeeper,
            $allowedSubspace
        ]);
    }

    protected function setUpNullExample()
    {
        $dimensionSource = new Fixtures\NullExampleDimensionSource();
        $dimensionZookeeper = new Dimension\ContentDimensionZookeeper($dimensionSource);
        $allowedSubspace = new DimensionSpace\AllowedDimensionSubspace($dimensionZookeeper);
        $this->subject = $this->getAccessibleMock(DimensionSpace\InterDimensionalVariationGraph::class, ['dummy'], [
            $dimensionSource,
            $dimensionZookeeper,
            $allowedSubspace
        ]);
    }

    /**
     * @test
     * @throws \ReflectionException
     */
    public function determineWeightNormalizationBaseEvaluatesToMaximumDimensionDepthPlusOne()
    {
        $firstDummy = new Dimension\ContentDimensionValue('firstDummy');
        $firstDimension = new Dimension\ContentDimension(new Dimension\ContentDimensionIdentifier('first'), [$firstDummy], $firstDummy);
        $firstDepth = new Dimension\ContentDimensionValueSpecializationDepth(random_int(0, 100));
        ObjectAccess::setProperty($firstDimension, 'maximumDepth', $firstDepth, true);

        $secondDummy = new Dimension\ContentDimensionValue('secondDummy');
        $secondDimension = new Dimension\ContentDimension(new Dimension\ContentDimensionIdentifier('second'), [$secondDummy], $secondDummy);
        $secondDepth = new Dimension\ContentDimensionValueSpecializationDepth(random_int(0, 100));
        ObjectAccess::setProperty($secondDimension, 'maximumDepth', $secondDepth, true);

        /** @var DimensionSpace\InterDimensionalVariationGraph $graph */
        $graph = $this->getAccessibleMock(DimensionSpace\InterDimensionalVariationGraph::class, ['dummy']);
        $this->inject($graph, 'contentDimensionSource', $this->createDimensionSourceMock(['first' => $firstDimension, 'second' => $secondDimension]));

        $this->assertSame(max($firstDepth->getDepth(), $secondDepth->getDepth()) + 1, $graph->_call('determineWeightNormalizationBase'));
    }

    /**
     * @param array|Dimension\ContentDimension[] $contentDimensions
     * @return Dimension\ContentDimensionSourceInterface
     * @throws \ReflectionException
     */
    protected function createDimensionSourceMock(array $contentDimensions): Dimension\ContentDimensionSourceInterface
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Dimension\ContentDimensionSourceInterface $mockDimensionSource */
        $mockDimensionSource = $this->createMock(Dimension\ContentDimensionSourceInterface::class);
        $mockDimensionSource->method('getContentDimensionsOrderedByPriority')
            ->willReturn($contentDimensions);

        return $mockDimensionSource;
    }
}
