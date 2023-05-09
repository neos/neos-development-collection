<?php

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Neos\ContentRepository\Core\Tests\Unit\DimensionSpace;

use Neos\ContentRepository\Core\Dimension;
use Neos\ContentRepository\Core\DimensionSpace;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

// NOTE: not sure why this is needed
require_once(__DIR__ . '/Fixtures/VariationExampleDimensionSource.php');
require_once(__DIR__ . '/Fixtures/NullExampleDimensionSource.php');

/**
 * Unit test cases for the inter dimensional variation graph
 */
class InterDimensionalVariationGraphTest extends TestCase
{
    protected DimensionSpace\InterDimensionalVariationGraph $subject;

    public function testInitializeWeightedDimensionSpacePointsCorrectlyInitializesAllAvailableWeightedDimensionSpacePointsWithDimensionsWithVariationsGiven()
    {
        $this->setUpVariationExample();
        $reflection = new \ReflectionClass($this->subject);
        $reflection->getMethod('initializeWeightedDimensionSpacePoints')->invoke($this->subject);

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
                'dimensionA' => new Dimension\ContentDimensionValue(
                    $coordinates[0],
                    new Dimension\ContentDimensionValueSpecializationDepth($coordinates[2])
                ),
                'dimensionB' => new Dimension\ContentDimensionValue(
                    $coordinates[1],
                    new Dimension\ContentDimensionValueSpecializationDepth($coordinates[3])
                )
            ]);
            $expectedWeightedDimensionSpacePoints[$weightedDimensionSpacePoints->getIdentityHash()] = $weightedDimensionSpacePoints;
        }

        $this->assertEquals(
            $expectedWeightedDimensionSpacePoints,
            $this->subject->getWeightedDimensionSpacePoints()
        );

        foreach ($expectedWeightedDimensionSpacePoints as $weightedDimensionSpacePointsIdentifier => $weightedDimensionSpacePoints) {
            $this->assertEquals(
                $weightedDimensionSpacePoints,
                $this->subject->getWeightedDimensionSpacePointByHash($weightedDimensionSpacePointsIdentifier)
            );
        }
    }

    public function testInitializeWeightedDimensionSpacePointsCorrectlyInitializesSingularWeightedDimensionSpacePointsWithNoDimensionsGiven()
    {
        $this->setUpNullExample();
        $reflection = new \ReflectionClass($this->subject);
        $reflection->getMethod('initializeWeightedDimensionSpacePoints')->invoke($this->subject);

        $singularWeightedDimensionSpacePoints = new DimensionSpace\WeightedDimensionSpacePoint([]);
        $this->assertEquals(
            [$singularWeightedDimensionSpacePoints->getIdentityHash() => $singularWeightedDimensionSpacePoints],
            $this->subject->getWeightedDimensionSpacePoints()
        );
    }

    public function testGetWeightedDimensionSpacePointsByDimensionSpacePointReturnsNullForPointOutsideTheAllowedDimensionSpace()
    {
        $this->setUpVariationExample();

        $this->assertSame(
            null,
            $this->subject->getWeightedDimensionSpacePointByDimensionSpacePoint(DimensionSpace\DimensionSpacePoint::fromArray([
                'undefinedDimension' => 'undefinedDimensionValue'
            ]))
        );
    }

    public function testInitializeVariationsCorrectlyInitializesSpecializations()
    {
        $this->setUpVariationExample();
        $reflection = new \ReflectionClass($this->subject);
        $reflection->getMethod('initializeVariations')->invoke($this->subject);

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
                $specialization = DimensionSpace\DimensionSpacePoint::fromArray([
                    'dimensionA' => $specializationRecord[0],
                    'dimensionB' => $specializationRecord[1]
                ]);
                $expectedIndexedSpecializations[$specialization->hash] = $specialization;
                $expectedWeightedSpecializations[$specializationRecord[2]][$specialization->hash] = $specialization;
            }

            $generalization = DimensionSpace\DimensionSpacePoint::fromArray([
                'dimensionA' => $generalizationCoordinates[0],
                'dimensionB' => $generalizationCoordinates[1]
            ]);

            $this->assertEquals(
                $expectedIndexedSpecializations,
                $this->subject->getIndexedSpecializations($generalization)->points
            );

            $this->assertEquals(
                $expectedWeightedSpecializations,
                $this->subject->getWeightedSpecializations($generalization)
            );
        }
    }

    public function testInitializeVariationsCorrectlyInitializesGeneralizations()
    {
        $this->setUpVariationExample();
        $reflection = new \ReflectionClass($this->subject);
        $reflection->getMethod('initializeVariations')->invoke($this->subject);

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
                $generalization = DimensionSpace\DimensionSpacePoint::fromArray([
                    'dimensionA' => $generalizationRecord[0],
                    'dimensionB' => $generalizationRecord[1]
                ]);
                $expectedIndexedGeneralizations[$generalization->hash] = $generalization;
                $expectedWeightedGeneralizations[$generalizationRecord[2]] = $generalization;
            }

            $specializedDimensionSpacePoint = DimensionSpace\DimensionSpacePoint::fromArray([
                'dimensionA' => $specializationCoordinates[0],
                'dimensionB' => $specializationCoordinates[1]
            ]);

            $this->assertEquals(
                $expectedIndexedGeneralizations,
                $this->subject->getIndexedGeneralizations($specializedDimensionSpacePoint)->points
            );

            $this->assertEquals(
                $expectedWeightedGeneralizations,
                $this->subject->getWeightedGeneralizations($specializedDimensionSpacePoint)
            );
        }
    }

    public function testInitializeVariationsCorrectlyInitializesPrimaryGeneralizations()
    {
        $this->setUpVariationExample();
        $reflection = new \ReflectionClass($this->subject);
        $reflection->getMethod('initializeVariations')->invoke($this->subject);

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

            $specializationDimensionSpacePoint = DimensionSpace\DimensionSpacePoint::fromArray([
                'dimensionA' => $specializationCoordinates[0],
                'dimensionB' => $specializationCoordinates[1]
            ]);
            $expectedPrimaryGeneralizationSpacePoint = $primaryGeneralizationCoordinates
                ? DimensionSpace\DimensionSpacePoint::fromArray([
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

    public function testGetSpecializationSetThrowsExceptionForDimensionSpacePointOutsideTheAllowedSubspace()
    {
        $this->expectException(DimensionSpace\Exception\DimensionSpacePointNotFound::class);
        $this->setUpVariationExample();

        $this->subject->getSpecializationSet(DimensionSpace\DimensionSpacePoint::fromArray(['undefinedDimension' => 'undefinedDimensionValue']));
    }

    public function testGetSpecializationSetReturnsEmptySetForOriginWithoutSpecializationsAndWithoutOriginInclusion()
    {
        $this->setUpVariationExample();

        $origin = DimensionSpace\DimensionSpacePoint::fromArray([
            'dimensionA' => 'value1.1.1',
            'dimensionB' => 'value1.1.1'
        ]);

        $this->assertEquals(
            new DimensionSpace\DimensionSpacePointSet([]),
            $this->subject->getSpecializationSet($origin, false)
        );
    }

    public function testGetSpecializationSetReturnsSetConsistingOnlyOfOriginForOriginWithoutSpecializationsAndWithOriginInclusion()
    {
        $this->setUpVariationExample();

        $origin = DimensionSpace\DimensionSpacePoint::fromArray([
            'dimensionA' => 'value1.1.1',
            'dimensionB' => 'value1.1.1'
        ]);

        $this->assertEquals(
            new DimensionSpace\DimensionSpacePointSet([$origin]),
            $this->subject->getSpecializationSet($origin, true)
        );
    }

    public function testGetSpecializationSetReturnsCorrectSpecializationSetForOriginWithSpecializations()
    {
        $this->setUpVariationExample();

        $origin = DimensionSpace\DimensionSpacePoint::fromArray([
            'dimensionA' => 'value1.1',
            'dimensionB' => 'value1.1'
        ]);

        $this->assertEquals(
            new DimensionSpace\DimensionSpacePointSet([
                DimensionSpace\DimensionSpacePoint::fromArray([
                    'dimensionA' => 'value1.1',
                    'dimensionB' => 'value1.1'
                ]),
                DimensionSpace\DimensionSpacePoint::fromArray([
                    'dimensionA' => 'value1.1',
                    'dimensionB' => 'value1.1.1'
                ]),
                DimensionSpace\DimensionSpacePoint::fromArray([
                    'dimensionA' => 'value1.1.1',
                    'dimensionB' => 'value1.1'
                ]),
                DimensionSpace\DimensionSpacePoint::fromArray([
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
        $dimensionZookeeper = new DimensionSpace\ContentDimensionZookeeper($dimensionSource);
        $this->subject = new DimensionSpace\InterDimensionalVariationGraph(
            $dimensionSource,
            $dimensionZookeeper
        );
    }

    protected function setUpNullExample(): void
    {
        $dimensionSource = new Fixtures\NullExampleDimensionSource();
        $dimensionZookeeper = new DimensionSpace\ContentDimensionZookeeper($dimensionSource);
        $this->subject = new DimensionSpace\InterDimensionalVariationGraph(
            $dimensionSource,
            $dimensionZookeeper
        );
    }

    public function testDetermineWeightNormalizationBaseEvaluatesToMaximumDimensionDepthPlusOne()
    {
        $firstDepth = new Dimension\ContentDimensionValueSpecializationDepth(random_int(0, 100));
        $firstDummy = new Dimension\ContentDimensionValue('firstDummy', $firstDepth);
        $firstDimension = new Dimension\ContentDimension(
            new Dimension\ContentDimensionId('first'),
            new Dimension\ContentDimensionValues([$firstDummy]),
            Dimension\ContentDimensionValueVariationEdges::createEmpty()
        );

        $secondDepth = new Dimension\ContentDimensionValueSpecializationDepth(random_int(0, 100));
        $secondDummy = new Dimension\ContentDimensionValue('secondDummy', $secondDepth);
        $secondDimension = new Dimension\ContentDimension(
            new Dimension\ContentDimensionId('second'),
            new Dimension\ContentDimensionValues([$secondDummy]),
            Dimension\ContentDimensionValueVariationEdges::createEmpty()
        );

        $dimensionSource = $this->createDimensionSourceMock(['first' => $firstDimension, 'second' => $secondDimension]);
        $dimensionZookeeper = new DimensionSpace\ContentDimensionZookeeper($dimensionSource);
        $graph =  new DimensionSpace\InterDimensionalVariationGraph(
            $dimensionSource,
            $dimensionZookeeper
        );

        $reflection = new \ReflectionClass($graph);
        $this->assertSame(
            max($firstDepth->value, $secondDepth->value) + 1,
            $reflection->getMethod('determineWeightNormalizationBase')->invoke($graph)
        );
    }

    public function testGetVariantTypeCorrectlyDeterminesTheVariantType()
    {
        $this->setUpVariationExample();

        $specialization = DimensionSpace\DimensionSpacePoint::fromArray(['dimensionA' => 'value1.1', 'dimensionB' => 'value1']);
        $generalization = DimensionSpace\DimensionSpacePoint::fromArray(['dimensionA' => 'value1', 'dimensionB' => 'value1']);
        $peer = DimensionSpace\DimensionSpacePoint::fromArray(['dimensionA' => 'value1.2', 'dimensionB' => 'value1']);

        self::assertSame(DimensionSpace\VariantType::TYPE_SPECIALIZATION, $this->subject->getVariantType($specialization, $generalization));
        self::assertSame(DimensionSpace\VariantType::TYPE_GENERALIZATION, $this->subject->getVariantType($generalization, $specialization));
        self::assertSame(DimensionSpace\VariantType::TYPE_PEER, $this->subject->getVariantType($specialization, $peer));
        self::assertSame(DimensionSpace\VariantType::TYPE_PEER, $this->subject->getVariantType($peer, $specialization));
        self::assertSame(DimensionSpace\VariantType::TYPE_SAME, $this->subject->getVariantType($peer, $peer));
    }

    /**
     * @param array<string,Dimension\ContentDimension> $contentDimensions
     */
    protected function createDimensionSourceMock(array $contentDimensions): Dimension\ContentDimensionSourceInterface
    {
        /** @var MockObject|Dimension\ContentDimensionSourceInterface $mockDimensionSource */
        $mockDimensionSource = $this->createMock(Dimension\ContentDimensionSourceInterface::class);
        $mockDimensionSource->method('getContentDimensionsOrderedByPriority')
            ->willReturn($contentDimensions);

        return $mockDimensionSource;
    }
}
