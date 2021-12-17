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

namespace Neos\ContentRepository\DimensionSpace\Tests\Unit\Dimension;

use Neos\ContentRepository\DimensionSpace\Dimension;
use Neos\ContentRepository\DimensionSpace\Dimension\Exception\ContentDimensionValuesAreMissing;
use Neos\ContentRepository\DimensionSpace\Dimension\Exception\GeneralizationIsInvalid;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test cases for content dimensions
 */
class ContentDimensionTest extends UnitTestCase
{
    protected ?Dimension\ContentDimension $subject;

    /**
     * @var array<string,Dimension\ContentDimensionValue>
     */
    protected array $values;

    protected function setUp(): void
    {
        parent::setUp();

        $dimensionIdentifier = new Dimension\ContentDimensionIdentifier('market');
        $this->values['world'] = new Dimension\ContentDimensionValue(
            'world',
            new Dimension\ContentDimensionValueSpecializationDepth(0),
            Dimension\ContentDimensionConstraintSet::createEmpty()
        );
        $this->values['eu'] = new Dimension\ContentDimensionValue(
            'eu',
            new Dimension\ContentDimensionValueSpecializationDepth(1),
            Dimension\ContentDimensionConstraintSet::createEmpty()
        );
        $euEdge = new Dimension\ContentDimensionValueVariationEdge($this->values['eu'], $this->values['world']);
        $this->values['de'] = new Dimension\ContentDimensionValue(
            'de',
            new Dimension\ContentDimensionValueSpecializationDepth(2),
            Dimension\ContentDimensionConstraintSet::createEmpty()
        );
        $deEdge = new Dimension\ContentDimensionValueVariationEdge($this->values['de'], $this->values['eu']);
        $this->values['us'] = new Dimension\ContentDimensionValue(
            'us',
            new Dimension\ContentDimensionValueSpecializationDepth(1),
            Dimension\ContentDimensionConstraintSet::createEmpty()
        );
        $usEdge = new Dimension\ContentDimensionValueVariationEdge($this->values['us'], $this->values['world']);

        $this->subject = new Dimension\ContentDimension(
            $dimensionIdentifier,
            $this->values,
            $this->values['world'],
            new Dimension\ContentDimensionValueVariationEdges([$euEdge, $deEdge, $usEdge])
        );
    }

    public function testInitializationThrowsExceptionWithoutAnyDimensionValuesGiven()
    {
        $this->expectException(ContentDimensionValuesAreMissing::class);
        new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('dimension'),
            [],
            new Dimension\ContentDimensionValue('default'),
            Dimension\ContentDimensionValueVariationEdges::createEmpty()
        );
    }

    public function testGetValueReturnsValueForMatchingIdentifier()
    {
        $this->assertSame(
            $this->values['world'],
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
            ['world' => $this->values['world']],
            $this->subject->getRootValues()
        );
    }

    public function testGetGeneralizationCorrectlyDeterminesGeneralization()
    {
        $this->assertSame(
            $this->values['world'],
            $this->subject->getGeneralization($this->values['eu'])
        );
    }

    public function testGetSpecializationsCorrectlyDeterminesSpecializations()
    {
        $this->assertSame(
            [
                'eu' => $this->values['eu'],
                'us' => $this->values['us']
            ],
            $this->subject->getSpecializations($this->values['world'])
        );
    }

    /**
     * @throws GeneralizationIsInvalid
     */
    public function testCalculateSpecializationDepthReturnsZeroForValueItself()
    {
        $this->assertEquals(
            new Dimension\ContentDimensionValueSpecializationDepth(0),
            $this->subject->calculateSpecializationDepth($this->values['world'], $this->values['world'])
        );
    }

    /**
     * @throws GeneralizationIsInvalid
     */
    public function testCalculateSpecializationDepthCalculatesCorrectDepthForSpecialization()
    {
        $this->assertEquals(
            new Dimension\ContentDimensionValueSpecializationDepth(1),
            $this->subject->calculateSpecializationDepth($this->values['eu'], $this->values['world'])
        );
        $this->assertEquals(
            new Dimension\ContentDimensionValueSpecializationDepth(2),
            $this->subject->calculateSpecializationDepth($this->values['de'], $this->values['world'])
        );
    }

    public function testCalculateSpecializationDepthThrowsExceptionForDisconnectedValues()
    {
        $this->expectException(GeneralizationIsInvalid::class);
        $this->subject->calculateSpecializationDepth($this->values['us'], $this->values['eu']);
    }

    public function testGetMaximumDepthCorrectlyDeterminesMaximumDepth()
    {
        $this->assertEquals(
            new Dimension\ContentDimensionValueSpecializationDepth(2),
            $this->subject->maximumDepth
        );
    }
}
