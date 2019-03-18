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
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\ValueObject\Configuration\AssetClass;

class AssetClassTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function validAssetClasses(): array
    {
        return [
            ['Image']
        ];
    }

    /**
     * @param $assetClassAsString
     * @dataProvider validAssetClasses()
     * @test
     */
    public function validAssetClassesAreAccepted($assetClassAsString): void
    {
        $assetClass = new AssetClass($assetClassAsString);
        self::assertSame($assetClassAsString, (string)$assetClass);
    }

    /**
     * @return array
     */
    public function invalidAssetClasses(): array
    {
        return [
            [''],
            ['Something'],
            ['Image '],
            ['image'],
            ['Document'],
        ];
    }

    /**
     * @param $assetClassAsString
     * @test
     * @dataProvider invalidAssetClasses()
     * @expectedException \InvalidArgumentException
     */
    public function invalidAssetClassesAreRejected($assetClassAsString): void
    {
        new AssetClass($assetClassAsString);
    }

    /**
     * @test
     */
    public function getFullyQualifiedClassNameReturnsCorrectClassName(): void
    {
        $assetClass = new AssetClass('Image');
        self::assertSame(Image::class, $assetClass->getFullyQualifiedClassName());
    }
}
