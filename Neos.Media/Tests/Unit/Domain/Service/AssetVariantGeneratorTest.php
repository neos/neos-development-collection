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
use Neos\Media\Domain\Service\AssetService;
use Neos\Media\Domain\Service\AssetVariantGenerator;

/**
 * Test case for the Asset Variant Generator
 */
class AssetVariantGeneratorTest extends UnitTestCase
{
    /**
     * @test
     * @throws
     */
    public function variantsAreCreatedAccordingToPreset(): void
    {
        $assetService = $this->mockAssetService();
        $asset = $this->mockImage();

        $assetVariantGenerator = $this->mockAssetVariantGenerator([], $assetService);

        $variants = $assetVariantGenerator->createVariants($asset);
        self::assertCount(2, $variants);
    }

    /**
     * @test
     * @throws
     */
    public function noVariantsAreCreatedForUnsupportedAssetTypes(): void
    {
        $assetService = $this->mockAssetService();

        $assetVariantGenerator = $this->mockAssetVariantGenerator(['createVariant'], $assetService);

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
                'mediaTypePatterns' => ['image\/.*'],
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

        $assetService = $this->mockAssetService($variantPresetsConfiguration);
        $asset = $this->mockImage();

        $assetVariantGenerator = $this->mockAssetVariantGenerator([], $assetService);
        $createdVariants = $assetVariantGenerator->createVariants($asset);
        self::assertArrayHasKey('Flownative.Demo:Preset', $createdVariants);

        $variant = $createdVariants['Flownative.Demo:Preset'];
        self::assertInstanceOf(ImageVariant::class, $variant);
        self::assertSame(1, $variant->getAdjustments()->count());
        self::assertCount(1, $asset->getVariants());
        self::assertSame('Flownative.Demo:Preset', $variant->getPresetIdentifier());
        self::assertSame('wide', $variant->getPresetVariantName());
    }

    /**
     * @expectedException \Neos\Media\Exception\AssetVariantGeneratorException
     * @test
     * @throws \Neos\Flow\Configuration\Exception\InvalidConfigurationException
     * @throws \Neos\Flow\ResourceManagement\Exception
     * @throws \Neos\Media\Exception\AssetVariantGeneratorException
     * @throws \Neos\Media\Exception\ImageFileException
     */
    public function createVariantThrowsExceptionOnUnknownAdjustmentType(): void
    {
        $variantPresetsConfiguration = [
            'Flownative.Demo:Preset' => [
                'label' => 'Demo Preset',
                'mediaTypePatterns' => ['image\/.*'],
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

        $assetService = $this->mockAssetService($variantPresetsConfiguration);
        $asset = $this->mockImage();

        $assetVariantGenerator = $this->mockAssetVariantGenerator([], $assetService);
        $assetVariantGenerator->createVariants($asset);
    }


    // ------------------------------------------------------------------------------------------------------------
    // TEST HELPER METHODS

    /**
     * @param array $variantPresetsConfiguration
     * @return AssetService
     */
    private function mockAssetService(array $variantPresetsConfiguration = []): AssetService
    {
        if ($variantPresetsConfiguration === []) {
            $variantPresetsConfiguration = [
                'Flownative.Demo:Preset1' => [
                    'label' => 'Demo Preset 1',
                    'mediaTypePatterns' => ['image\/.*'],
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
                    'mediaTypePatterns' => ['image\/.*'],
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

        $assetService = new AssetService();
        $this->inject($assetService, 'variantPresetsConfiguration', $variantPresetsConfiguration);
        return $assetService;
    }

    /**
     * @param array $methods
     * @param AssetService $assetService
     * @return AssetVariantGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockAssetVariantGenerator(array $methods, AssetService $assetService)
    {
        $methods[] = 'createImageVariant';

        $mock = $this->createPartialMock(AssetVariantGenerator::class, $methods);
        $this->inject($mock, 'assetService', $assetService);
        $that = $this;

        $mock->method('createImageVariant')->willReturnCallback(
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
     * @return Image|\PHPUnit_Framework_MockObject_MockObject
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
