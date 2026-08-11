<?php
namespace Neos\Fusion\Tests\Unit\Core\Parser;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Neos\Fusion\Core\FusionSourceCodeCollection;
use Neos\Fusion\Core\FusionSourceCode;
use Neos\Fusion\Exception;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion;
use Neos\Fusion\Core\Cache\ParserCache;
use org\bovigo\vfs\vfsStream;
use Neos\Fusion\Core\Parser;
use org\bovigo\vfs\vfsStreamContent;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * Testcase for the Include Pattern for the Fusion Parser
 */
class ParserIncludeTest extends UnitTestCase
{
    protected Parser $parser;

    public function setUp(): void
    {
        $this->parser = new Parser();
        $this->injectParserCacheMockIntoParser($this->parser);
    }

    private function injectParserCacheMockIntoParser(Parser $parser): void
    {
        $parserCache = $this->getMockBuilder(ParserCache::class)->getMock();
        $parserCache->method('cacheForFusionFile')->willReturnCallback(fn ($_, $getValue) => $getValue());
        $parserCache->method('cacheForDsl')->willReturnCallback(fn ($_, $_2, $getValue) => $getValue());
        $this->inject($parser, 'parserCache', $parserCache);
    }

    public static function setUpBeforeClass(): void
    {
        $directory = [
            'root.fusion' => '"root.fusion" = true',
            'file.fusion' => '"file.fusion" = true',
            'sp3z:al-CHAR_.fs' => '"sp3z:al-CHAR_.fs" = true',
            'file with space.fusion' => '"file with space.fusion" = true',
            'Globbing' => [
                'Nested' => [
                    'level2-A.fusion' => '"Globbing/Nested/level2-A.fusion" = true',
                    'level2-B.fusion' => '"Globbing/Nested/level2-B.fusion" = true',
                    'level2-C.js' => '"Globbing/Nested/level2-C.js" = true',
                    'Deep' => [
                        'level3-A.fusion' => '"Globbing/Nested/Deep/level3-A.fusion" = true',
                        'level3-B.js' => '"Globbing/Nested/Deep/level3-B.js" = true',
                    ]
                ],
                'level1-A.fusion' => '"Globbing/level1-A.fusion" = true',
                'level1-B.fusion' => '"Globbing/level1-B.fusion" = true',
                'level1-C.fusion' => '"Globbing/level1-C.fusion" = true',
                'level1-D.js' => '"Globbing/level1-D.js" = true',
                'level1-E.js' => '"Globbing/level1-E.js" = true',
                'level1-F.css' => '"Globbing/level1-F.css" = true',
            ],
        ];

        $file_system = vfsStream::setup('fusion', null, $directory);
        // This is needed otherwise use of phps \stat() in the fusion parser for the detection of recursion
        // will not work if the files have the same size
        self::setUniqueLastModifiedTimeForEachFileRecursive($file_system);
    }

    public static function includeSingleFile(): \Generator
    {
        yield 'single file without quotes and space relative' => [
            'contextPathAndFilename' => 'vfs://fusion/root.fusion',
            'fusionCode' => 'include:file.fusion',
            'expectedFusionAst' => ['file.fusion' => true]
        ];

        yield 'single file without quotes and special chars absolute' => [
            'contextPathAndFilename' => 'vfs://fusion/root.fusion',
            'fusionCode' => 'include: vfs://fusion/sp3z:al-CHAR_.fs ',
            'expectedFusionAst' => ['sp3z:al-CHAR_.fs' => true]
        ];

        yield 'single file without quotes and with space absolute' => [
            'contextPathAndFilename' => 'vfs://fusion/root.fusion',
            'fusionCode' => 'include:  vfs://fusion/file.fusion  ',
            'expectedFusionAst' => ['file.fusion' => true]
        ];

        yield 'single file with single quotes explicit relative' => [
            'contextPathAndFilename' => 'vfs://fusion/root.fusion',
            'fusionCode' => 'include:\'./file.fusion\'',
            'expectedFusionAst' => ['file.fusion' => true]
        ];

        yield 'single file with double quotes space explicit relative' => [
            'contextPathAndFilename' => 'vfs://fusion/root.fusion',
            'fusionCode' => 'include:  "  ./file.fusion  "  ',
            'expectedFusionAst' => ['file.fusion' => true]
        ];

        yield 'single file context will prevent recursion' => [
            'contextPathAndFilename' => 'vfs://fusion/file.fusion',
            'fusionCode' => 'include:./file.fusion',
            'expectedFusionAst' => []
        ];
    }

    public static function includeNormalGlobbing(): \Generator
    {
        yield 'simple glob relative' => [
            'contextPathAndFilename' => 'vfs://fusion/root.fusion',
            'fusionCode' => 'include: Globbing/* ',
            'expectedFusionAst' => [
                'Globbing/level1-A.fusion' => true,
                'Globbing/level1-B.fusion' => true,
                'Globbing/level1-C.fusion' => true,
            ]
        ];
    }

