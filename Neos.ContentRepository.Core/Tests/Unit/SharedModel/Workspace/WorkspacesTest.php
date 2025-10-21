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

class WorkspacesTest extends TestCase
{
    public static function provideGetBaseWorkspacesExamples()
    {
        yield 'empty workspaces' => [
            'workspaces' => [],
            'parameter' => 'random',
            'baseWorkspaces' => [],
        ];

        yield 'not in set' => [
            'workspaces' => [
                self::workspace('a', null),
                self::workspace('b', 'a'),
            ],
            'parameter' => 'random',
            'baseWorkspaces' => [],
        ];

        yield 'base not in set (b) -> ~a~' => [
            'workspaces' => [
                self::workspace('b', 'a'),
            ],
            'parameter' => 'b',
            'baseWorkspaces' => [],
        ];

        yield 'one deep (b) -> a' => [
            'workspaces' => [
                self::workspace('a', null),
                self::workspace('b', 'a'),
            ],
            'parameter' => 'b',
            'baseWorkspaces' => ['a'],
        ];

        yield 'recursive (d) -> c -> b -> a' => [
            'workspaces' => [
                self::workspace('a', null),
                self::workspace('b', 'a'),
                self::workspace('c', 'b'),
                self::workspace('d', 'c'),
            ],
            'parameter' => 'd',
            'baseWorkspaces' => ['a', 'b', 'c'],
        ];

        yield 'recursive, exclude descendants d -> (c) -> b -> a' => [
            'workspaces' => [
                self::workspace('a', null),
                self::workspace('b', 'a'),
                self::workspace('c', 'b'),
                self::workspace('d', 'c'),
            ],
            'parameter' => 'c',
            'baseWorkspaces' => ['a', 'b'],
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
            'parameter' => 'c',
            'baseWorkspaces' => ['a', 'b']
        ];
    }

    public static function provideGetDependantWorkspacesExamples()
    {
        yield 'empty workspaces' => [
            'workspaces' => [],
            'parameter' => 'random',
            'immediatelyDepending' => [],
            'recursiveDependingpaces' => [],
        ];

        yield 'not in set' => [
            'workspaces' => [
                self::workspace('a', null),
                self::workspace('b', 'a'),
            ],
            'parameter' => 'random',
            'immediatelyDepending' => [],
            'recursiveDependingpaces' => [],
        ];

        yield 'one deep b -> (a)' => [
            'workspaces' => [
                self::workspace('a', null),
                self::workspace('b', 'a'),
            ],
            'parameter' => 'a',
            'immediatelyDepending' => ['b'],
            'recursiveDependingpaces' => ['b'],
        ];

        yield 'recursive d -> c -> b -> (a)' => [
            'workspaces' => [
                self::workspace('a', null),
                self::workspace('b', 'a'),
                self::workspace('c', 'b'),
                self::workspace('d', 'c'),
            ],
            'parameter' => 'a',
            'immediatelyDepending' => ['b'],
            'recursiveDepending' => ['b', 'c', 'd'],
        ];

        yield 'recursive, exclude bases d -> c -> (b) -> a' => [
            'workspaces' => [
                self::workspace('a', null),
                self::workspace('b', 'a'),
                self::workspace('c', 'b'),
                self::workspace('d', 'c'),
            ],
            'parameter' => 'b',
            'immediatelyDepending' => ['c'],
            'recursiveDepending' => ['c', 'd'],
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
            'parameter' => 'b',
            'immediatelyDepending' => ['c', 'e'],
            'recursiveDepending' => ['c', 'e', 'd', 'f']
        ];
    }

    /**
     * @dataProvider provideGetDependantWorkspacesExamples
     */
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

    /**
     * @dataProvider provideGetBaseWorkspacesExamples
     */
    public function testGetBaseWorkspaces(array $workspaces, string $requestedWorkspaceName, array $expectedBaseWorkspaceNames): void
    {
        $actual = Workspaces::fromArray($workspaces)->getBaseWorkspaces(WorkspaceName::fromString($requestedWorkspaceName));
        // todo order should be fixed
        self::assertEqualsCanonicalizing(
            $expectedBaseWorkspaceNames,
            $actual->map(fn (Workspace $workspace) => $workspace->workspaceName->value)
        );
    }

    /**
     * @dataProvider rootWorkspaceSampleProvider
     */
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
