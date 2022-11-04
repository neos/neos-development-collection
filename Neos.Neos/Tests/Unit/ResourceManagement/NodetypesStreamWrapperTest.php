<?php
namespace Neos\Neos\Tests\Unit\ResourceManagement;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\ResourceManagement\Exception;
use org\bovigo\vfs\vfsStream;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\ResourceManagement\NodeTypesStreamWrapper;

/**
 * Tests for the NodeTypesStreamWrapper class
 */
class NodetypesStreamWrapperTest extends UnitTestCase
{
    /**
     * @var NodeTypesStreamWrapper
     */
    protected $nodeTypesStreamWrapper;

    /**
     * @var PackageManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPackageManager;

    protected function setUp(): void
    {
        vfsStream::setup('Foo');

        $this->nodeTypesStreamWrapper = new NodeTypesStreamWrapper();

        $this->mockPackageManager = $this->createMock(PackageManager::class);
        $this->inject($this->nodeTypesStreamWrapper, 'packageManager', $this->mockPackageManager);
    }

    /**
     * @test
     */
    public function openThrowsExceptionForInvalidScheme()
    {
        $this->expectException(\InvalidArgumentException::class);
        $openedPathAndFilename = '';
        $this->nodeTypesStreamWrapper->open('invalid-scheme://foo/bar', 'r', 0, $openedPathAndFilename);
    }

    public function providePathesToCheckForForbiddenTraversalOutOfPath(): array
    {
        return [
            // pathes that traverse out of package scope
            ['nodetypes://Some.Package/../', true],
            ['nodetypes://Some.Package/..', true],
            ['nodetypes://Some.Package/../bar', true],
            ['nodetypes://Some.Package/foo/../../bar/..', true],
            // traversal inside package is allowed
            ['nodetypes://Some.Package/foo/../bar', false],
            ['nodetypes://Some.Package/bar/..', false],
            // no traversal
            ['nodetypes://Some.Package/test.txt', false],
            ['nodetypes://Some.Package/foo/bar/baz.txt', false]
        ];
    }

    /**
     * @test
     * @dataProvider providePathesToCheckForForbiddenTraversalOutOfPath
     */
    public function openThrowsExceptionForPathesThatTryToTraverseUpwards(string $forbiddenPath, bool $expectException)
    {
        if ($expectException) {
            $this->expectException(\InvalidArgumentException::class);
        }

        $mockPackage = $this->createMock(FlowPackageInterface::class);
        $mockPackage->expects(self::any())->method('getPackagePath')->will(self::returnValue('vfs://Packages/Application/Some.Package'));
        $this->mockPackageManager->expects(self::once())->method('getPackage')->with('Some.Package')->will(self::returnValue($mockPackage));

        $result = $this->nodeTypesStreamWrapper->open($forbiddenPath, 'r', 0, $openedPathAndFilename);
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function openThrowsExceptionForNonExistingPackages()
    {
        $this->expectException(Exception::class);
        $packageKey = 'Non.Existing.Package';
        $this->mockPackageManager->expects(self::once())->method('getPackage')->willThrowException(new \Neos\Flow\Package\Exception\UnknownPackageException('Test exception'));

        $openedPathAndFilename = '';
        $this->nodeTypesStreamWrapper->open('nodetypes://' . $packageKey . '/Some/Path', 'r', 0, $openedPathAndFilename);
    }

    /**
     * @test
     */
    public function openResolvesPackageKeysUsingThePackageManager()
    {
        $packageKey = 'Some.Package';
        mkdir('vfs://Foo/NodeTypes/');
        file_put_contents('vfs://Foo/NodeTypes/Path', 'fixture');

        $mockPackage = $this->createMock(FlowPackageInterface::class);
        $mockPackage->expects(self::any())->method('getPackagePath')->will(self::returnValue('vfs://Foo'));
        $this->mockPackageManager->expects(self::once())->method('getPackage')->with($packageKey)->will(self::returnValue($mockPackage));

        $openedPathAndFilename = '';
        self::assertTrue($this->nodeTypesStreamWrapper->open('nodetypes://' . $packageKey . '/Path', 'r', 0, $openedPathAndFilename));
        self::assertSame($openedPathAndFilename, 'vfs://Foo/NodeTypes/Path');
    }

    /**
     * This makes sure the code does not see a 40-charatcer package key as a resource hash.
     * @test
     */
    public function openResolves40CharacterLongPackageKeysUsingThePackageManager()
    {
        $packageKey = 'Some.PackageKey.Containing.40.Characters';
        mkdir('vfs://Foo/NodeTypes/');
        file_put_contents('vfs://Foo/NodeTypes/Path', 'fixture');

        $mockPackage = $this->createMock(FlowPackageInterface::class);
        $mockPackage->expects(self::any())->method('getPackagePath')->will(self::returnValue('vfs://Foo'));
        $this->mockPackageManager->expects(self::once())->method('getPackage')->with($packageKey)->will(self::returnValue($mockPackage));

        $openedPathAndFilename = '';
        self::assertTrue($this->nodeTypesStreamWrapper->open('nodetypes://' . $packageKey . '/Path', 'r', 0, $openedPathAndFilename));
        self::assertSame($openedPathAndFilename, 'vfs://Foo/NodeTypes/Path');
    }
}
