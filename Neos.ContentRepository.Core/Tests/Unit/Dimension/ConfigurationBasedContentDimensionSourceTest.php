<?php

/*s
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\Dimension;

use Neos\ContentRepository\Core\Dimension;
use Neos\ContentRepository\Core\Dimension\ContentDimensionConstraintSet;
use PHPUnit\Framework\TestCase;

/**
 * Unit test cases for the ConfigurationBasedContentDimensionSource
 */
class ConfigurationBasedContentDimensionSourceTest extends TestCase
{
    protected ?Dimension\ConfigurationBasedContentDimensionSource $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new Dimension\ConfigurationBasedContentDimensionSource([
            'dimensionA' => [
                'values' => [
                    'valueA1' => [
                        'constraints' => [
                            'dimensionB' => [
                                '*' => false,
                                'valueB1' => true,
                                'valueB2' => false
                            ]
                        ],
                        'specializations' => [
                            'valueA1.1' => [
                                'constraints' => []
                            ]
                        ],
                        'dimensionValueConfiguration' => [
                            'key' => 'value'
                        ]
                    ],
                    'valueA2' => [
                        'constraints' => [
                            'dimensionB' => [
                                '*' => true,
                                'valueB1' => false,
                                'valueB2' => true
                            ]
                        ],
                    ]
                ],
                'dimensionConfiguration' => [
                    'anotherKey' => 'anotherValue'
                ]
            ],
            'dimensionB' => [
                'values' => [
                    'valueB1' => [
                    ],
                    'valueB2' => [
                    ],
                    'valueB3' => [
                    ]
                ],
            ]
        ]);
    }

    /**
     * @throws Dimension\Exception\ContentDimensionDefaultValueIsMissing
     */
    public function testEmptyDimensionConfigurationIsCorrectlyInitialized()
    {
        $subject = new Dimension\ConfigurationBasedContentDimensionSource([]);

        $this->assertSame([], $subject->getContentDimensionsOrderedByPriority());
    }

    public function testDimensionsAreInitializedInCorrectOrder()
    {
        $dimensions = $this->subject->getContentDimensionsOrderedByPriority();
        $dimensionKeys = array_keys($dimensions);

        $this->assertSame('dimensionA', $dimensionKeys[0]);
        $this->assertSame('dimensionB', $dimensionKeys[1]);
    }

    public function testDimensionValuesAreCorrectlyInitialized()
    {
        $dimensionA = $this->subject->getDimension(new Dimension\ContentDimensionId('dimensionA'));
        $dimensionB = $this->subject->getDimension(new Dimension\ContentDimensionId('dimensionB'));

        $this->assertEquals(
            new Dimension\ContentDimensionValue(
                'valueA1',
                new Dimension\ContentDimensionValueSpecializationDepth(0),
                new ContentDimensionConstraintSet([
                    'dimensionB' => new Dimension\ContentDimensionConstraints(
                        false,
                        [
                            'valueB1' => true,
                            'valueB2' => false
                        ]
                    )
                ]),
                [
                    'dimensionValueConfiguration' => [
                        'key' => 'value'
                    ]
                ]
            ),
            $dimensionA->getValue('valueA1')
        );
        $this->assertEquals(
            new Dimension\ContentDimensionValue(
                'valueA1.1',
                new Dimension\ContentDimensionValueSpecializationDepth(1),
                ContentDimensionConstraintSet::createEmpty()
            ),
            $dimensionA->getValue('valueA1.1')
        );
        $this->assertEquals(
            new Dimension\ContentDimensionValue(
                'valueA2',
                new Dimension\ContentDimensionValueSpecializationDepth(0),
                new ContentDimensionConstraintSet([
                    'dimensionB' => new Dimension\ContentDimensionConstraints(
                        true,
                        [
                            'valueB1' => false,
                            'valueB2' => true
                        ]
                    )
                ])
            ),
            $dimensionA->getValue('valueA2')
        );

        $this->assertEquals(
            new Dimension\ContentDimensionValue(
                'valueB1',
                new Dimension\ContentDimensionValueSpecializationDepth(0)
            ),
            $dimensionB->getValue('valueB1')
        );
        $this->assertEquals(
            new Dimension\ContentDimensionValue(
                'valueB2',
                new Dimension\ContentDimensionValueSpecializationDepth(0)
            ),
            $dimensionB->getValue('valueB2')
        );
        $this->assertEquals(
            new Dimension\ContentDimensionValue(
                'valueB3',
                new Dimension\ContentDimensionValueSpecializationDepth(0)
            ),
            $dimensionB->getValue('valueB3')
        );
    }

    public function testSpecializationsAreCorrectlyInitialized()
    {
        $dimensionA = $this->subject->getDimension(new Dimension\ContentDimensionId('dimensionA'));
        $this->assertSame(
            [
                'valueA1.1' => $dimensionA->getValue('valueA1.1')
            ],
            $dimensionA->getSpecializations($dimensionA->getValue('valueA1'))
        );
        $this->assertSame(
            null,
            $dimensionA->getGeneralization($dimensionA->getValue('valueA1'))
        );

        $this->assertSame(
            [],
            $dimensionA->getSpecializations($dimensionA->getValue('valueA1.1'))
        );
        $this->assertSame(
            $dimensionA->getValue('valueA1'),
            $dimensionA->getGeneralization($dimensionA->getValue('valueA1.1'))
        );

        $this->assertSame(
            [],
            $dimensionA->getSpecializations($dimensionA->getValue('valueA2'))
        );
        $this->assertSame(
            null,
            $dimensionA->getGeneralization($dimensionA->getValue('valueA2'))
        );

        $dimensionB = $this->subject->getDimension(new Dimension\ContentDimensionId('dimensionB'));
        $this->assertSame(
            [],
            $dimensionB->getSpecializations($dimensionB->getValue('valueB1'))
        );
        $this->assertSame(
            null,
            $dimensionA->getGeneralization($dimensionB->getValue('valueB1'))
        );

        $this->assertSame(
            [],
            $dimensionB->getSpecializations($dimensionB->getValue('valueB2'))
        );
        $this->assertSame(
            null,
            $dimensionA->getGeneralization($dimensionB->getValue('valueB2'))
        );

        $this->assertSame(
            [],
            $dimensionB->getSpecializations($dimensionB->getValue('valueB3'))
        );
        $this->assertSame(
            null,
            $dimensionA->getGeneralization($dimensionB->getValue('valueB3'))
        );
    }

    public function testMaximumDepthIsCorrectlyInitialized()
    {
        $dimensionA = $this->subject->getDimension(new Dimension\ContentDimensionId('dimensionA'));
        $dimensionB = $this->subject->getDimension(new Dimension\ContentDimensionId('dimensionB'));

        $this->assertEquals(
            new Dimension\ContentDimensionValueSpecializationDepth(1),
            $dimensionA->getMaximumDepth()
        );
        $this->assertEquals(
            new Dimension\ContentDimensionValueSpecializationDepth(0),
            $dimensionB->getMaximumDepth()
        );
    }

    public function testRestrictionsAreCorrectlyInitialized()
    {
        $dimensionA = $this->subject->getDimension(new Dimension\ContentDimensionId('dimensionA'));
        $dimensionB = $this->subject->getDimension(new Dimension\ContentDimensionId('dimensionB'));

        $valueA1 = $dimensionA->getValue('valueA1');
        $this->assertSame(
            false,
            $valueA1->getConstraints($dimensionB->id)->isWildcardAllowed
        );
        $this->assertEquals(
            [
                'valueB1' => true,
                'valueB2' => false
            ],
            $valueA1->getConstraints($dimensionB->id)->identifierRestrictions
        );

        $valueA11 = $dimensionA->getValue('valueA1.1');
        $this->assertEquals(
            ContentDimensionConstraintSet::createEmpty(),
            $valueA11->constraints
        );

        $valueA2 = $dimensionA->getValue('valueA2');
        $this->assertSame(
            true,
            $valueA2->getConstraints($dimensionB->id)->isWildcardAllowed
        );
        $this->assertEquals(
            [
                'valueB1' => false,
                'valueB2' => true
            ],
            $valueA2->getConstraints($dimensionB->id)->identifierRestrictions
        );

        $valueB1 = $dimensionB->getValue('valueB1');
        $this->assertEquals(
            ContentDimensionConstraintSet::createEmpty(),
            $valueB1->constraints
        );

        $valueB2 = $dimensionB->getValue('valueB2');
        $this->assertEquals(
            ContentDimensionConstraintSet::createEmpty(),
            $valueB2->constraints
        );

        $valueB3 = $dimensionB->getValue('valueB3');
        $this->assertEquals(
            ContentDimensionConstraintSet::createEmpty(),
            $valueB3->constraints
        );
    }

    public function testDimensionConfigurationValuesAreCorrectlyInitialized()
    {
        $dimensionA = $this->subject->getDimension(new Dimension\ContentDimensionId('dimensionA'));

        $this->assertSame('anotherValue', $dimensionA->getConfigurationValue('dimensionConfiguration.anotherKey'));
    }

    public function testDimensionValueConfigurationValuesAreCorrectlyInitialized()
    {
        $dimensionA = $this->subject->getDimension(new Dimension\ContentDimensionId('dimensionA'));
        $dimensionValueA1 = $dimensionA->getValue('valueA1');

        $this->assertSame('value', $dimensionValueA1->getConfigurationValue('dimensionValueConfiguration.key'));
    }
}
