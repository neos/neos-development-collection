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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WorkspaceNameTest extends TestCase
{
    #[Test]
    public function sameNameDoesNotCreateANewInstance(): void
    {
        $instance1 = WorkspaceName::fromString('workspace-name');
        $instance2 = WorkspaceName::tryFromString('workspace-name');
        self::assertSame($instance1, $instance2);
    }

    public static function validWorkspaceNames(): iterable
    {
        yield ['a'];
        yield ['abcdefghijklmnopqrstuvwxyz'];
        yield ['a0123456789'];
        yield ['this-is-valid'];
    }

    #[DataProvider('validWorkspaceNames')]
    #[Test]
    public function fromStringWorksForValidValues(string $value): void
    {
        self::assertSame(WorkspaceName::fromString($value)->value, $value);
    }

    #[DataProvider('validWorkspaceNames')]
    #[Test]
    public function tryFromStringReturnsInstanceForValidValues(string $value): void
    {
        self::assertSame(WorkspaceName::tryFromString($value)->value, $value);
    }

    public static function invalidWorkspaceNames(): iterable
    {
        yield 'empty string' => [''];
        yield 'leading dash' => ['-invalid'];
        yield 'upper case characters' => ['thisIsNotAllowed'];
        yield 'whitespace' => ['this neither'];
        yield 'exceeding max length' => ['this-is-just-a-little-little-bit-too-long-'];
    }

    #[DataProvider('invalidWorkspaceNames')]
    #[Test]
    public function fromStringFailsForInvalidValues(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        WorkspaceName::fromString($value);
    }

    #[DataProvider('invalidWorkspaceNames')]
    #[Test]
    public function tryFromStringReturnsNullForInvalidValues(string $value): void
    {
        self::assertNull(WorkspaceName::tryFromString($value));
    }

    #[Test]
    public function forLiveReturnsAConstantInstance(): void
    {
        self::assertSame(WorkspaceName::fromString(WorkspaceName::WORKSPACE_NAME_LIVE), WorkspaceName::forLive());
    }

    public static function transliterateFromStringDataProvider(): iterable
    {
        yield 'valid name is not changed' => ['value' => 'already-valid', 'expectedResult' => 'already-valid'];
        yield 'name is lower-cased' => ['value' => 'mixedCase', 'expectedResult' => 'mixedcase'];
        yield 'chinese characters' => ['value' => '北京', 'expectedResult' => 'bei-jing'];
        yield 'german umlauts' => ['value' => 'ümläute', 'expectedResult' => 'umlaute'];
        yield 'white space' => ['value' => ' Contains spaces ', 'expectedResult' => 'contains-spaces'];
        yield 'exceeding max length' => ['value' => 'This name is just a little little bit too long', 'expectedResult' => 'this-name-is-just-a-little-little-bi'];
        yield 'only special characters' => ['value' => '-', 'expectedResult' => '336d5ebc5436534e61d16e63ddfca327'];
    }

    #[DataProvider('transliterateFromStringDataProvider')]
    #[Test]
    public function transliterateFromStringTests(string $value, string $expectedResult): void
    {
        self::assertSame($expectedResult, WorkspaceName::transliterateFromString($value)->value);
    }

    #[Test]
    public function isLiveReturnsFalseByDefault(): void
    {
        self::assertFalse(WorkspaceName::fromString('not-live')->isLive());
    }

    #[Test]
    public function isLiveReturnsTrueForLiveWorkspace(): void
    {
        self::assertTrue(WorkspaceName::forLive()->isLive());
    }

    #[Test]
    public function jsonSerializeReturnsPlainValue(): void
    {
        self::assertJsonStringEqualsJsonString(json_encode(WorkspaceName::forLive()), '"live"');
    }

    #[Test]
    public function equalsReturnsFalseIfTwoInstancesDontMatch(): void
    {
        self::assertFalse(WorkspaceName::fromString('some-workspace')->equals(WorkspaceName::fromString('some-other-workspace')));
    }

    #[Test]
    public function equalsReturnsTrueIfTwoInstancesMatch(): void
    {
        self::assertTrue(WorkspaceName::fromString('some-workspace')->equals(WorkspaceName::fromString('some-workspace')));
    }
}
