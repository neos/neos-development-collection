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
use Neos\Media\Domain\ValueObject\Configuration\Label;
use Neos\Media\Domain\ValueObject\Configuration\Variant;

class VariantTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function invalidIdentifiers(): array
    {
        return [
            ['something with spaces'],
            ['somwthingWithÜmlauts'],
            ['somethingWithEm⭕️ji😀'],
        ];
    }

    /**
     * @param $identifier
     * @dataProvider invalidIdentifiers()
     * @test
     */
    public function invalidIdentifiersAreRejected($identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Variant($identifier, new Label('Test'));
    }

    /**
     * @test
     */
    public function variantIdentifierCanBeRetrieved(): void
    {
        $variant = new Variant('someVariant', new Label('Test'));

        self::assertSame('someVariant', $variant->identifier());
    }

    /**
     * @test
     */
    public function variantLabelCanBeRetrieved(): void
    {
        $label = new Label('This is a variant');
        $variant = new Variant('someVariant', $label);

        self::assertSame($label, $variant->label());
    }

    /**
     * @test
     */
    public function variantFromArray(): void
    {
        $configuration = [
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
        ];

        $variant = Variant::fromConfiguration('wide', $configuration);

        self::assertEquals('Wide', $variant->label());
        self::assertEquals('A wide cropped variant', $variant->description());

        $adjustments = $variant->adjustments();
        self::assertCount(1, $adjustments);
        self::assertInstanceOf(Adjustment::class, $adjustments['crop']);
    }
}
