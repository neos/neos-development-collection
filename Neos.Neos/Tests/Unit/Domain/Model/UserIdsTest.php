<?php

declare(strict_types=1);

namespace Neos\Neos\Tests\Unit\Domain\Model;

use Neos\Neos\Domain\Model\UserId;
use Neos\Neos\Domain\Model\UserIds;
use PHPUnit\Framework\TestCase;

class UserIdsTest extends TestCase
{
    /**
     * @test
     */
    public function contain()
    {
        $workspaceNames = UserIds::create(
            $nameInstance = UserId::fromString('foo'),
            UserId::fromString('bar'),
        );

        self::assertTrue($workspaceNames->contain($nameInstance));
        self::assertTrue($workspaceNames->contain(UserId::fromString('foo')));
        self::assertFalse($workspaceNames->contain(UserId::fromString('not-included')));
    }
}
