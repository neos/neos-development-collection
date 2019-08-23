<?php
namespace Neos\Media\Tests\Unit\TypeConverter;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\TypeConverter\ImageInterfaceConverter;
use Neos\Flow\ResourceManagement\PersistentResource;

/**
 * Testcase for the ImageConverter
 */
class ImageInterfaceConverterTest extends UnitTestCase
{
    /**
     * @var ImageInterfaceConverter
     */
    protected $converter;

    /**
     * @var ReflectionService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockReflectionService;

    /**
     * @var PersistenceManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPersistenceManager;

    /**
     * @var ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockObjectManager;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->converter = new ImageInterfaceConverter();
        $this->mockReflectionService = $this->createMock(ReflectionService::class);
        $this->inject($this->converter, 'reflectionService', $this->mockReflectionService);

        $this->mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $this->inject($this->converter, 'persistenceManager', $this->mockPersistenceManager);

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->inject($this->converter, 'objectManager', $this->mockObjectManager);
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        self::assertEquals(['string', 'array'], $this->converter->getSupportedSourceTypes());
        self::assertEquals(ImageInterface::class, $this->converter->getSupportedTargetType());
        self::assertEquals(2, $this->converter->getPriority());
    }

    /**
     * @return array
     */
    public function canConvertFromDataProvider()
    {
        $dummyResource = $this->createMock(PersistentResource::class);
        return [
            [['resource' => $dummyResource], Image::class, true],
            [['__identity' => 'foo'], Image::class, false],
            [['resource' => $dummyResource], ImageInterface::class, true],
        ];
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
        self::assertEquals($expected, $this->converter->canConvertFrom($source, $targetType));
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfResourcePropertyIsNotConverted()
    {
        $this->mockObjectManager->expects(self::any())->method('getClassNameByObjectName')->will(self::returnCallback(function ($objectType) {
            return $objectType;
        }));
        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOption(ImageInterfaceConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, true);

        self::assertNull($this->converter->convertFrom([], Image::class, [], $configuration));
    }
}
