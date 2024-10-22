<?php
namespace Neos\Fusion\Tests\Unit\Core;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Core\FusionSourceCode;
use Neos\Fusion\Core\FusionSourceCodeCollection;
use org\bovigo\vfs\vfsStream;

class FusionSourceCodeDtosTest extends UnitTestCase
{
    private static function getTheOnlyFusionCode(FusionSourceCodeCollection $collection): FusionSourceCode
    {
        self::assertCount(1, $collection);
        /** @var FusionSourceCode[] $asArray */
        $asArray = $collection->getIterator()->getArrayCopy();
        return $asArray[0];
    }

    /**
     * @test
     */
    public function pureFactories()
    {
        $code = FusionSourceCode::fromString("a");
        self::assertEquals("a", $code->getSourceCode());
        self::assertEquals(null, $code->getFilePath());

        $code = self::getTheOnlyFusionCode(
            FusionSourceCodeCollection::fromString("a")
        );
        self::assertEquals("a", $code->getSourceCode());
        self::assertEquals(null, $code->getFilePath());

        $code = FusionSourceCode::fromDangerousPotentiallyDifferingSourceCodeAndFilePath("a", "memory://a");
        self::assertEquals("a", $code->getSourceCode());
        self::assertEquals("memory://a", $code->getFilePath());

        $code = FusionSourceCodeCollection::empty();
        self::assertCount(0, $code);
    }

    /**
     * @test
     */
    public function fromFilePathFactories()
    {
        vfsStream::setup('fusion', null, [
            "test.fusion" => "contents"
        ]);

        $code = FusionSourceCode::fromFilePath("vfs://fusion/test.fusion");
        self::assertEquals("contents", $code->getSourceCode());
        self::assertEquals("vfs://fusion/test.fusion", $code->getFilePath());

        $code = self::getTheOnlyFusionCode(
            FusionSourceCodeCollection::fromFilePath("vfs://fusion/test.fusion")
        );
        self::assertEquals("contents", $code->getSourceCode());
        self::assertEquals("vfs://fusion/test.fusion", $code->getFilePath());

        $code = FusionSourceCodeCollection::tryFromFilePath("vfs://fusion/notexistant.fusion");
        self::assertCount(0, $code);
    }

    /**
     * @test
     */
    public function collectionIterableAndCountable()
    {
        $code = FusionSourceCode::fromString("a");
        $collection = new FusionSourceCodeCollection($code);

        self::assertCount(1, $collection);

        foreach ($collection as $index => $item) {
            self::assertEquals($code, $item, "Item not in collection.");
            self::assertEquals(0, $index);
        }
    }

    /**
     * @test
     */
    public function deduplication()
    {
        vfsStream::setup('fusion', null, [
            "test1.fusion" => "contents1",
            "test2.fusion" => "contents2",
        ]);

        $code1 = FusionSourceCode::fromFilePath("vfs://fusion/test1.fusion");

        $code2 = FusionSourceCode::fromFilePath("vfs://fusion/test2.fusion");

        $code1doubled = FusionSourceCode::fromFilePath("vfs://fusion/test1.fusion");

        $collection = new FusionSourceCodeCollection($code1, $code2, $code1doubled);

        self::assertCount(2, $collection, "The deduplication didnt work.");

        $asArray = $collection->getIterator()->getArrayCopy();

        self::assertEquals($code2, $asArray[0]);
        self::assertEquals($code1doubled, $asArray[1]);
    }

    /**
     * @test
     */
    public function deduplication2()
    {
        vfsStream::setup('fusion', null, [
            "test1.fusion" => "contents1",
            "test2.fusion" => "contents2",
            "test3.fusion" => "contents3",
        ]);

        $code1 = FusionSourceCode::fromFilePath("vfs://fusion/test1.fusion");

        $code2 = FusionSourceCode::fromFilePath("vfs://fusion/test2.fusion");

        $code3 = FusionSourceCode::fromString("huhu");

        $code1doubled = FusionSourceCode::fromFilePath("vfs://fusion/test1.fusion");

        $code1doubled2 = FusionSourceCode::fromFilePath("vfs://fusion/test1.fusion");

        $code4 = FusionSourceCode::fromString("vfs://fusion/test3.fusion");

        $collection = new FusionSourceCodeCollection($code1, $code2, $code3, $code1doubled, $code1doubled2, $code4);

        self::assertCount(4, $collection, "The deduplication didnt work.");

        $asArray = $collection->getIterator()->getArrayCopy();

        self::assertEquals($code2, $asArray[0]);
        self::assertEquals($code3, $asArray[1]);
        self::assertEquals($code1doubled2, $asArray[2]);
        self::assertEquals($code4, $asArray[3]);
    }

    /**
     * @test
     */
    public function deduplication4()
    {
        vfsStream::setup('fusion', null, [
            "test1.fusion" => "contents1",
            "test2.fusion" => "contents2",

        ]);

        $code1 = FusionSourceCode::fromFilePath("vfs://fusion/test1.fusion");

        $code2 = FusionSourceCode::fromFilePath("vfs://fusion/test2.fusion");

        $code3 = FusionSourceCode::fromString("huhu");

        $code1doubled = FusionSourceCode::fromFilePath("vfs://fusion/test1.fusion");

        $code1doubled2 = FusionSourceCode::fromFilePath("vfs://fusion/test1.fusion");

        $collection = new FusionSourceCodeCollection($code1, $code2, $code3, $code1doubled, $code1doubled2);

        self::assertCount(3, $collection, "The deduplication didnt work.");

        $asArray = $collection->getIterator()->getArrayCopy();

        self::assertEquals($code2, $asArray[0]);
        self::assertEquals($code3, $asArray[1]);
        self::assertEquals($code1doubled2, $asArray[2]);
    }


    /**
     * @test
     */
    public function union()
    {
        $code1 = FusionSourceCodeCollection::fromString("a");

        $code2 = FusionSourceCodeCollection::fromString("b");

        $collection = $code1->union($code2);

        self::assertCount(2, $collection, "The union didnt work.");

        /** @var FusionSourceCode[] $asArray */
        $asArray = $collection->getIterator()->getArrayCopy();

        self::assertEquals("a", $asArray[0]->getSourceCode());
        self::assertEquals("b", $asArray[1]->getSourceCode());
    }
}
