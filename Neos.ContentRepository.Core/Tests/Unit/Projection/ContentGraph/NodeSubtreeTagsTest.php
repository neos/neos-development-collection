<?php
namespace Neos\ContentRepository\Core\Tests\Unit\Projection\ContentGraph;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeSubtreeTags;
use PHPUnit\Framework\TestCase;

class SubtreeTagsWithInheritedTest extends TestCase
{

    /**
     * @test
     */
    public function createEmptyCreatesInstanceWithoutTags(): void
    {
        $tags = NodeSubtreeTags::createEmpty();
        self::assertTrue($tags->tags->isEmpty());
        self::assertTrue($tags->inheritedTags->isEmpty());
    }

    /**
     * @test
     */
    public function iteratingOverIncludesAllTags(): void
    {
        $tags = NodeSubtreeTags::create(
            SubtreeTags::fromStrings('a', 'b'),
            SubtreeTags::fromStrings('c', 'd'),
        );
        $actualResult = array_map(static fn (SubtreeTag $tag) => $tag->value, iterator_to_array($tags));
        self::assertSame(['a', 'b', 'c', 'd'], $actualResult);
    }

}
