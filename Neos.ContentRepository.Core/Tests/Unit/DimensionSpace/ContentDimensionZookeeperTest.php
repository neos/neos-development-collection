<?php

namespace Neos\ContentRepository\Core\Tests\Unit\DimensionSpace;

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Core\DimensionSpace;
use Neos\ContentRepository\Core\Dimension;
use PHPUnit\Framework\TestCase;

// NOTE: not sure why this is needed
require_once(__DIR__ . '/Fixtures/ExampleDimensionSource.php');

/**
 * Unit test cases for the ContentDimensionZookeeper
 */
class ContentDimensionZookeeperTest extends TestCase
{
    protected ?DimensionSpace\ContentDimensionZookeeper $subject;

    protected ?Fixtures\ExampleDimensionSource $dimensionSource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dimensionSource = new Fixtures\ExampleDimensionSource();
        $this->subject = new DimensionSpace\ContentDimensionZookeeper($this->dimensionSource);
    }

    /**
     * @test
     */
    public function getAllowedCombinationsCorrectlyDeterminesAllowedCombinations()
    {
        $allowedCombinations = $this->subject->getAllowedCombinations();
        $marketIdentifier = new Dimension\ContentDimensionId('market');
        $languageIdentifier = new Dimension\ContentDimensionId('language');

        $this->assertSame(6, count($allowedCombinations));
        $this->assertContains([
            'market' => $this->dimensionSource->getDimension($marketIdentifier)->getValue('CH'),
            'language' => $this->dimensionSource->getDimension($languageIdentifier)->getValue('de')
        ], $allowedCombinations);
        $this->assertContains([
            'market' => $this->dimensionSource->getDimension($marketIdentifier)->getValue('CH'),
            'language' => $this->dimensionSource->getDimension($languageIdentifier)->getValue('fr')
        ], $allowedCombinations);
        $this->assertContains([
            'market' => $this->dimensionSource->getDimension($marketIdentifier)->getValue('CH'),
            'language' => $this->dimensionSource->getDimension($languageIdentifier)->getValue('it')
        ], $allowedCombinations);
        $this->assertContains([
            'market' => $this->dimensionSource->getDimension($marketIdentifier)->getValue('LU'),
            'language' => $this->dimensionSource->getDimension($languageIdentifier)->getValue('de')
        ], $allowedCombinations);
        $this->assertContains([
            'market' => $this->dimensionSource->getDimension($marketIdentifier)->getValue('LU'),
            'language' => $this->dimensionSource->getDimension($languageIdentifier)->getValue('fr')
        ], $allowedCombinations);
        $this->assertContains([
            'market' => $this->dimensionSource->getDimension($marketIdentifier)->getValue('LU'),
            'language' => $this->dimensionSource->getDimension($languageIdentifier)->getValue('lb')
        ], $allowedCombinations);
    }
}
