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

declare(strict_types=1);

namespace Neos\ContentRepository\Tests\Unit\Dimension;

use Neos\ContentRepository\Dimension;
use Neos\ContentRepository\Dimension\Exception\ContentDimensionValuesAreInvalid;
use Neos\ContentRepository\Dimension\Exception\GeneralizationIsInvalid;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test cases for content dimensions
 */
class ContentDimensionTest extends UnitTestCase
{
    protected ?Dimension\ContentDimension $subject;

    protected ?Dimension\ContentDimensionValues $values;

    protected function setUp(): void
    {
        parent::setUp();

        $dimensionIdentifier = new Dimension\ContentDimensionIdentifier('market');
        $values['world'] = new Dimension\ContentDimensionValue(
            'world',
            new Dimension\ContentDimensionValueSpecializationDepth(0),
            Dimension\ContentDimensionConstraintSet::createEmpty()
        );
        $values['eu'] = new Dimension\ContentDimensionValue(
            'eu',
            new Dimension\ContentDimensionValueSpecializationDepth(1),
            Dimension\ContentDimensionConstraintSet::createEmpty()
        );
        $euEdge = new Dimension\ContentDimensionValueVariationEdge($values['eu'], $values['world']);
        $values['de'] = new Dimension\ContentDimensionValue(
            'de',
            new Dimension\ContentDimensionValueSpecializationDepth(2),
            Dimension\ContentDimensionConstraintSet::createEmpty()
        );
        $deEdge = new Dimension\ContentDimensionValueVariationEdge($values['de'], $values['eu']);
        $values['us'] = new Dimension\ContentDimensionValue(
            'us',
            new Dimension\ContentDimensionValueSpecializationDepth(1),
            Dimension\ContentDimensionConstraintSet::createEmpty()
        );
        $usEdge = new Dimension\ContentDimensionValueVariationEdge($values['us'], $values['world']);
        $this->values = new Dimension\ContentDimensionValues($values);

        $this->subject = new Dimension\ContentDimension(
            $dimensionIdentifier,
            $this->values,
            new Dimension\ContentDimensionValueVariationEdges([$euEdge, $deEdge, $usEdge])
        );
    }

    public function testInitializationThrowsExceptionWithoutAnyDimensionValuesGiven()
    {
        $this->expectException(ContentDimensionValuesAreInvalid::class);
        new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('dimension'),
            new Dimension\ContentDimensionValues([]),
            Dimension\ContentDimensionValueVariationEdges::createEmpty()
        );
    }

    public function testGetValueReturnsValueForMatchingIdentifier()
    {
        $this->assertSame(
            $this->values->getValue('world'),
            $this->subject->getValue('world')
        );
    }

    public function testGetValueReturnsNullForNonMatchingIdentifier()
    {
        $this->assertSame(
            null,
            $this->subject->getValue('fr')
        );
    }

    public function testGetRootValuesReturnsAllAndOnlyRootValues()
    {
        $this->assertSame(
            ['world' => $this->values->getValue('world')],
            $this->subject->getRootValues()
        );
    }

    public function testGetGeneralizationCorrectlyDeterminesGeneralization()
    {
        $this->assertSame(
            $this->values->getValue('world'),
            $this->subject->getGeneralization($this->values->getValue('eu'))
        );
    }

    public function testGetSpecializationsCorrectlyDeterminesSpecializations()
    {
        $this->assertSame(
            [
                'eu' => $this->values->getValue('eu'),
                'us' => $this->values->getValue('us')
            ],
            $this->subject->getSpecializations($this->values->getValue('world'))
        );
    }

    /**
     * @throws GeneralizationIsInvalid
     */
    public function testCalculateSpecializationDepthReturnsZeroForValueItself()
    {
        $this->assertEquals(
            new Dimension\ContentDimensionValueSpecializationDepth(0),
            $this->subject->calculateSpecializationDepth(
                $this->values->getValue('world'),
                $this->values->getValue('world')
            )
        );
    }

    /**
     * @throws GeneralizationIsInvalid
     */
    public function testCalculateSpecializationDepthCalculatesCorrectDepthForSpecialization()
    {
        $this->assertEquals(
            new Dimension\ContentDimensionValueSpecializationDepth(1),
            $this->subject->calculateSpecializationDepth(
                $this->values->getValue('eu'),
                $this->values->getValue('world')
            )
        );
        $this->assertEquals(
            new Dimension\ContentDimensionValueSpecializationDepth(2),
            $this->subject->calculateSpecializationDepth(
                $this->values->getValue('de'),
                $this->values->getValue('world')
            )
        );
    }

    public function testCalculateSpecializationDepthThrowsExceptionForDisconnectedValues()
    {
        $this->expectException(GeneralizationIsInvalid::class);
        $this->subject->calculateSpecializationDepth(
            $this->values->getValue('us'),
            $this->values->getValue('eu')
        );
    }

    public function testGetMaximumDepthCorrectlyDeterminesMaximumDepth()
    {
        $this->assertEquals(
            new Dimension\ContentDimensionValueSpecializationDepth(2),
            $this->subject->getMaximumDepth()
        );
    }
}
