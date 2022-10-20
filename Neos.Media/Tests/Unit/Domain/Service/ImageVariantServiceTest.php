<?php

namespace Neos\Media\Tests\Unit\Domain\Service;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\Service\AssetVariantGenerator;
use Neos\Media\Domain\Service\ImageVariantService;
use Neos\Media\Domain\ValueObject\Configuration\Label;
use Neos\Media\Domain\ValueObject\Configuration\Variant;
use Neos\Media\Domain\ValueObject\Configuration\VariantPreset;
use ReflectionClass;
use ReflectionException;

/**
 * Test case for the ImageVariant Service
 */
class ImageVariantServiceTest extends UnitTestCase
{
    /**
     * @var ImageVariantService
     */
    protected $imageVariantService;

    protected function setUp(): void
    {
        $this->imageVariantService = new ImageVariantService();
    }

    /**
     * @return array
     */
    public function getAllPresetsByConfigurationsProvider(): array
    {
        $neosPreset = new VariantPreset(new Label('neosTestImageVariants'));
        $flowPreset = new VariantPreset(new Label('flowTestImageVariants'));

        $neosPresetConfiguration = [
            'square' => new Variant('square', new Label('square')),
            'portrait' => new Variant('portrait', new Label('portrait'))
        ];
        $flowPresetConfiguration = [
            'panorama' => new Variant('panorama', new Label('panorama'))
        ];

        $variantPresetReflection = new ReflectionClass(VariantPreset::class);

        $variantPresetReflectionVariants = $variantPresetReflection->getProperty('variants');
        $variantPresetReflectionVariants->setAccessible(true);

        $variantPresetReflectionVariants->setValue($neosPreset, $neosPresetConfiguration);
        $variantPresetReflectionVariants->setValue($flowPreset, $flowPresetConfiguration);

        return [
            'empty' => [['neos' => $neosPreset, 'flow' => $flowPreset], '', true],
            'known preset' => [['neos' => $neosPreset, 'flow' => $flowPreset], 'neos', false],
            'unknown preset' => [['neos' => $neosPreset, 'flow' => $flowPreset], 'imageVariant', true],
        ];
    }

    /**
     * @test
     * @dataProvider getAllPresetsByConfigurationsProvider
     *
     * @param VariantPreset[] $variantPresets
     * @param string|null $presetIdentifier
     * @param bool $emptyResult
     *
     * @throws ReflectionException
     */
    public function getAllPresetsByConfigurations(array $variantPresets, ?string $presetIdentifier, bool $emptyResult): void
    {
        $assetVariantGeneratorMock = $this->getMockBuilder(AssetVariantGenerator::class)->getMock();
        $assetVariantGeneratorMock->expects($this->once())->method('getVariantPresets')->willReturn($variantPresets);

        $imageVariantService = new ImageVariantService();

        $imageVariantServiceReflection = new ReflectionClass(ImageVariantService::class);
        $reflectionProperty = $imageVariantServiceReflection->getProperty('assetVariantGenerator');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($imageVariantService, $assetVariantGeneratorMock);

        $reflectionMethod = $imageVariantServiceReflection->getMethod('getAllPresetsOfIdentifier');
        $reflectionMethod->setAccessible(true);
        $presetsConfig = $reflectionMethod->invokeArgs($imageVariantService, [$presetIdentifier]);

        if ($emptyResult) {
            self::assertEquals([], $presetsConfig);
        } else {
            self::assertEquals([$variantPresets['neos']], $presetsConfig);
        }
    }
}
