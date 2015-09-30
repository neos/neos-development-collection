<?php
namespace TYPO3\Media\Tests\Unit\TypeConverter;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the ImageConverter
 */
class ImageInterfaceConverterTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Media\TypeConverter\ImageInterfaceConverter
     */
    protected $converter;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->converter = new \TYPO3\Media\TypeConverter\ImageInterfaceConverter();
        $this->mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');
        $this->inject($this->converter, 'reflectionService', $this->mockReflectionService);

        $this->mockPersistenceManager = $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');
        $this->inject($this->converter, 'persistenceManager', $this->mockPersistenceManager);

        $this->mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
        $this->inject($this->converter, 'objectManager', $this->mockObjectManager);
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(array('string', 'array'), $this->converter->getSupportedSourceTypes());
        $this->assertEquals('TYPO3\Media\Domain\Model\ImageInterface', $this->converter->getSupportedTargetType());
        $this->assertEquals(2, $this->converter->getPriority());
    }

    /**
     * @return array
     */
    public function canConvertFromDataProvider()
    {
        $dummyResource = $this->getMock('TYPO3\Flow\Resource\Resource');
        return array(
            array(array('resource' => $dummyResource), 'TYPO3\Media\Domain\Model\Image', true),
            array(array('__identity' => 'foo'), 'TYPO3\Media\Domain\Model\Image', false),
            array(array('resource' => $dummyResource), 'TYPO3\Media\Domain\Model\ImageInterface', true),
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
     * @expectedException \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException
     */
    public function convertFromReturnsNullIfResourcePropertyIsNotConverted()
    {
        $this->mockReflectionService->expects($this->any())->method('isClassAnnotatedWith')->with('TYPO3\Media\Domain\Model\Image', 'TYPO3\Flow\Annotations\ValueObject')->will($this->returnValue(true));
        $this->assertNull($this->converter->convertFrom(array(), 'TYPO3\Media\Domain\Model\Image', array()));
        $this->assertNull($this->converter->convertFrom(array(), 'TYPO3\Media\Domain\Model\Image', array('resource' => 'bar')));
    }
}
