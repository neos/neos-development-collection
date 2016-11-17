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
        $this->mockReflectionService = $this->createMock('TYPO3\Flow\Reflection\ReflectionService');
        $this->inject($this->converter, 'reflectionService', $this->mockReflectionService);

        $this->mockPersistenceManager = $this->createMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');
        $this->inject($this->converter, 'persistenceManager', $this->mockPersistenceManager);

        $this->mockObjectManager = $this->createMock('TYPO3\Flow\Object\ObjectManagerInterface');
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
        $dummyResource = $this->createMock('TYPO3\Flow\Resource\Resource');
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
     */
    public function convertFromReturnsNullIfResourcePropertyIsNotConverted()
    {
        $this->mockObjectManager->expects($this->any())->method('getClassNameByObjectName')->will($this->returnCallback(function ($objectType) {
            return $objectType;
        }));
        $configuration = new \TYPO3\Flow\Property\PropertyMappingConfiguration();
        $configuration->setTypeConverterOption('TYPO3\Media\TypeConverter\ImageInterfaceConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, true);

        $this->assertNull($this->converter->convertFrom(array(), 'TYPO3\Media\Domain\Model\Image', array(), $configuration));
    }
}
