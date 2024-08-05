<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\SharedModel\Workspace;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use PHPUnit\Framework\TestCase;

final class WorkspaceNameTest extends TestCase
{
    /**
     * @test
     */
    public function sameNameDoesNotCreateANewInstance(): void
    {
        $instance1 = WorkspaceName::fromString('workspace-name');
        $instance2 = WorkspaceName::tryFromString('workspace-name');
        self::assertSame($instance1, $instance2);
    }

    private static function validWorkspaceNames(): iterable
    {
        yield ['a'];
        yield ['abcdefghijklmnopqrstuvwxyz'];
        yield ['a0123456789'];
        yield ['this-is-valid'];
    }

    /**
     * @test
     * @dataProvider validWorkspaceNames
     */
    public function fromStringWorksForValidValues(string $value): void
    {
        self::assertSame(WorkspaceName::fromString($value)->value, $value);
    }

    /**
     * @test
     * @dataProvider validWorkspaceNames
     */
    public function tryFromStringReturnsInstanceForValidValues(string $value): void
    {
        self::assertSame(WorkspaceName::tryFromString($value)->value, $value);
    }

    private static function invalidWorkspaceNames(): iterable
    {
        yield 'empty string' => [''];
        yield 'only digits' => ['123'];
        yield 'leading dash' => ['-invalid'];
        yield 'upper case characters' => ['thisIsNotAllowed'];
        yield 'whitespace' => ['this neither'];
        yield 'exceeding max length' => ['this-is-just-a-little-too-long-'];
    }

    /**
     * @test
     * @dataProvider invalidWorkspaceNames
     */
    public function fromStringFailsForInvalidValues(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        WorkspaceName::fromString($value);
    }

    /**
     * @test
     * @dataProvider invalidWorkspaceNames
     */
    public function tryFromStringReturnsNullForInvalidValues(string $value): void
    {
        self::assertNull(WorkspaceName::tryFromString($value));
    }

    /**
     * @test
     */
    public function forLiveReturnsAConstantInstance(): void
    {
        self::assertSame(WorkspaceName::fromString(WorkspaceName::WORKSPACE_NAME_LIVE), WorkspaceName::forLive());
    }

    private static function transliterateFromStringDataProvider(): iterable
    {
        yield 'valid name is not changed' => ['value' => 'already-valid', 'expectedResult' => 'already-valid'];
        yield 'name is lower-cased' => ['value' => 'mixedCase', 'expectedResult' => 'mixedcase'];
        yield 'chinese characters' => ['value' => '北京', 'expectedResult' => 'bei-jing'];
        yield 'german umlauts' => ['value' => 'ümläute', 'expectedResult' => 'umlaute'];
        yield 'white space' => ['value' => ' Contains spaces ', 'expectedResult' => 'contains-spaces'];
        yield 'exceeding max length' => ['value' => 'This name is just a little too long', 'expectedResult' => 'this-name-is-just-a-little-too'];
        yield 'only special characters' => ['value' => '-', 'expectedResult' => 'workspace-336d5ebc5436534e61d1'];
    }

    /**
     * @test
     * @dataProvider transliterateFromStringDataProvider
     */
    public function transliterateFromStringTests(string $value, string $expectedResult): void
    {
        self::assertSame($expectedResult, WorkspaceName::transliterateFromString($value)->value);
    }

    /**
     * @test
     */
    public function isLiveReturnsFalseByDefault(): void
    {
        self::assertFalse(WorkspaceName::fromString('not-live')->isLive());
    }

    /**
     * @test
     */
    public function isLiveReturnsTrueForLiveWorkspace(): void
    {
        self::assertTrue(WorkspaceName::forLive()->isLive());
    }

    /**
     * @test
     */
    public function jsonSerializeReturnsPlainValue(): void
    {
        self::assertJsonStringEqualsJsonString(json_encode(WorkspaceName::forLive()), '"live"');
    }

    /**
     * @test
     */
    public function equalsReturnsFalseIfTwoInstancesDontMatch(): void
    {
        self::assertFalse(WorkspaceName::fromString('some-workspace')->equals(WorkspaceName::fromString('some-other-workspace')));
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfTwoInstancesMatch(): void
    {
        self::assertTrue(WorkspaceName::fromString('some-workspace')->equals(WorkspaceName::fromString('some-workspace')));
    }
}
