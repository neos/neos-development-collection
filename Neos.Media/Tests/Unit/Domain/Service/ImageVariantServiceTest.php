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
use Neos\Media\Domain\Service\ImageVariantService;
use Neos\Media\Domain\ValueObject\Configuration\Label;
use Neos\Media\Domain\ValueObject\Configuration\Variant;
use Neos\Media\Domain\ValueObject\Configuration\VariantPreset;
use ReflectionClass;

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
    public function getAllPresetsByConfigsProvider(): array
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
            [['neos' => $neosPreset, 'flow' => $flowPreset], null],
            [['neos' => $neosPreset, 'flow' => $flowPreset], ''],
            [['neos' => $neosPreset, 'flow' => $flowPreset], 'neos']
        ];
    }

    /**
     * @test
     * @param array $variantPresetConfigs
     * @param string|null $presetIdentifier
     * @dataProvider getAllPresetsByConfigsProvider
     */
    public function getAllPresetsByConfigs(array $variantPresetConfigs, ?string $presetIdentifier): void
    {
        if (is_null($presetIdentifier)) {
            $presetsConfig = $this->imageVariantService->getAllPresetsByConfigs($variantPresetConfigs);
        } else {
            $presetsConfig = $this->imageVariantService->getAllPresetsByConfigs($variantPresetConfigs, $presetIdentifier);
        }

        if (!$presetIdentifier) {
            self::assertEquals(['neos' => ['square', 'portrait'], 'flow' => ['panorama']], $presetsConfig);
        } else {
            self::assertEquals(['neos' => ['square', 'portrait']], $presetsConfig);
        }
    }
}
