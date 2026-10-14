<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\SharedModel\Workspace;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceNames;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspaces;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceStatus;
use PHPUnit\Framework\TestCase;

class WorkspaceNamesTest extends TestCase
{
    /**
     * @test
     */
    public function contain()
    {
        $workspaceNames = WorkspaceNames::create(
            $nameInstance = WorkspaceName::fromString('foo'),
            WorkspaceName::fromString('bar'),
        );

        self::assertTrue($workspaceNames->contain($nameInstance));
        self::assertTrue($workspaceNames->contain(WorkspaceName::fromString('foo')));
        self::assertFalse($workspaceNames->contain(WorkspaceName::fromString('not-included')));
    }

    /**
     * @test
     */
    public function fromWorkspaces()
    {
        $workspaces = Workspaces::fromArray([
            self::workspace('root', null),
            self::workspace('regular', 'root'),
            self::workspace('other-root', null),
        ]);

        self::assertEquals(
            WorkspaceNames::create(
                WorkspaceName::fromString('root'),
                WorkspaceName::fromString('regular'),
                WorkspaceName::fromString('other-root'),
            ),
            WorkspaceNames::fromWorkspaces($workspaces)
        );
    }

    private static function workspace(string $name): Workspace
    {
        return Workspace::create(
            WorkspaceName::fromString($name),
            null,
            ContentStreamId::create(),
            WorkspaceStatus::UP_TO_DATE,
            false
        );
    }
}
