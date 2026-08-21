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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class AdjustmentTest extends UnitTestCase
{
    /**
     * @return array
     */
    public static function invalidIdentifiers(): array
    {
        return [
            ['something with spaces'],
            ['somwthingWithÜmlauts'],
            ['somethingWithEm⭕️ji😀'],
        ];
    }

    /**
     * @param $identifier
     */
    #[DataProvider('invalidIdentifiers')]
    #[Test]
    public function invalidIdentifiersAreRejected($identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Adjustment($identifier, '');
    }

    #[Test]
    public function adjustmentIdentifierCanBeRetrieved(): void
    {
        $adjustment = new Adjustment('someAdjustment', '');
        self::assertSame('someAdjustment', $adjustment->identifier());
    }

    #[Test]
    public function missingTypeIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Adjustment::fromConfiguration('someAdjustment', []);
    }

    #[Test]
    public function fromConfiguration(): void
    {
        $configuration = [
            'type' => CropImageAdjustment::class,
            'options' => [
                'aspectRatio' => [
                    'width' => 16,
                    'height' => 9
                ]
            ]
        ];

        $adjustment = Adjustment::fromConfiguration('wide', $configuration);
        self::assertSame('wide', $adjustment->identifier());
        self::assertSame(CropImageAdjustment::class, $adjustment->type());
        self::assertSame($configuration['options'], $adjustment->options());
    }
}
