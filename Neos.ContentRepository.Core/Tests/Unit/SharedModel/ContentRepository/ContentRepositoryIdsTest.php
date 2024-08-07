<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\SharedModel\ContentRepository;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryIds;
use PHPUnit\Framework\TestCase;

class ContentRepositoryIdsTest extends TestCase
{
    /**
     * @test
     */
    public function fromArraySupportsEmptyArray(): void
    {
        $contentRepositoryIds = ContentRepositoryIds::fromArray([]);
        self::assertCount(0, $contentRepositoryIds);
    }

    /**
     * @test
     */
    public function fromArrayConvertsStringsToContentRepositoryIds(): void
    {
        $contentRepositoryIds = ContentRepositoryIds::fromArray(['some_cr_id', ContentRepositoryId::fromString('other_cr_id')]);
        self::assertEquals([ContentRepositoryId::fromString('some_cr_id'), ContentRepositoryId::fromString('other_cr_id')], iterator_to_array($contentRepositoryIds));
    }

    /**
     * @test
     */
    public function fromArrayThrowsExceptionForInvalidItem(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ContentRepositoryIds::fromArray([1234]);
    }
}
