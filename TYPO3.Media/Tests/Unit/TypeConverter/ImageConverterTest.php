<?php
namespace TYPO3\Media\Tests\Unit\TypeConverter;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the ImageConverter
 */
class ImageConverterTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Media\TypeConverter\ImageConverter
     */
    protected $converter;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->converter = new \TYPO3\Media\TypeConverter\ImageConverter();
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(array('array'), $this->converter->getSupportedSourceTypes());
        $this->assertEquals('TYPO3\Media\Domain\Model\Image', $this->converter->getSupportedTargetType());
        $this->assertEquals(1, $this->converter->getPriority());
    }

    /**
     * @return array
     */
    public function canConvertFromDataProvider()
    {
        return array(
            array(array(), 'TYPO3\Media\Domain\Model\Image', true),
            array(array('__identity' => 'foo'), 'TYPO3\Media\Domain\Model\Image', false),
            array(array(), 'TYPO3\Media\Domain\Model\ImageInterface', true),
        );
    }

    /**
     * @test
     * @dataProvider canConvertFromDataProvider
     *
     * @param mixed $source
     * @param string $targetType
     * @param boolean $expected
     */
    public function canConvertFromTests($source, $targetType, $expected)
    {
        $this->assertEquals($expected, $this->converter->canConvertFrom($source, $targetType));
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfResourcePropertyIsNotConverted()
    {
        $this->assertNull($this->converter->convertFrom(array(), 'TYPO3\Media\Domain\Model\Image'));
        $this->assertNull($this->converter->convertFrom(array(), 'TYPO3\Media\Domain\Model\Image', array('resource' => 'bar')));
    }
}
