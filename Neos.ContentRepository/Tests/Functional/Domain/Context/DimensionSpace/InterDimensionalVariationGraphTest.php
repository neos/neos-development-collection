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
    public function initializeSubgraphsCorrectlyInitializesAllAvailableSubgraphsWithDimensionsWithVariationsGiven()
    {
        $this->setUpVariationExample();
        $this->subject->_call('initializeSubgraphs');

        $expectedSubgraphCoordinates = [
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

        $expectedSubgraphs = [];
        foreach ($expectedSubgraphCoordinates as $coordinates) {
            $subgraph = new DimensionSpace\ContentSubgraph([
                'dimensionA' => new Dimension\ContentDimensionValue($coordinates[0], new Dimension\ContentDimensionValueSpecializationDepth($coordinates[2])),
                'dimensionB' => new Dimension\ContentDimensionValue($coordinates[1], new Dimension\ContentDimensionValueSpecializationDepth($coordinates[3]))
            ]);
            $expectedSubgraphs[$subgraph->getIdentityHash()] = $subgraph;
        }

        $this->assertEquals(
            $expectedSubgraphs,
            $this->subject->getSubgraphs()
        );

        foreach ($expectedSubgraphs as $subgraphIdentifier => $subgraph) {
            $this->assertEquals($subgraph, $this->subject->getSubgraphByDimensionSpacePointHash($subgraphIdentifier));
        }
    }

    /**
     * @test
     */
    public function initializeSubgraphsCorrectlyInitializesSingularSubgraphWithNoDimensionsGiven()
    {
        $this->setUpNullExample();
        $this->subject->_call('initializeSubgraphs');

        $singularSubgraph = new DimensionSpace\ContentSubgraph([]);
        $this->assertEquals(
            [$singularSubgraph->getIdentityHash() => $singularSubgraph],
            $this->subject->getSubgraphs()
        );
    }

    /**
     * @test
     */
    public function getSubgraphByDimensionSpacePointReturnsNullForPointOutsideTheAllowedDimensionSpace()
    {
        $this->setUpVariationExample();

        $this->assertSame(
            null,
            $this->subject->getSubgraphByDimensionSpacePoint(new Domain\ValueObject\DimensionSpacePoint([
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
                             ['value1', 'value1.1'],
                             ['value1', 'value1.2'],
                             ['value1', 'value1.1.1'],
                             ['value1.1', 'value1'],
                             ['value1.1', 'value1.1'],
                             ['value1.1', 'value1.2'],
                             ['value1.1', 'value1.1.1'],
                             ['value1.2', 'value1'],
                             ['value1.2', 'value1.1'],
                             ['value1.2', 'value1.2'],
                             ['value1.2', 'value1.1.1'],
                             ['value1.1.1', 'value1'],
                             ['value1.1.1', 'value1.1'],
                             ['value1.1.1', 'value1.2'],
                             ['value1.1.1', 'value1.1.1']
                         ]
                     ],
                     [
                         ['value1', 'value1.1'],
                         [
                             ['value1', 'value1.1.1'],
                             ['value1.1', 'value1.1'],
                             ['value1.1', 'value1.1.1'],
                             ['value1.2', 'value1.1'],
                             ['value1.2', 'value1.1.1'],
                             ['value1.1.1', 'value1.1'],
                             ['value1.1.1', 'value1.1.1']
                         ],
                     ],
                     [
                         ['value1', 'value1.2'],
                         [
                             ['value1.1', 'value1.2'],
                             ['value1.2', 'value1.2'],
                             ['value1.1.1', 'value1.2']
                         ],
                     ],
                     [
                         ['value1', 'value1.1.1'],
                         [
                             ['value1.1', 'value1.1.1'],
                             ['value1.2', 'value1.1.1'],
                             ['value1.1.1', 'value1.1.1']
                         ],
                     ],
                     [
                         ['value1.1', 'value1'],
                         [
                             ['value1.1', 'value1.1'],
                             ['value1.1', 'value1.2'],
                             ['value1.1', 'value1.1.1'],
                             ['value1.1.1', 'value1'],
                             ['value1.1.1', 'value1.1'],
                             ['value1.1.1', 'value1.2'],
                             ['value1.1.1', 'value1.1.1']
                         ],
                     ],
                     [
                         ['value1.1', 'value1.1'],
                         [
                             ['value1.1', 'value1.1.1'],
                             ['value1.1.1', 'value1.1'],
                             ['value1.1.1', 'value1.1.1']
                         ]
                     ],
                     [
                         ['value1.1', 'value1.2'],
                         [
                             ['value1.1.1', 'value1.2']
                         ]
                     ],
                     [
                         ['value1.1', 'value1.1.1'],
                         [
                             ['value1.1.1', 'value1.1.1']
                         ]
                     ],
                     [
                         ['value1.2', 'value1'],
                         [
                             ['value1.2', 'value1.1'],
                             ['value1.2', 'value1.2'],
                             ['value1.2', 'value1.1.1']
                         ]
                     ],
                     [
                         ['value1.2', 'value1.1'],
                         [
                             ['value1.2', 'value1.1.1']
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
                             ['value1.1.1', 'value1.1'],
                             ['value1.1.1', 'value1.2'],
                             ['value1.1.1', 'value1.1.1']
                         ]
                     ],
                     [
                         ['value1.1.1', 'value1.1'],
                         [
                             ['value1.1.1', 'value1.1.1']
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
            $specializationCoordinateSet = $variationData[1];

            $expectedSpecializations = [];
            foreach ($specializationCoordinateSet as $specializationCoordinates) {
                $specialization = $this->subject->getSubgraphByDimensionSpacePoint(new Domain\ValueObject\DimensionSpacePoint([
                    'dimensionA' => $specializationCoordinates[0],
                    'dimensionB' => $specializationCoordinates[1]
                ]));
                $expectedSpecializations[$specialization->getIdentityHash()] = $specialization;
            }

            $actualSpecializations = $this->subject->getSpecializations($this->subject->getSubgraphByDimensionSpacePoint(new Domain\ValueObject\DimensionSpacePoint([
                'dimensionA' => $generalizationCoordinates[0],
                'dimensionB' => $generalizationCoordinates[1]
            ])));

            $this->assertEquals(
                $expectedSpecializations,
                $actualSpecializations
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
                             ['value1', 'value1']
                         ],
                     ],
                     [
                         ['value1', 'value1.2'],
                         [
                             ['value1', 'value1']
                         ],
                     ],
                     [
                         ['value1', 'value1.1.1'],
                         [
                             ['value1', 'value1.1'],
                             ['value1', 'value1']
                         ],
                     ],
                     [
                         ['value1.1', 'value1'],
                         [
                             ['value1', 'value1']
                         ],
                     ],
                     [
                         ['value1.1', 'value1.1'],
                         [
                             ['value1.1', 'value1'],
                             ['value1', 'value1.1'],
                             ['value1', 'value1']
                         ]
                     ],
                     [
                         ['value1.1', 'value1.2'],
                         [
                             ['value1.1', 'value1'],
                             ['value1', 'value1.2'],
                             ['value1', 'value1']
                         ]
                     ],
                     [
                         ['value1.1', 'value1.1.1'],
                         [
                             ['value1.1', 'value1.1'],
                             ['value1.1', 'value1'],
                             ['value1', 'value1.1.1'],
                             ['value1', 'value1.1'],
                             ['value1', 'value1']
                         ]
                     ],
                     [
                         ['value1.2', 'value1'],
                         [
                             ['value1', 'value1']
                         ]
                     ],
                     [
                         ['value1.2', 'value1.1'],
                         [
                             ['value1.2', 'value1'],
                             ['value1', 'value1.1'],
                             ['value1', 'value1']
                         ]
                     ],
                     [
                         ['value1.2', 'value1.2'],
                         [
                             ['value1.2', 'value1'],
                             ['value1', 'value1.2'],
                             ['value1', 'value1']
                         ]
                     ],
                     [
                         ['value1.2', 'value1.1.1'],
                         [
                             ['value1.2', 'value1.1'],
                             ['value1.2', 'value1'],
                             ['value1', 'value1.1.1'],
                             ['value1', 'value1.1'],
                             ['value1', 'value1']
                         ]
                     ],
                     [
                         ['value1.1.1', 'value1'],
                         [
                             ['value1.1', 'value1'],
                             ['value1', 'value1'],
                         ]
                     ],
                     [
                         ['value1.1.1', 'value1.1'],
                         [
                             ['value1.1.1', 'value1'],
                             ['value1.1', 'value1.1'],
                             ['value1.1', 'value1'],
                             ['value1', 'value1.1'],
                             ['value1', 'value1']
                         ]
                     ],
                     [
                         ['value1.1.1', 'value1.2'],
                         [
                             ['value1.1.1', 'value1'],
                             ['value1.1', 'value1.2'],
                             ['value1.1', 'value1'],
                             ['value1', 'value1.2'],
                             ['value1', 'value1']
                         ]
                     ],
                     [
                         ['value1.1.1', 'value1.1.1'],
                         [
                             ['value1.1.1', 'value1.1'],
                             ['value1.1.1', 'value1'],
                             ['value1.1', 'value1.1.1'],
                             ['value1.1', 'value1.1'],
                             ['value1.1', 'value1'],
                             ['value1', 'value1.1.1'],
                             ['value1', 'value1.1'],
                             ['value1', 'value1']
                         ]
                     ]
                 ] as $variationData) {
            $specializationCoordinates = $variationData[0];
            $generalizationCoordinateSet = $variationData[1];

            $expectedGeneralizations = [];
            foreach ($generalizationCoordinateSet as $generalizationCoordinates) {
                $generalization = $this->subject->getSubgraphByDimensionSpacePoint(new Domain\ValueObject\DimensionSpacePoint([
                    'dimensionA' => $generalizationCoordinates[0],
                    'dimensionB' => $generalizationCoordinates[1]
                ]));
                $expectedGeneralizations[$generalization->getIdentityHash()] = $generalization;
            }

            $actualGeneralizations = $this->subject->getGeneralizations($this->subject->getSubgraphByDimensionSpacePoint(new Domain\ValueObject\DimensionSpacePoint([
                'dimensionA' => $specializationCoordinates[0],
                'dimensionB' => $specializationCoordinates[1]
            ])));

            $this->assertEquals(
                $expectedGeneralizations,
                $actualGeneralizations
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

            $this->assertSame(
                ($primaryGeneralizationCoordinates
                    ? $this->subject->getSubgraphByDimensionSpacePoint(new Domain\ValueObject\DimensionSpacePoint([
                        'dimensionA' => $primaryGeneralizationCoordinates[0],
                        'dimensionB' => $primaryGeneralizationCoordinates[1]
                    ]))
                    : null),
                $this->subject->getPrimaryGeneralization(
                    $this->subject->getSubgraphByDimensionSpacePoint(new Domain\ValueObject\DimensionSpacePoint([
                        'dimensionA' => $specializationCoordinates[0],
                        'dimensionB' => $specializationCoordinates[1]
                    ]))
                )
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
