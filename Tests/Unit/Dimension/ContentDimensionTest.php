<?php

namespace Neos\ContentRepository\DimensionSpace\Tests\Unit\Dimension;

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
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test cases for content dimensions
 */
class ContentDimensionTest extends UnitTestCase
{
    /**
     * @var Dimension\ContentDimension
     */
    protected $subject;

    /**
     * @var array|Dimension\ContentDimensionValue[]
     */
    protected $values;

    public function setUp()
    {
        parent::setUp();

        $dimensionIdentifier = new Dimension\ContentDimensionIdentifier('market');
        $this->values['world'] = new Dimension\ContentDimensionValue('world', new Dimension\ContentDimensionValueSpecializationDepth(0), []);
        $this->values['eu'] = new Dimension\ContentDimensionValue('eu', new Dimension\ContentDimensionValueSpecializationDepth(1), []);
        $euEdge = new Dimension\ContentDimensionValueVariationEdge($this->values['eu'], $this->values['world']);
        $this->values['de'] = new Dimension\ContentDimensionValue('de', new Dimension\ContentDimensionValueSpecializationDepth(2), []);
        $deEdge = new Dimension\ContentDimensionValueVariationEdge($this->values['de'], $this->values['eu']);
        $this->values['us'] = new Dimension\ContentDimensionValue('us', new Dimension\ContentDimensionValueSpecializationDepth(1), []);
        $usEdge = new Dimension\ContentDimensionValueVariationEdge($this->values['us'], $this->values['world']);

        $this->subject = new Dimension\ContentDimension($dimensionIdentifier, $this->values, $this->values['world'], [$euEdge, $deEdge, $usEdge]);
    }

    /**
     * @expectedException \Neos\ContentRepository\DimensionSpace\Dimension\Exception\ContentDimensionValuesAreMissing
     * @test
     */
    public function initializationThrowsExceptionWithoutAnyDimensionValuesGiven()
    {
        $subject = new Dimension\ContentDimension(
            new Dimension\ContentDimensionIdentifier('dimension'),
            [],
            new Dimension\ContentDimensionValue('default')
        );
    }

    /**
     * @test
     */
    public function getValueReturnsValueForMatchingIdentifier()
    {
        $this->assertSame(
            $this->values['world'],
            $this->subject->getValue('world')
        );
    }

    /**
     * @test
     */
    public function getValueReturnsNullForNonMatchingIdentifier()
    {
        $this->assertSame(
            null,
            $this->subject->getValue('fr')
        );
    }

    /**
     * @test
     */
    public function getRootValuesReturnsAllAndOnlyRootValues()
    {
        $this->assertSame(
            ['world' => $this->values['world']],
            $this->subject->getRootValues()
        );
    }

    /**
     * @test
     */
    public function getGeneralizationCorrectlyDeterminesGeneralization()
    {
        $this->assertSame(
            $this->values['world'],
            $this->subject->getGeneralization($this->values['eu'])
        );
    }

    /**
     * @test
     */
    public function getSpecializationsCorrectlyDeterminesSpecializations()
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
     * @test
     * @throws Dimension\Exception\GeneralizationIsInvalid
     */
    public function calculateSpecializationDepthReturnsZeroForValueItself()
    {
        $this->assertEquals(
            new Dimension\ContentDimensionValueSpecializationDepth(0),
            $this->subject->calculateSpecializationDepth($this->values['world'], $this->values['world'])
        );
    }

    /**
     * @test
     * @throws Dimension\Exception\GeneralizationIsInvalid
     */
    public function calculateSpecializationDepthCalculatesCorrectDepthForSpecialization()
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

    /**
     * @test
     * @expectedException \Neos\ContentRepository\DimensionSpace\Dimension\Exception\GeneralizationIsInvalid
     * @throws Dimension\Exception\GeneralizationIsInvalid
     */
    public function calculateSpecializationDepthThrowsExceptionForDisconnectedValues()
    {
        $this->subject->calculateSpecializationDepth($this->values['us'], $this->values['eu']);
    }

    /**
     * @test
     */
    public function getMaximumDepthCorrectlyDeterminesMaximumDepth()
    {
        $this->assertEquals(
            new Dimension\ContentDimensionValueSpecializationDepth(2),
            $this->subject->getMaximumDepth()
        );
    }
}
