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
use Neos\Media\Domain\ValueObject\Configuration\VariantPreset;
use ReflectionException;

class ImageVariantServiceTest extends UnitTestCase
{
    public function getAllPresetsOfIdentifierProvider(): iterable
    {
        $neosPreset = new VariantPreset(new Label('neosTestImageVariants'));
        $flowPreset = new VariantPreset(new Label('flowTestImageVariants'));

        yield 'empty' => [
            'variantPreset' => [
                'neos' => $neosPreset,
                'flow' => $flowPreset
            ],
            'presetIdentifier' => '',
            'hasEmptyResult' => true
        ];

        yield 'known preset' => [
            'variantPreset' => [
                'neos' => $neosPreset,
                'flow' => $flowPreset
            ],
            'presetIdentifier' => 'neos',
            'hasEmptyResult' => false
        ];

        yield 'unknown preset' => [
            'variantPreset' => [
                'neos' => $neosPreset,
                'flow' => $flowPreset
            ],
            'presetIdentifier' => 'imageVariant',
            'hasEmptyResult' => true
        ];
    }

    /**
     * @test
     * @dataProvider getAllPresetsOfIdentifierProvider
     *
     * @param VariantPreset[] $variantPresets
     * @param string|null $presetIdentifier
     * @param bool $emptyResult
     *
     * @throws ReflectionException
     */
    public function getAllPresetsOfIdentifier(array $variantPresets, ?string $presetIdentifier, bool $emptyResult): void
    {
        $assetVariantGeneratorMock = $this->getMockBuilder(AssetVariantGenerator::class)->getMock();
        $assetVariantGeneratorMock->expects($this->once())->method('getVariantPresets')->willReturn($variantPresets);

        $imageVariantService = new ImageVariantService($assetVariantGeneratorMock);

        $presetsConfig = $imageVariantService->getAllPresetsOfIdentifier($presetIdentifier);

        if ($emptyResult) {
            self::assertEquals([], $presetsConfig);
        } else {
            self::assertEquals([$variantPresets['neos']], $presetsConfig);
        }
    }
}