    public static function includeRecursiveGlobbing(): \Generator
    {
        yield 'recursive glob relative with specified file end' => [
            'contextPathAndFilename' => 'vfs://fusion/root.fusion',
            'fusionCode' => 'include:Globbing/**/*.fusion',
            'expectedFusionAst' => [
                'Globbing/Nested/level2-A.fusion' => true,
                'Globbing/Nested/level2-B.fusion' => true,
                'Globbing/Nested/Deep/level3-A.fusion' => true,
                'Globbing/level1-A.fusion' => true,
                'Globbing/level1-B.fusion' => true,
                'Globbing/level1-C.fusion' => true,
            ]
        ];

        yield 'recursive glob relative without recursion' => [
            'contextPathAndFilename' => 'vfs://fusion/Globbing/level1-A.fusion',
            'fusionCode' => 'include:**/*',
            'expectedFusionAst' => [
                'Globbing/Nested/level2-A.fusion' => true,
                'Globbing/Nested/level2-B.fusion' => true,
                'Globbing/Nested/Deep/level3-A.fusion' => true,
                // Not included because this would mean a recursion. The context is already level1-A.fusion
                // 'Globbing/level1-A.fusion' => true,
                'Globbing/level1-B.fusion' => true,
                'Globbing/level1-C.fusion' => true,
            ]
        ];

        yield 'recursive glob absolute' => [
            'contextPathAndFilename' => 'vfs://fusion/root.fusion',
            'fusionCode' => 'include: vfs://fusion/Globbing/**/*',
            'expectedFusionAst' => [
                'Globbing/Nested/level2-A.fusion' => true,
                'Globbing/Nested/level2-B.fusion' => true,
                'Globbing/Nested/Deep/level3-A.fusion' => true,
                'Globbing/level1-A.fusion' => true,
                'Globbing/level1-B.fusion' => true,
                'Globbing/level1-C.fusion' => true,
            ]
        ];

        yield 'recursive glob relative parent' => [
            'contextPathAndFilename' => 'vfs://fusion/Globbing/Nested/level2-A.fusion',
            'fusionCode' => 'include: ../**/*',
            'expectedFusionAst' => [
                // Not included because this would mean a recursion.
                // 'Globbing/Nested/level2-A.fusion' => true,
                'Globbing/Nested/level2-B.fusion' => true,
                'Globbing/Nested/Deep/level3-A.fusion' => true,
                'Globbing/level1-A.fusion' => true,
                'Globbing/level1-B.fusion' => true,
                'Globbing/level1-C.fusion' => true,
            ]
        ];

        yield 'recursive glob relative with uncommon specified file end' => [
            'contextPathAndFilename' => 'vfs://fusion/root.fusion',
            'fusionCode' => 'include: ./Globbing/**/*-A.fusion',
            'expectedFusionAst' => [
                'Globbing/Nested/level2-A.fusion' => true,
                'Globbing/Nested/Deep/level3-A.fusion' => true,
                'Globbing/level1-A.fusion' => true,
            ]
        ];
    }

    #[DataProvider('includeSingleFile')]
    #[DataProvider('includeNormalGlobbing')]
    #[DataProvider('includeRecursiveGlobbing')]
    #[Test]
    public function fusionParseMethodIsCalledCorrectlyWithFilesOfPattern($contextPathAndFilename, $fusionCode, $expectedFusionAst): void
    {
        $actualFusionAst = $this->parser->parseFromSource(new FusionSourceCodeCollection(
            FusionSourceCode::fromDangerousPotentiallyDifferingSourceCodeAndFilePath($fusionCode, $contextPathAndFilename)
        ))->toArray();

        self::assertSame($expectedFusionAst, $actualFusionAst);
    }

    #[Test]
    public function absoluteIncludePathsRaiseError(): void
    {
        self::expectException(Exception::class);
        self::expectExceptionCode(1636144292);

        $fusionCode = <<<Fusion
        include: /**/*
        Fusion;

        $this->parser->parseFromSource(FusionSourceCodeCollection::fromString($fusionCode))->toArray();
    }

    public static function weirdFusionIncludeValuesAreHandedOver(): \Generator
    {
        yield 'pattern with direct comment' => [
            'include: pattern /* this is a comment */', 'pattern'
        ];
        yield 'pattern with direct comment 2' => [
            'include: pattern // this is a comment', 'pattern'
        ];
        yield 'unquoted pattern with what could be a comment as start' => [
            'include: /**/*', '/**/*'
        ];
        yield 'unquoted pattern with what could be a comment as start 2' => [
            'include: //hello', '//hello'
        ];
    }

