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
use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion;
use org\bovigo\vfs\vfsStream;
use Neos\Fusion\Core\Parser;
use org\bovigo\vfs\vfsStreamContent;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * Testcase for the Include Pattern for the Fusion Parser
 */
class ParserIncludeTest extends UnitTestCase
{
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

    public function includeSingleFile(): \Generator
    {
        yield 'single file without quotes and space relative' => [
            'context' => 'vfs://fusion/root.fusion',
            'fusion ' => 'include:file.fusion',
            'include' => ['file.fusion' => true]
        ];

        yield 'single file without quotes and special chars absolute' => [
            'context' => 'vfs://fusion/root.fusion',
            'fusion ' => 'include: vfs://fusion/sp3z:al-CHAR_.fs ',
            'include' => ['sp3z:al-CHAR_.fs' => true]
        ];

        yield 'single file without quotes and with space absolute' => [
            'context' => 'vfs://fusion/root.fusion',
            'fusion ' => 'include:  vfs://fusion/file.fusion  ',
            'include' => ['file.fusion' => true]
        ];

        yield 'single file with single quotes explicit relative' => [
            'context' => 'vfs://fusion/root.fusion',
            'fusion ' => 'include:\'./file.fusion\'',
            'include' => ['file.fusion' => true]
        ];

        yield 'single file with double quotes space explicit relative' => [
            'context' => 'vfs://fusion/root.fusion',
            'fusion ' => 'include:  "  ./file.fusion  "  ',
            'include' => ['file.fusion' => true]
        ];

        yield 'single file context will prevent recursion' => [
            'context' => 'vfs://fusion/file.fusion',
            'fusion ' => 'include:./file.fusion',
            'include' => []
        ];

        yield 'single file with spaces without quotes' => [
            'context' => 'vfs://fusion/file.fusion',
            'fusion ' => 'include:file with space.fusion',
            'include' => ['file with space.fusion' => true]
        ];
    }

    public function includeNormalGlobbing(): \Generator
    {
        yield 'simple glob relative' => [
            'context' => 'vfs://fusion/root.fusion',
            'fusion ' => 'include: Globbing/* ',
            'include' => [
                'Globbing/level1-A.fusion' => true,
                'Globbing/level1-B.fusion' => true,
                'Globbing/level1-C.fusion' => true,
            ]
        ];
    }

    public function includeRecursiveGlobbing(): \Generator
    {
        yield 'recursive glob relative with specified file end' => [
            'context' => 'vfs://fusion/root.fusion',
            'fusion ' => 'include:Globbing/**/*.fusion',
            'include' => [
                'Globbing/Nested/level2-A.fusion' => true,
                'Globbing/Nested/level2-B.fusion' => true,
                'Globbing/Nested/Deep/level3-A.fusion' => true,
                'Globbing/level1-A.fusion' => true,
                'Globbing/level1-B.fusion' => true,
                'Globbing/level1-C.fusion' => true,
            ]
        ];

        yield 'recursive glob relative without recursion' => [
            'context' => 'vfs://fusion/Globbing/level1-A.fusion',
            'fusion ' => 'include:**/*',
            'include' => [
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
            'context' => 'vfs://fusion/root.fusion',
            'fusion ' => 'include: vfs://fusion/Globbing/**/*',
            'include' => [
                'Globbing/Nested/level2-A.fusion' => true,
                'Globbing/Nested/level2-B.fusion' => true,
                'Globbing/Nested/Deep/level3-A.fusion' => true,
                'Globbing/level1-A.fusion' => true,
                'Globbing/level1-B.fusion' => true,
                'Globbing/level1-C.fusion' => true,
            ]
        ];

        yield 'recursive glob relative parent' => [
            'context' => 'vfs://fusion/Globbing/Nested/level2-A.fusion',
            'fusion ' => 'include: ../**/*',
            'include' => [
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
            'context' => 'vfs://fusion/root.fusion',
            'fusion ' => 'include: ./Globbing/**/*-A.fusion',
            'include' => [
                'Globbing/Nested/level2-A.fusion' => true,
                'Globbing/Nested/Deep/level3-A.fusion' => true,
                'Globbing/level1-A.fusion' => true,
            ]
        ];
    }

    /**
     * @dataProvider includeSingleFile
     * @dataProvider includeNormalGlobbing
     * @dataProvider includeRecursiveGlobbing
     * @test
     */
    public function fusionParseMethodIsCalledCorrectlyWithFilesOfPattern($contextPathAndFilename, $fusionCode, $expectedFusionAst): void
    {
        $parser = new Parser();
        $actualFusionAst = $parser->parse($fusionCode, $contextPathAndFilename);

        self::assertSame($expectedFusionAst, $actualFusionAst);
    }

    /**
     * @test
     */
    public function absoluteIncludePathsRaiseError(): void
    {
        self::expectException(Fusion\Exception::class);
        self::expectExceptionCode(1636144292);

        $fusionCode = <<<Fusion
        include: /**/*
        Fusion;

        $parser = new Parser();
        $parser->parse($fusionCode);
    }


    public function weirdFusionIncludesAreParsed(): \Generator
    {
        yield 'pattern with direct comment' => [
            'include: pattern/* hello this is (not) a comment */', 'pattern/* hello this is (not) a comment */'
        ];
        yield 'unquoted pattern with spaces' => [
            'include: fusion file with space.fusion', 'fusion file with space.fusion'
        ];
        yield 'unquoted pattern with uncommon char' => [
            'include: folder/äüö.fusion', 'folder/äüö.fusion'
        ];
        yield 'unquoted pattern with what could be a comment as start' => [
            'include: /**/*', '/**/*'
        ];
        yield 'unquoted pattern with what could be a comment as start 2' => [
            'include: // hello', '// hello'
        ];
    }

    /**
     * @dataProvider weirdFusionIncludesAreParsed
     * @test
     */
    public function testFusionIncludesArePassedCorrectlyToIncludeAndParseFilesByPattern($fusion, $includePattern): void
    {
        $parser = $this->getMockBuilder(Parser::class)->disableOriginalConstructor()->onlyMethods(['includeAndParseFilesByPattern'])->getMock();
        $parser
            ->expects(self::once())
            ->method('includeAndParseFilesByPattern')
            ->with($includePattern);

        $parser->parse($fusion);
    }

    /**
     * FilePattern accept only simple File paths or /**\/* and /*
     */
    public function unsupportedGlobbingTechnics(): array
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

    /**
     * @test
     * @dataProvider unsupportedGlobbingTechnics
     */
    public function testUnsupportedGlobbingTechnicsThrowException($pattern): void
    {
        self::expectException(Fusion\Exception::class);
        self::expectExceptionCode(1636144713);

        $fusionCode = <<<Fusion
        include: vfs://fusion/$pattern
        Fusion;

        $parser = new Parser();
        $parser->parse($fusionCode);
    }

    /**
     * @test
     */
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
