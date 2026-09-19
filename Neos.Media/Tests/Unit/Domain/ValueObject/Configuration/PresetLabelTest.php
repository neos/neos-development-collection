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
use Neos\Media\Domain\ValueObject\Configuration\Label;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class PresetLabelTest extends UnitTestCase
{
    /**
     * @return array
     */
    public static function validLabels(): array
    {
        return [
            ['Demo Preset 1'],
            ['x'],
            ['a-pretty-long-name-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'],
            ['Coffee cups in different sizes'],
            ['Tazas de café en diferentes tamaños'],
            ['فناجين قهوة بأحجام مختلفة'],
            ['Kaffeetassen in verschiedenen Größen'],
            ['Kaffibollar í mismunandi stærðum'],
            ['Kafijas tases dažādos izmēros'],
            ['😀'],
            ['☕️'],
            ['😇']
        ];
    }

    /**
     * @param $label
     */
    #[DataProvider('validLabels')]
    #[Test]
    public function validLabelsAreAccepted($label): void
    {
        $presetLabel = new Label($label);
        self::assertSame($label, (string)$presetLabel);
    }

    /**
     * @return array
     */
    public static function invalidLabels(): array
    {
        return [
            [''],
            ['<some tag>'],
            ['a-too-long-name-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'],
        ];
    }

    /**
     * @param $label
     */
    #[DataProvider('invalidLabels')]
    #[Test]
    public function invalidLabelsAreRejected($label): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Label($label);
    }
}
