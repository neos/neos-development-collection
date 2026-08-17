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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Neos\Cache\Backend\TransientMemoryBackend;
use Neos\Cache\CacheAwareInterface;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Security\Context;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Core\Cache\ContentCache;
use Neos\Fusion\Exception\CacheException;

/**
 * Test case for the ContentCache
 */
class ContentCacheTest extends UnitTestCase
{
    /**
     * @return array
     */
    public static function tags()
    {
        return [
            ['Everything', 'Everything'],
            ['Node_f6dc5e8e-03d9-306f-1572-92ab7a7bc4ef', 'Node_f6dc5e8e-03d9-306f-1572-92ab7a7bc4ef'],
            ['NodeType_Acme.Com:Page', 'NodeType_Acme_Com-Page'],
            [
                'DescendentOf_f6dc5e8e-03d9-306f-1572-92ab7a7bc4ef',
                'DescendentOf_f6dc5e8e-03d9-306f-1572-92ab7a7bc4ef'
            ]
        ];
    }

    #[DataProvider('tags')]
    #[Test]
    public function flushByTagSanitizesTagsForCacheFrontend($tag, $sanitizedTag)
    {
        $mockCache = $this->getMockBuilder(StringFrontend::class)->disableOriginalConstructor()->getMock();
        $mockCache->expects(self::once())->method('flushByTag')->with($sanitizedTag);
        $contentCache = new ContentCache();
        $this->inject($contentCache, 'cache', $mockCache);
        $contentCache->flushByTag($tag);
    }

    /**
     * @return array
     */
    public static function invalidEntryIdentifierValues()
    {
        return [
            'object not implementing CacheAwareInterface' => [['foo' => new \stdClass()]]
        ];
    }

    #[DataProvider('invalidEntryIdentifierValues')]
    #[Test]
    public function createCacheSegmentWithInvalidEntryIdentifierValueThrowsException($entryIdentifierValues)
    {
        $this->expectException(CacheException::class);
        $this->expectExceptionCode(1395846615);
        $contentCache = new ContentCache();
        $mockSecurityContext = $this->createMock(Context::class);
        $this->inject($contentCache, 'securityContext', $mockSecurityContext);
        $contentCache->createCacheSegment('My content', '/foo/bar', $entryIdentifierValues);
    }

    /**
     * @return array
     */
    public static function validEntryIdentifierValues()
    {
        return [
            'string value' => [['foo' => 'Bar']],
            'boolean value' => [['foo' => true]],
            'integer value' => [['foo' => 42]],
            // data providers must be static and cannot build mocks, so the test method
            // replaces this marker with a CacheAwareInterface mock
            'object implementing CacheAwareInterface' => [['foo' => '__mock:CacheAwareInterface']],
            'null' => [['foo' => null]]
        ];
    }

    #[DataProvider('validEntryIdentifierValues')]
    #[Test]
    public function createCacheSegmentWithValidEntryIdentifierValueCreatesIdentifier($entryIdentifierValues)
    {
        if (($entryIdentifierValues['foo'] ?? null) === '__mock:CacheAwareInterface') {
            $entryIdentifierValues['foo'] = $this->createMock(CacheAwareInterface::class);
        }
        $contentCache = new ContentCache();
        $mockSecurityContext = $this->createMock(Context::class);
        $this->inject($contentCache, 'securityContext', $mockSecurityContext);
        $segement = $contentCache->createCacheSegment('My content', '/foo/bar', $entryIdentifierValues);
        self::assertNotEmpty($segement);
    }

    #[Test]
    public function createCacheSegmentWithLifetimeStoresLifetimeAfterTagsInMetadata()
    {
        $contentCache = new ContentCache();
        $mockSecurityContext = $this->createMock(Context::class);
        $this->inject($contentCache, 'securityContext', $mockSecurityContext);
        $segment = $contentCache->createCacheSegment('My content', '/foo/bar', [42], ['Foo', 'Bar'], 60);
        self::assertStringContainsString('Foo,Bar;60' . ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN, $segment);
    }

    #[Test]
    public function processCacheSegmentsSetsLifetimeFromMetadata()
    {
        $contentCache = new ContentCache();
        $mockSecurityContext = $this->createMock(Context::class);
        $this->inject($contentCache, 'securityContext', $mockSecurityContext);

        $mockCache = $this->createMock(FrontendInterface::class);
        $this->inject($contentCache, 'cache', $mockCache);

        $segement = $contentCache->createCacheSegment('My content', '/foo/bar', [42], ['Foo', 'Bar'], 60);

        $mockCache->expects(self::once())->method('set')->with(
            $this->anything(),
            $this->anything(),
            $this->anything(),
            60
        );

        $contentCache->processCacheSegments($segement);
    }