    #[DataProvider('weirdFusionIncludeValuesAreHandedOver')]
    #[Test]
    public function testFusionIncludesArePassedCorrectlyToIncludeAndParseFilesByPattern($fusion, $includePattern): void
    {
        $parser = $this->getMockBuilder(Parser::class)->disableOriginalConstructor()->onlyMethods(['handleFileInclude'])->getMock();
        $this->injectParserCacheMockIntoParser($parser);
        $matcher = self::once();
        $parser
            ->expects($matcher)
            ->method('handleFileInclude')
            ->willReturnCallback(function (...$parameters) use ($matcher, $includePattern) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertSame($includePattern, $parameters[1]);
                }
            });

        $parser->parseFromSource(FusionSourceCodeCollection::fromString($fusion))->toArray();
    }

    public static function throwsFusionIncludesWithSpaces(): \Generator
    {
        yield 'pattern with direct comment' => [
            'include: /* comments are here not allowed */ pattern '
        ];
        yield 'pattern with direct comment 2' => [
            'include: pattern/* hello this is (not) a comment */'
        ];
        yield 'unquoted pattern with spaces' => [
            'include: fusion file with space.fusion'
        ];
        yield 'unquoted pattern with uncommon char' => [
            'include: folder/äüö.fusion'
        ];
        yield 'unquoted pattern with what could be a comment as start 2' => [
            'include: // hello'
        ];
    }

    #[DataProvider('throwsFusionIncludesWithSpaces')]
    #[Test]
    public function testFusionIncludesThrowExpectedEndOfStatement($fusion): void
    {
        self::expectException(Exception::class);
        self::expectExceptionCode(1635878683);

        $parser = $this->getMockBuilder(Parser::class)->disableOriginalConstructor()->onlyMethods(['handleFileInclude'])->getMock();
        $this->injectParserCacheMockIntoParser($parser);
        $parser
            ->expects(self::never())
            ->method('handleFileInclude');

        $parser->parseFromSource(FusionSourceCodeCollection::fromString($fusion))->toArray();
    }

    /**
     * FilePattern accept only simple File paths or /**\/* and /*
     */
    public static function unsupportedGlobbingTechnics(): array
    {
        return [
            'simple glob at end without slash (that means its a file)' => ['file*'],
            'simple glob inside filename' => ['file*name.fusion'],
            'recursive glob at end without slash' => ['folder**/*'],
            'simple glob with superfluous star' => ['folder/**'],
            'recursive glob with superfluous star' => ['folder/**/**'],
            'recursive glob with specific filename' => ['folder/**/filename.fusion'],
            'recursive glob with specific recursion folder' => ['folder/*folder*/*'],
            'recursive glob with normal folder glob' => ['folder/**/*/'],
            'recursive glob with normal folder glob and filename' => ['folder/**/*/file.fusion'],
            'recursive glob with specific folder' => ['folder/**/*folder/file.fusion'],
            'multiple globing mixed' => ['folder/*/folder/**/*'],
            'simple glob only for folder' => ['folder/*/file.fusion'],
            'recursive glob with glob filename' => ['folder/**/*file*.fusion'],
        ];
    }

    #[DataProvider('unsupportedGlobbingTechnics')]
    #[Test]
    public function testUnsupportedGlobbingTechnicsThrowException($pattern): void
    {
        self::expectException(Exception::class);
        self::expectExceptionCode(1636144713);

        $fusionCode = <<<Fusion
        include: vfs://fusion/$pattern
        Fusion;

        $this->parser->parseFromSource(FusionSourceCodeCollection::fromString($fusionCode))->toArray();
    }

    #[Test]
    public function testThatInTestEnvironmentStatCanDifferentiateBetweenFilesWhoHaveTheSameSize(): void
    {
        self::assertNotSame(stat('vfs://fusion/Globbing/level1-A.fusion'), stat('vfs://fusion/Globbing/level1-B.fusion'));
        self::assertNotSame(stat('vfs://fusion/Globbing/level1-A.fusion'), stat('vfs://fusion/Globbing/level1-C.fusion'));
        self::assertSame(stat('vfs://fusion/Globbing/level1-A.fusion'), stat('vfs://fusion/Globbing/level1-A.fusion'));
    }

    private static function setUniqueLastModifiedTimeForEachFileRecursive(vfsStreamContent $content, &$time = 1636129472): void
    {
        if ($content->getType() === vfsStreamContent::TYPE_FILE) {
            $content->lastModified(++$time);
            return;
        }
        if ($content->getType() !== vfsStreamContent::TYPE_DIR) {
            return;
        }
        /** @var vfsStreamDirectory $content */
        foreach ($content->getChildren() as $child) {
            self::setUniqueLastModifiedTimeForEachFileRecursive($child, $time);
        }
    }
}
