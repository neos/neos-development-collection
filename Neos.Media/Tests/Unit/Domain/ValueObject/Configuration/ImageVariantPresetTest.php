<?php
namespace Neos\Media\Tests\Unit\Domain\ValueObject\Configuration;

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
use Neos\Media\Domain\Model\Adjustment\CropImageAdjustment;
use Neos\Media\Domain\ValueObject\Configuration\Adjustment;
use Neos\Media\Domain\ValueObject\Configuration\ImageVariantPreset;
use Neos\Media\Domain\ValueObject\Configuration\Label;
use Neos\Media\Domain\ValueObject\Configuration\Variant;

class ImageVariantPresetTest extends UnitTestCase
{
    /**
     * @test
     */
    public function imageVariantLabelCanBeRetrieved(): void
    {
        $label = new Label('Demo Preset 1');
        $preset = new ImageVariantPreset($label);
        self::assertSame($label, $preset->label());
    }

    /**
     * @test
     */
    public function fromConfiguration(): void
    {
        $configuration = [
            'label' => 'Demo Preset 1 👋',
            'variants' => [
                'wide' => [
                    'label' => 'Wide',
                    'description' => 'A wide cropped variant',
                    // 'icon' => '...',
                    'adjustments' => [
                        'crop' => [
                            'type' => CropImageAdjustment::class,
                            'options' => [
                                'aspectRatio' => [
                                    'width' => 16,
                                    'height' => 9
                                ],
                                'boosts' => []
                            ],
                            'editableOptions' => [
                                'boosts' => true
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $preset = ImageVariantPreset::fromConfiguration($configuration);

        $variants = $preset->variants();
        self::assertCount(1, $variants);
        self::assertContainsOnlyInstancesOf(Variant::class, $variants);

        $variant = $variants['wide'];
        self::assertEquals('Wide', $variant->label());
        self::assertEquals('A wide cropped variant', $variant->description());

        $adjustments = $variant->adjustments();
        self::assertCount(1, $adjustments);
        self::assertContainsOnlyInstancesOf(Adjustment::class, $adjustments);
    }
}
