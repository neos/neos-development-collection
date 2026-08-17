<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\SharedModel\Workspace;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspaces;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceStatus;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class WorkspacesTest extends TestCase
{
    public static function provideGetBaseWorkspacesExamples()
    {
        yield 'empty workspaces' => [
            'workspaces' => [],
            'requestedWorkspaceName' => 'random',
            'expectedBaseWorkspaceNames' => [],
        ];

        yield 'not in set' => [
            'workspaces' => [
                self::workspace('a', null),
                self::workspace('b', 'a'),
            ],
            'requestedWorkspaceName' => 'random',
            'expectedBaseWorkspaceNames' => [],
        ];

        yield 'base not in set (b) -> ~a~' => [
            'workspaces' => [
                self::workspace('b', 'a'),
            ],
            'requestedWorkspaceName' => 'b',
            'expectedBaseWorkspaceNames' => [],
        ];

        yield 'one deep (b) -> a' => [
            'workspaces' => [
                self::workspace('a', null),
                self::workspace('b', 'a'),
            ],
            'requestedWorkspaceName' => 'b',
            'expectedBaseWorkspaceNames' => ['a'],
        ];

        yield 'recursive (d) -> c -> b -> a' => [
            'workspaces' => [
                self::workspace('a', null),
                self::workspace('b', 'a'),
                self::workspace('c', 'b'),
                self::workspace('d', 'c'),
            ],
            'requestedWorkspaceName' => 'd',
            'expectedBaseWorkspaceNames' => ['a', 'b', 'c'],
        ];

        yield 'recursive, exclude descendants d -> (c) -> b -> a' => [
            'workspaces' => [
                self::workspace('a', null),
                self::workspace('b', 'a'),
                self::workspace('c', 'b'),
                self::workspace('d', 'c'),
            ],
            'requestedWorkspaceName' => 'c',
            'expectedBaseWorkspaceNames' => ['a', 'b'],
        ];

        yield 'recursive, exclude descendants and other chains d -> (c) -> b -> a && f -> e -> b -> a && g -> a && y -> z' => [
            'workspaces' => [
                self::workspace('a', null),
                self::workspace('b', 'a'),
                self::workspace('c', 'b'),
                self::workspace('d', 'c'),

                self::workspace('e', 'b'),
                self::workspace('f', 'e'),

                self::workspace('g', 'a'),

                self::workspace('z', null),
                self::workspace('y', 'z'),
            ],
            'requestedWorkspaceName' => 'c',
            'expectedBaseWorkspaceNames' => ['a', 'b']
        ];
    }

    public static function provideGetDependantWorkspacesExamples()
    {
        yield 'empty workspaces' => [
            'workspaces' => [],
            'requestedWorkspaceName' => 'random',
            'expectedImmediatelyDepending' => [],
            'expectedRecursiveDepending' => [],
        ];

        yield 'not in set' => [
            'workspaces' => [
                self::workspace('a', null),
                self::workspace('b', 'a'),
            ],
            'requestedWorkspaceName' => 'random',
            'expectedImmediatelyDepending' => [],
            'expectedRecursiveDepending' => [],
        ];

        yield 'one deep b -> (a)' => [
            'workspaces' => [
                self::workspace('a', null),
                self::workspace('b', 'a'),
            ],
            'requestedWorkspaceName' => 'a',
            'expectedImmediatelyDepending' => ['b'],
            'expectedRecursiveDepending' => ['b'],
        ];

        yield 'recursive d -> c -> b -> (a)' => [
            'workspaces' => [
                self::workspace('a', null),
                self::workspace('b', 'a'),
                self::workspace('c', 'b'),
                self::workspace('d', 'c'),
            ],
            'requestedWorkspaceName' => 'a',
            'expectedImmediatelyDepending' => ['b'],
            'expectedRecursiveDepending' => ['b', 'c', 'd'],
        ];

        yield 'recursive, exclude bases d -> c -> (b) -> a' => [
            'workspaces' => [
                self::workspace('a', null),
                self::workspace('b', 'a'),
                self::workspace('c', 'b'),
                self::workspace('d', 'c'),
            ],
            'requestedWorkspaceName' => 'b',
            'expectedImmediatelyDepending' => ['c'],
            'expectedRecursiveDepending' => ['c', 'd'],
        ];

        yield 'recursive, exclude descendants and other chains d -> c -> (b) -> a && f -> e -> (b) -> a && g -> a && y -> z' => [
            'workspaces' => [
                self::workspace('a', null),
                self::workspace('b', 'a'),
                self::workspace('c', 'b'),
                self::workspace('d', 'c'),

                self::workspace('e', 'b'),
                self::workspace('f', 'e'),

                self::workspace('g', 'a'),

                self::workspace('z', null),
                self::workspace('y', 'z'),
            ],
            'requestedWorkspaceName' => 'b',
            'expectedImmediatelyDepending' => ['c', 'e'],
            'expectedRecursiveDepending' => ['c', 'e', 'd', 'f']
        ];
    }

    #[DataProvider('provideGetDependantWorkspacesExamples')]
    public function testGetDependantWorkspaces(array $workspaces, string $requestedWorkspaceName, array $expectedImmediatelyDepending, array $expectedRecursiveDepending): void
    {
        $workspaces =  Workspaces::fromArray($workspaces);

        $actualImmediate = $workspaces->getDependantWorkspaces(WorkspaceName::fromString($requestedWorkspaceName));
        self::assertSame(
            $expectedImmediatelyDepending,
            $actualImmediate->map(fn (Workspace $workspace) => $workspace->workspaceName->value)
        );

        $actualRecursive = $workspaces->getDependantWorkspacesRecursively(WorkspaceName::fromString($requestedWorkspaceName));
        self::assertSame(
            $expectedRecursiveDepending,
            $actualRecursive->map(fn (Workspace $workspace) => $workspace->workspaceName->value)
        );
    }

    #[DataProvider('provideGetBaseWorkspacesExamples')]
    public function testGetBaseWorkspaces(array $workspaces, string $requestedWorkspaceName, array $expectedBaseWorkspaceNames): void
    {
        $actual = Workspaces::fromArray($workspaces)->getBaseWorkspaces(WorkspaceName::fromString($requestedWorkspaceName));
        // todo order should be fixed
        self::assertEqualsCanonicalizing(
            $expectedBaseWorkspaceNames,
            $actual->map(fn (Workspace $workspace) => $workspace->workspaceName->value)
        );
    }

    #[DataProvider('rootWorkspaceSampleProvider')]
    public function testGetRootWorkspaces(Workspaces $workspaces, array $expectedRootWorkspaceNames): void
    {
        Assert::assertEquals(
            $expectedRootWorkspaceNames,
            array_map(
                fn (Workspace $workspace): WorkspaceName => $workspace->workspaceName,
                $workspaces->getRootWorkspaces(),
            )
        );
    }

    public static function rootWorkspaceSampleProvider(): iterable
    {
        yield 'empty set' => [
            'workspaces' => Workspaces::fromArray([]),
            'expectedRootWorkspaceNames' => []
        ];

        yield 'root set' => [
            'workspaces' => Workspaces::fromArray([
                self::workspace('root', null),
                self::workspace('other-root', null),
            ]),
            'expectedRootWorkspaceNames' => [
                WorkspaceName::fromString('root'),
                WorkspaceName::fromString('other-root'),
            ]
        ];

        yield 'mixed set' => [
            'workspaces' => Workspaces::fromArray([
                self::workspace('root', null),
                self::workspace('regular', 'root'),
                self::workspace('leaf', 'regular'),
                self::workspace('other-root', null),
            ]),
            'expectedRootWorkspaceNames' => [
                WorkspaceName::fromString('root'),
                WorkspaceName::fromString('other-root'),
            ]
        ];
    }

    private static function workspace(string $name, string|null $baseWorkspace): Workspace
    {
        return Workspace::create(
            WorkspaceName::fromString($name),
            $baseWorkspace ? WorkspaceName::fromString($baseWorkspace) : null,
            ContentStreamId::create(),
            WorkspaceStatus::UP_TO_DATE,
            false
        );
    }
}
