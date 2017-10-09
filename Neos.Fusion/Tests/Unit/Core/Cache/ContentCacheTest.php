<?php
namespace Neos\Fusion\Tests\Unit\Core\Cache;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\TransientMemoryBackend;
use Neos\Cache\CacheAwareInterface;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Security\Context;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Core\Cache\CacheSegmentParser;
use Neos\Fusion\Core\Cache\ContentCache;

/**
 * Test case for the ContentCache
 */
class ContentCacheTest extends UnitTestCase
{

    /**
     * @return array
     */
    public function tags()
    {
        return array(
            array('Everything', 'Everything'),
            array('Node_f6dc5e8e-03d9-306f-1572-92ab7a7bc4ef', 'Node_f6dc5e8e-03d9-306f-1572-92ab7a7bc4ef'),
            array('NodeType_Neos.NodeTypes:Page', 'NodeType_Neos_NodeTypes-Page'),
            array(
                'DescendentOf_f6dc5e8e-03d9-306f-1572-92ab7a7bc4ef',
                'DescendentOf_f6dc5e8e-03d9-306f-1572-92ab7a7bc4ef'
            )
        );
    }

    /**
     * @dataProvider tags()
     * @test
     */
    public function flushByTagSanitizesTagsForCacheFrontend($tag, $sanitizedTag)
    {
        $mockCache = $this->getMockBuilder(StringFrontend::class)->disableOriginalConstructor()->getMock();
        $mockCache->expects($this->once())->method('flushByTag')->with($sanitizedTag);
        $contentCache = new ContentCache();
        $this->inject($contentCache, 'cache', $mockCache);
        $contentCache->flushByTag($tag);
    }

    /**
     * @return array
     */
    public function invalidEntryIdentifierValues()
    {
        return array(
            'object not implementing CacheAwareInterface' => array(array('foo' => new \stdClass()))
        );
    }

    /**
     * @test
     * @dataProvider invalidEntryIdentifierValues
     * @expectedException \Neos\Fusion\Exception\CacheException
     * @expectedExceptionCode 1395846615
     */
    public function createCacheSegmentWithInvalidEntryIdentifierValueThrowsException($entryIdentifierValues)
    {
        $contentCache = new ContentCache();
        $mockSecurityContext = $this->createMock(Context::class);
        $this->inject($contentCache, 'securityContext', $mockSecurityContext);
        $contentCache->createCacheSegment('My content', '/foo/bar', $entryIdentifierValues);
    }

    /**
     * @return array
     */
    public function validEntryIdentifierValues()
    {
        $mockCacheAware = $this->createMock(CacheAwareInterface::class);
        return array(
            'string value' => array(array('foo' => 'Bar')),
            'boolean value' => array(array('foo' => true)),
            'integer value' => array(array('foo' => 42)),
            'object implementing CacheAwareInterface' => array(array('foo' => $mockCacheAware)),
            'null' => array(array('foo' => null))
        );
    }

    /**
     * @test
     * @dataProvider validEntryIdentifierValues
     */
    public function createCacheSegmentWithValidEntryIdentifierValueCreatesIdentifier($entryIdentifierValues)
    {
        $contentCache = new ContentCache();
        $mockSecurityContext = $this->createMock(Context::class);
        $this->inject($contentCache, 'securityContext', $mockSecurityContext);
        $segement = $contentCache->createCacheSegment('My content', '/foo/bar', $entryIdentifierValues);
        $this->assertNotEmpty($segement);
    }

    /**
     * @test
     */
    public function createCacheSegmentWithLifetimeStoresLifetimeAfterTagsInMetadata()
    {
        $contentCache = new ContentCache();
        $mockSecurityContext = $this->createMock(Context::class);
        $this->inject($contentCache, 'securityContext', $mockSecurityContext);
        $segement = $contentCache->createCacheSegment('My content', '/foo/bar', array(42), array('Foo', 'Bar'), 60);
        $this->assertContains('Foo,Bar;60' . ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN, $segement);
    }

    /**
     * @test
     */
    public function processCacheSegmentsSetsLifetimeFromMetadata()
    {
        $contentCache = new ContentCache();
        $mockSecurityContext = $this->createMock(Context::class);
        $this->inject($contentCache, 'securityContext', $mockSecurityContext);

        $mockCache = $this->createMock(FrontendInterface::class);
        $this->inject($contentCache, 'cache', $mockCache);

        $segement = $contentCache->createCacheSegment('My content', '/foo/bar', array(42), array('Foo', 'Bar'), 60);

        $mockCache->expects($this->once())->method('set')->with($this->anything(), $this->anything(), $this->anything(),
            60);

        $contentCache->processCacheSegments($segement);
    }

