<?php

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Tests\Unit;

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\Dimension\ContentDimensionConstraintSet;
use Neos\ContentRepository\Core\Dimension\ContentDimensionId;
use Neos\ContentRepository\Core\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Core\Dimension\ContentDimensionValue;
use Neos\ContentRepository\Core\Dimension\ContentDimensionValueSpecializationDepth;
use Neos\ContentRepository\Core\Tests\Unit\Dimension\ConfigurationBasedContentDimensionSourceTest;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\GherkinTableNodeBasedContentDimensionSource;
use PHPUnit\Framework\TestCase;

class GherkinTableNodeBasedContentDimensionSourceTest extends TestCase
{
    protected ContentDimensionSourceInterface $subject;

    protected function setUp(): void
    {
        parent::setUp();
        /**
         * This test is based on the test {@see ConfigurationBasedContentDimensionSourceTest} and adjusted as we dont use constraints.
         * The below used table syntax in behat is equivalent to the array representation of the configuration based source:
         *
         *     dimensionA:
         *       values:
         *         valueA1:
         *           specializations:
         *             valueA1.1: []
         *         valueA2: []
         *     dimensionB:
         *       values:
         *         valueB1: []
         *         valueB2: []
         *         valueB3: []
         */
        // parsed gherkin table shape:
        $table = [
            ['Identifier', 'Values'                   , 'Generalizations'   ],
            ['dimensionA', 'valueA1,valueA1.1,valueA2', 'valueA1.1->valueA1'],
            ['dimensionB', 'valueB1,valueB2,valueB3'  , ''                  ],
        ];
        $this->subject = GherkinTableNodeBasedContentDimensionSource::fromGherkinTableNode(new TableNode($table));
    }

    public function testEmptyDimensionConfigurationIsCorrectlyInitialized()
    {
        $subject = GherkinTableNodeBasedContentDimensionSource::createEmpty();

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
        $dimensionA = $this->subject->getDimension(new ContentDimensionId('dimensionA'));
        $dimensionB = $this->subject->getDimension(new ContentDimensionId('dimensionB'));

        $this->assertEquals(
            new ContentDimensionValue(
                'valueA1',
                new ContentDimensionValueSpecializationDepth(0),
                ContentDimensionConstraintSet::createEmpty(),
                []
            ),
            $dimensionA->getValue('valueA1')
        );
        $this->assertEquals(
            new ContentDimensionValue(
                'valueA1.1',
                new ContentDimensionValueSpecializationDepth(1),
                ContentDimensionConstraintSet::createEmpty()
            ),
            $dimensionA->getValue('valueA1.1')
        );
        $this->assertEquals(
            new ContentDimensionValue(
                'valueA2',
                new ContentDimensionValueSpecializationDepth(0),
                ContentDimensionConstraintSet::createEmpty()
            ),
            $dimensionA->getValue('valueA2')
        );

        $this->assertEquals(
            new ContentDimensionValue(
                'valueB1',
                new ContentDimensionValueSpecializationDepth(0)
            ),
            $dimensionB->getValue('valueB1')
        );
        $this->assertEquals(
            new ContentDimensionValue(
                'valueB2',
                new ContentDimensionValueSpecializationDepth(0)
            ),
            $dimensionB->getValue('valueB2')
        );
        $this->assertEquals(
            new ContentDimensionValue(
                'valueB3',
                new ContentDimensionValueSpecializationDepth(0)
            ),
            $dimensionB->getValue('valueB3')
        );
    }

    public function testSpecializationsAreCorrectlyInitialized()
    {
        $dimensionA = $this->subject->getDimension(new ContentDimensionId('dimensionA'));
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

        $dimensionB = $this->subject->getDimension(new ContentDimensionId('dimensionB'));
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
        $dimensionA = $this->subject->getDimension(new ContentDimensionId('dimensionA'));
        $dimensionB = $this->subject->getDimension(new ContentDimensionId('dimensionB'));

        $this->assertEquals(
            new ContentDimensionValueSpecializationDepth(1),
            $dimensionA->getMaximumDepth()
        );
        $this->assertEquals(
            new ContentDimensionValueSpecializationDepth(0),
            $dimensionB->getMaximumDepth()
        );
    }

    public function testRestrictionsAreCorrectlyInitialized()
    {
        $dimensionA = $this->subject->getDimension(new ContentDimensionId('dimensionA'));
        $dimensionB = $this->subject->getDimension(new ContentDimensionId('dimensionB'));

        $valueA1 = $dimensionA->getValue('valueA1');
        $this->assertEquals(
            ContentDimensionConstraintSet::createEmpty(),
            $valueA1->constraints
        );

        $valueA11 = $dimensionA->getValue('valueA1.1');
        $this->assertEquals(
            ContentDimensionConstraintSet::createEmpty(),
            $valueA11->constraints
        );

        $valueA2 = $dimensionA->getValue('valueA2');
        $this->assertEquals(
            ContentDimensionConstraintSet::createEmpty(),
            $valueA2->constraints
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
}
