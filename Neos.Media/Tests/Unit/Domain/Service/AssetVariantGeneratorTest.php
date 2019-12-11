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

use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Media\Domain\Model\Adjustment\CropImageAdjustment;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Audio;
use Neos\Media\Domain\Model\Document;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Model\Video;
use Neos\Media\Domain\Service\AssetVariantGenerator;
use Neos\Media\Exception\AssetVariantGeneratorException;

/**
 * Test case for the Asset Variant Generator
 */
class AssetVariantGeneratorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getVariantPresetsReturnsConfiguration(): void
    {
        $configuration = [
            'Flownative.Demo:Preset1' => [
                'label' => 'Demo Preset 1',
                'mediaTypePatterns' => ['~image/.*~'],
                'variants' => []
            ],
            'Flownative.Demo:Preset2' => [
                'label' => 'Demo Preset 2',
                'mediaTypePatterns' => ['~image/.*~'],
                'variants' => []
            ]
        ];

        $assetVariantGenerator = new AssetVariantGenerator();
        $this->inject($assetVariantGenerator, 'variantPresetsConfiguration', $configuration);

        $presets = $assetVariantGenerator->getVariantPresets();
        self::assertArrayHasKey('Flownative.Demo:Preset1', $presets);
        self::assertSame($configuration['Flownative.Demo:Preset1']['label'], (string)$presets['Flownative.Demo:Preset1']->label());
        self::assertArrayHasKey('Flownative.Demo:Preset2', $presets);
        self::assertSame($configuration['Flownative.Demo:Preset2']['label'], (string)$presets['Flownative.Demo:Preset2']->label());
    }

    /**
     * @test
     * @throws
     */
    public function variantsAreCreatedAccordingToPreset(): void
    {
        $asset = $this->mockImage();
        $assetVariantGenerator = $this->mockAssetVariantGenerator([]);
        $variants = $assetVariantGenerator->createVariants($asset);
        self::assertCount(2, $variants);
    }

    /**
     * @test
     * @throws
     */
    public function noVariantsAreCreatedForUnsupportedAssetTypes(): void
    {
        $assetVariantGenerator = $this->mockAssetVariantGenerator(['createVariant']);

        $documentAsset = $this->createMock(Document::class);
        assert($documentAsset instanceof AssetInterface);
        $videoAsset = $this->createMock(Video::class);
        assert($videoAsset instanceof AssetInterface);
        $audioAsset = $this->createMock(Audio::class);
        assert($audioAsset instanceof AssetInterface);
        $imageVariantAsset = $this->createMock(ImageVariant::class);
        assert($imageVariantAsset instanceof AssetInterface);

        self::assertCount(0, $assetVariantGenerator->createVariants($documentAsset));
        self::assertCount(0, $assetVariantGenerator->createVariants($videoAsset));
        self::assertCount(0, $assetVariantGenerator->createVariants($audioAsset));
        self::assertCount(0, $assetVariantGenerator->createVariants($imageVariantAsset));
    }

    /**
     * @test
     * @throws
     */
    public function createVariantCreatesVariantAccordingToPreset(): void
    {
        $variantPresetsConfiguration = [
            'Flownative.Demo:Preset' => [
                'label' => 'Demo Preset',
                'mediaTypePatterns' => ['~image/.*~'],
                'variants' => [
                    'wide' => [
                        'label' => 'Wide',
                        'adjustments' => [
                            'crop' => [
                                'type' => CropImageAdjustment::class,
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $asset = $this->mockImage();

        $assetVariantGenerator = $this->mockAssetVariantGenerator([], $variantPresetsConfiguration);
        $createdVariants = $assetVariantGenerator->createVariants($asset);
        self::assertArrayHasKey('Flownative.Demo:Preset:wide', $createdVariants);

        $variant = $createdVariants['Flownative.Demo:Preset:wide'];
        self::assertInstanceOf(ImageVariant::class, $variant);
        self::assertSame(1, $variant->getAdjustments()->count());
        self::assertCount(1, $asset->getVariants());
        self::assertSame('Flownative.Demo:Preset', $variant->getPresetIdentifier());
        self::assertSame('wide', $variant->getPresetVariantName());
    }

    /**
     * @test
     * @throws \Neos\Flow\Configuration\Exception\InvalidConfigurationException
     * @throws \Neos\Flow\ResourceManagement\Exception
     * @throws \Neos\Media\Exception\AssetVariantGeneratorException
     * @throws \Neos\Media\Exception\ImageFileException
     */
    public function createVariantThrowsExceptionOnUnknownAdjustmentType(): void
    {
        $this->expectException(AssetVariantGeneratorException::class);
        $variantPresetsConfiguration = [
            'Flownative.Demo:Preset' => [
                'label' => 'Demo Preset',
                'mediaTypePatterns' => ['~image/.*~'],
                'variants' => [
                    'wide' => [
                        'label' => 'Wide',
                        'adjustments' => [
                            'crop' => [
                                'type' => 'Acme\Invalid\AdjustmentType',
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $asset = $this->mockImage();

        $assetVariantGenerator = $this->mockAssetVariantGenerator([], $variantPresetsConfiguration);
        $assetVariantGenerator->createVariants($asset);
    }


    // ------------------------------------------------------------------------------------------------------------
    // TEST HELPER METHODS

    /**
     * @param array $methods
     * @param array $variantPresetsConfiguration
     * @return AssetVariantGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    private function mockAssetVariantGenerator(array $methods, array $variantPresetsConfiguration = [])
    {
        if ($variantPresetsConfiguration === []) {
            $variantPresetsConfiguration = [
                'Flownative.Demo:Preset1' => [
                    'label' => 'Demo Preset 1',
                    'mediaTypePatterns' => ['~image/.*~'],
                    'variants' => [
                        'wide' => [
                            'label' => 'Wide',
                            'adjustments' => [
                                'crop' => [
                                    'type' => CropImageAdjustment::class,
                                ]
                            ]
                        ]
                    ]
                ],
                'Flownative.Demo:Preset2' => [
                    'label' => 'Demo Preset 2',
                    'mediaTypePatterns' => ['~image/.*~'],
                    'variants' => [
                        'wide' => [
                            'label' => 'Wide',
                            'adjustments' => [
                                'crop' => [
                                    'type' => CropImageAdjustment::class,
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        $methods[] = 'createAssetVariant';

        $mock = $this->createPartialMock(AssetVariantGenerator::class, $methods);
        $that = $this;

        $this->inject($mock, 'variantPresetsConfiguration', $variantPresetsConfiguration);

        $mock->method('createAssetVariant')->willReturnCallback(
            function (Image $imageAsset) use ($that) {
                return $that->getMockBuilder(ImageVariant::class)
                    ->setConstructorArgs([$imageAsset])
                    ->setMethods(['refresh', 'renderResource'])
                    ->getMock();
            }
        );

        return $mock;
    }

    /**
     * @return Image|\PHPUnit\Framework\MockObject\MockObject
     */
    private function mockImage()
    {
        $mock = $this->getMockBuilder(Image::class)
            ->setConstructorArgs([$this->createMock(PersistentResource::class)])
            ->setMethods(['refresh', 'renderResource', 'getMediaType'])
            ->getMock();
        $mock->method('getMediaType')->willReturn('image/jpeg');

        return $mock;
    }
}