    /**
     * @test
     */
    public function createCacheSegmentAndProcessCacheSegmentsDoesWorkWithCacheSegmentTokensInContent()
    {
        $contentCache = new ContentCache();
        $mockSecurityContext = $this->createMock(Context::class);
        $this->inject($contentCache, 'securityContext', $mockSecurityContext);

        $mockCache = $this->createMock(FrontendInterface::class);
        $this->inject($contentCache, 'cache', $mockCache);

        $invalidContent = 'You should probably not use ' . ContentCache::CACHE_SEGMENT_START_TOKEN . ', ' . ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN . ' or ' . ContentCache::CACHE_SEGMENT_END_TOKEN . ' inside your content.';

        $content = $contentCache->createCacheSegment($invalidContent, 'some.fusionh.path', array('node' => 'foo'),
            array('mytag1', 'mytag2'));

        $validContent = 'But the cache should not fail because of it.';

        $content .= $contentCache->createCacheSegment($validContent, 'another.fusionh.path', array('node' => 'bar'),
            array('mytag2'), 86400);

        $mockCache->expects($this->at(0))->method('set')->with($this->anything(), $invalidContent,
            array('mytag1', 'mytag2'), null);
        $mockCache->expects($this->at(1))->method('set')->with($this->anything(), $validContent, array('mytag2'),
            86400);

        $output = $contentCache->processCacheSegments($content);

        $this->assertSame($invalidContent . $validContent, $output);
    }

    /**
     * @test
     */
    public function createUncachedSegmentAndProcessCacheSegmentsDoesWorkWithCacheSegmentTokensInContent()
    {
        $contentCache = new ContentCache();

        $mockPropertyMapper = $this->createMock(PropertyMapper::class);
        $mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnArgument(0));
        $this->inject($contentCache, 'propertyMapper', $mockPropertyMapper);

        $mockCache = $this->createMock(FrontendInterface::class);
        $this->inject($contentCache, 'cache', $mockCache);

        $invalidContent = 'You should probably not use ' . ContentCache::CACHE_SEGMENT_START_TOKEN . ', ' . ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN . ' or ' . ContentCache::CACHE_SEGMENT_END_TOKEN . ' inside your uncached content.';

        $content = $contentCache->createUncachedSegment($invalidContent, 'uncached.fusion.path',
            array('node' => 'A node identifier'));

        $output = $contentCache->processCacheSegments($content);

        $this->assertSame($invalidContent, $output);
    }

    /**
     * @test
     */
    public function getCachedSegmentWithExistingCacheEntryReplacesNestedCachedSegments()
    {
        $contentCache = new ContentCache();

        $mockSecurityContext = $this->createMock(Context::class);
        $this->inject($contentCache, 'securityContext', $mockSecurityContext);

        $mockPropertyMapper = $this->createMock(PropertyMapper::class);
        $mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnArgument(0));
        $this->inject($contentCache, 'propertyMapper', $mockPropertyMapper);

        $mockContext = $this->getMockBuilder(EnvironmentConfiguration::class)->disableOriginalConstructor()->getMock();
        $cacheBackend = new TransientMemoryBackend($mockContext);
        $cacheFrontend = new StringFrontend('foo', $cacheBackend);
        $cacheBackend->setCache($cacheFrontend);
        $this->inject($contentCache, 'cache', $cacheFrontend);

        $invalidContent = 'You should probably not use ' . ContentCache::CACHE_SEGMENT_START_TOKEN . ', ' . ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN . ' or ' . ContentCache::CACHE_SEGMENT_END_TOKEN . ' inside your content.';

        $innerCachedContent = $contentCache->createCacheSegment($invalidContent, 'some.fusionh.path.innerCached',
            array('node' => 'foo'), array('mytag1', 'mytag2'));

        $uncachedCommandOutput = 'This content is highly dynamic with ' . ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN . ' and ' . ContentCache::CACHE_SEGMENT_END_TOKEN;
        $innerUncachedContent = $contentCache->createUncachedSegment($uncachedCommandOutput,
            'some.fusionh.path.innerUncached', array('node' => 'A node identifier'));

        $outerContentStart = 'You can nest cached segments like <';
        $outerContentMiddle = '> or uncached segments like <';
        $outerContentEnd = '> inside other segments.';

        $outerContent = $outerContentStart . $innerCachedContent . $outerContentMiddle . $innerUncachedContent . $outerContentEnd;

        $content = $contentCache->createCacheSegment($outerContent, 'some.fusionh.path', array('node' => 'bar'),
            array('mytag2'), 86400);
        $output = $contentCache->processCacheSegments($content);

        $expectedOutput = $outerContentStart . $invalidContent . $outerContentMiddle . $uncachedCommandOutput . $outerContentEnd;

        $this->assertSame($expectedOutput, $output);

        $cachedContent = $contentCache->getCachedSegment(function ($command) use ($uncachedCommandOutput) {
            if ($command === 'eval=some.fusionh.path.innerUncached') {
                return $uncachedCommandOutput;
            } else {
                $this->fail('Unexpected command: ' . $command);
            }
        }, 'some.fusionh.path', array('node' => 'bar'));

        $this->assertSame($expectedOutput, $cachedContent);
    }
}