    #[Test]
    public function createCacheSegmentAndProcessCacheSegmentsDoesWorkWithCacheSegmentTokensInContent()
    {
        $contentCache = new ContentCache();
        $mockSecurityContext = $this->createMock(Context::class);
        $this->inject($contentCache, 'securityContext', $mockSecurityContext);

        $mockCache = $this->createMock(FrontendInterface::class);
        $this->inject($contentCache, 'cache', $mockCache);

        $invalidContent = 'You should probably not use ' . ContentCache::CACHE_SEGMENT_START_TOKEN . ', ' . ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN . ' or ' . ContentCache::CACHE_SEGMENT_END_TOKEN . ' inside your content.';

        $content = $contentCache->createCacheSegment(
            $invalidContent,
            'some.fusionh.path',
            ['node' => 'foo'],
            ['mytag1', 'mytag2']
        );

        $validContent = 'But the cache should not fail because of it.';

        $content .= $contentCache->createCacheSegment(
            $validContent,
            'another.fusionh.path',
            ['node' => 'bar'],
            ['mytag2'],
            86400
        );
        $matcher = self::atLeast(2);
        $mockCache->expects($matcher)
            ->method('set')->willReturnCallback(function (...$parameters) use ($matcher, $invalidContent, $validContent) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame($invalidContent, $parameters[1]);
                $this->assertSame(['mytag1', 'mytag2'], $parameters[2]);
                $this->assertSame(null, $parameters[3]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame($validContent, $parameters[1]);
                $this->assertSame(['mytag2'], $parameters[2]);
                $this->assertSame(86400, $parameters[3]);
            }
        });

        $output = $contentCache->processCacheSegments($content);

        self::assertSame($invalidContent . $validContent, $output);
    }

    #[Test]
    public function createUncachedSegmentAndProcessCacheSegmentsDoesWorkWithCacheSegmentTokensInContent()
    {
        $contentCache = new ContentCache();

        $mockPropertyMapper = $this->createMock(PropertyMapper::class);
        $mockPropertyMapper->expects(self::any())->method('convert')->willReturnArgument(0);
        $this->inject($contentCache, 'propertyMapper', $mockPropertyMapper);

        $mockCache = $this->createMock(FrontendInterface::class);
        $this->inject($contentCache, 'cache', $mockCache);

        $invalidContent = 'You should probably not use ' . ContentCache::CACHE_SEGMENT_START_TOKEN . ', ' . ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN . ' or ' . ContentCache::CACHE_SEGMENT_END_TOKEN . ' inside your uncached content.';

        $content = $contentCache->createUncachedSegment(
            $invalidContent,
            'uncached.fusion.path',
            ['node' => 'A node identifier']
        );

        $output = $contentCache->processCacheSegments($content);

        self::assertSame($invalidContent, $output);
    }

    #[Test]
    public function getCachedSegmentWithExistingCacheEntryReplacesNestedCachedSegments()
    {
        $contentCache = new ContentCache();

        $mockSecurityContext = $this->createMock(Context::class);
        $this->inject($contentCache, 'securityContext', $mockSecurityContext);

        $mockPropertyMapper = $this->createMock(PropertyMapper::class);
        $mockPropertyMapper->expects(self::any())->method('convert')->willReturnArgument(0);
        $this->inject($contentCache, 'propertyMapper', $mockPropertyMapper);

        $mockContext = $this->getMockBuilder(EnvironmentConfiguration::class)->disableOriginalConstructor()->getMock();
        $cacheBackend = new TransientMemoryBackend($mockContext);
        $cacheFrontend = new StringFrontend('foo', $cacheBackend);
        $cacheBackend->setCache($cacheFrontend);
        $this->inject($contentCache, 'cache', $cacheFrontend);

        $invalidContent = 'You should probably not use ' . ContentCache::CACHE_SEGMENT_START_TOKEN . ', ' . ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN . ' or ' . ContentCache::CACHE_SEGMENT_END_TOKEN . ' inside your content.';

        $innerCachedContent = $contentCache->createCacheSegment(
            $invalidContent,
            'some.fusionh.path.innerCached',
            ['node' => 'foo'],
            ['mytag1', 'mytag2']
        );

        $uncachedCommandOutput = 'This content is highly dynamic with ' . ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN . ' and ' . ContentCache::CACHE_SEGMENT_END_TOKEN;
        $innerUncachedContent = $contentCache->createUncachedSegment(
            $uncachedCommandOutput,
            'some.fusionh.path.innerUncached',
            ['node' => 'A node identifier']
        );

        $outerContentStart = 'You can nest cached segments like <';
        $outerContentMiddle = '> or uncached segments like <';
        $outerContentEnd = '> inside other segments.';

        $outerContent = $outerContentStart . $innerCachedContent . $outerContentMiddle . $innerUncachedContent . $outerContentEnd;

        $content = $contentCache->createCacheSegment(
            $outerContent,
            'some.fusionh.path',
            ['node' => 'bar'],
            ['mytag2'],
            86400
        );
        $output = $contentCache->processCacheSegments($content);

        $expectedOutput = $outerContentStart . $invalidContent . $outerContentMiddle . $uncachedCommandOutput . $outerContentEnd;

        self::assertSame($expectedOutput, $output);

        $cachedContent = $contentCache->getCachedSegment(function ($command) use ($uncachedCommandOutput) {
            if ($command === 'eval=some.fusionh.path.innerUncached') {
                return $uncachedCommandOutput;
            } else {
                $this->fail('Unexpected command: ' . $command);
            }
        }, 'some.fusionh.path', ['node' => 'bar']);

        self::assertSame($expectedOutput, $cachedContent);
    }
}
