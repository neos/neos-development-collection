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

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Projection\ContentGraph\DimensionSpacePointsBySubtreeTags;
use PHPUnit\Framework\TestCase;

class DimensionSpacePointsBySubtreeTagsTest extends TestCase
{

    /**
     * @test
     */
    public function createCreatesEmptyInstance(): void
    {
        self::assertJsonStringEqualsJsonString('{}', json_encode(DimensionSpacePointsBySubtreeTags::create(), JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT));
    }

    /**
     * @test
     */
    public function withSubtreeTagAndDimensionSpacePointReturnsTheSameInstanceIfItAlreadyContainsTheSubtreeTagDSPCombination(): void
    {
        $dsp = DimensionSpacePoint::fromArray(['dimensionA' => 'value1.1', 'dimensionB' => 'value1']);
        $tag = SubtreeTag::fromString('tag1');
        $dimensionSpacePointsBySubtreeTags = DimensionSpacePointsBySubtreeTags::create()
            ->withSubtreeTagAndDimensionSpacePoint($tag, $dsp);
        self::assertSame($dimensionSpacePointsBySubtreeTags, $dimensionSpacePointsBySubtreeTags->withSubtreeTagAndDimensionSpacePoint($tag, $dsp));
    }

    /**
     * @test
     */
    public function withSubtreeTagAndDimensionSpacePointAddsTagWithDSP(): void
    {
        $dsp1 = DimensionSpacePoint::fromArray(['dimensionA' => 'value1.1', 'dimensionB' => 'value1']);
        $dimensionSpacePointsBySubtreeTags = DimensionSpacePointsBySubtreeTags::create()
            ->withSubtreeTagAndDimensionSpacePoint(SubtreeTag::fromString('tag1'), $dsp1);

        $expectedJson = '{"tag1":[{"dimensionA":"value1.1","dimensionB":"value1"}]}';
        self::assertJsonStringEqualsJsonString($expectedJson, json_encode($dimensionSpacePointsBySubtreeTags, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT));
    }

    /**
     * @test
     */
    public function withSubtreeTagAndDimensionSpacePointMergesTagWithDSPs(): void
    {
        $dsp1 = DimensionSpacePoint::fromArray(['dimensionA' => 'value1.1', 'dimensionB' => 'value1']);
        $dsp2 = DimensionSpacePoint::fromArray(['dimensionA' => 'value1.2', 'dimensionB' => 'value2']);
        $dimensionSpacePointsBySubtreeTags = DimensionSpacePointsBySubtreeTags::create()
            ->withSubtreeTagAndDimensionSpacePoint(SubtreeTag::fromString('tag1'), $dsp1)
            ->withSubtreeTagAndDimensionSpacePoint(SubtreeTag::fromString('tag2'), $dsp2)
            ->withSubtreeTagAndDimensionSpacePoint(SubtreeTag::fromString('tag2'), $dsp1);

        $expectedJson = '{"tag1":[{"dimensionA":"value1.1","dimensionB":"value1"}],"tag2":[{"dimensionA":"value1.2","dimensionB":"value2"},{"dimensionA":"value1.1","dimensionB":"value1"}]}';
        self::assertJsonStringEqualsJsonString($expectedJson, json_encode($dimensionSpacePointsBySubtreeTags, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT));
    }

    /**
     * @test
     */
    public function forSubtreeTagReturnsEmptyDSPIfSubtreeTagIsNotContained(): void
    {
        $dimensionSpacePointsBySubtreeTags = DimensionSpacePointsBySubtreeTags::create();
        self::assertTrue($dimensionSpacePointsBySubtreeTags->forSubtreeTag(SubtreeTag::fromString('some-tag'))->equals(DimensionSpacePointSet::fromArray([])));
    }

    /**
     * @test
     */
    public function forSubtreeTagReturnsMatchingDSPs(): void
    {
        $dsp1 = DimensionSpacePoint::fromArray(['dimensionA' => 'value1.1', 'dimensionB' => 'value1']);
        $dsp2 = DimensionSpacePoint::fromArray(['dimensionA' => 'value1.2', 'dimensionB' => 'value2']);
        $dimensionSpacePointsBySubtreeTags = DimensionSpacePointsBySubtreeTags::create()
            ->withSubtreeTagAndDimensionSpacePoint(SubtreeTag::fromString('tag1'), $dsp1)
            ->withSubtreeTagAndDimensionSpacePoint(SubtreeTag::fromString('tag2'), $dsp2)
            ->withSubtreeTagAndDimensionSpacePoint(SubtreeTag::fromString('tag2'), $dsp1);

        self::assertTrue($dimensionSpacePointsBySubtreeTags->forSubtreeTag(SubtreeTag::fromString('tag2'))->equals(DimensionSpacePointSet::fromArray([$dsp1, $dsp2])));
    }
}
