<?php
namespace TYPO3\TypoScript\Tests\Unit\Core\Cache;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\TypoScript\Core\Cache\CacheSegmentParser;
use TYPO3\TypoScript\Core\Cache\ContentCache;

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
            array('NodeType_TYPO3.Neos.NodeTypes:Page', 'NodeType_TYPO3_Neos_NodeTypes-Page'),
            array('DescendentOf_f6dc5e8e-03d9-306f-1572-92ab7a7bc4ef', 'DescendentOf_f6dc5e8e-03d9-306f-1572-92ab7a7bc4ef')
        );
    }

    /**
     * @dataProvider tags()
     * @test
     */
    public function flushByTagSanitizesTagsForCacheFrontend($tag, $sanitizedTag)
    {
        $mockCache = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\StringFrontend')->disableOriginalConstructor()->getMock();
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
     * @expectedException \TYPO3\TypoScript\Exception\CacheException
     * @expectedExceptionCode 1395846615
     */
    public function createCacheSegmentWithInvalidEntryIdentifierValueThrowsException($entryIdentifierValues)
    {
        $contentCache = new ContentCache();
        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
        $this->inject($contentCache, 'securityContext', $mockSecurityContext);
        $contentCache->createCacheSegment('My content', '/foo/bar', $entryIdentifierValues);
    }

    /**
     * @return array
     */
    public function validEntryIdentifierValues()
    {
        $mockCacheAware = $this->getMock('TYPO3\Flow\Cache\CacheAwareInterface');
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
        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
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
        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
        $this->inject($contentCache, 'securityContext', $mockSecurityContext);
        $segement = $contentCache->createCacheSegment('My content', '/foo/bar', array(42), array('Foo', 'Bar'), 60);
        $this->assertContains(ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN . 'Foo,Bar;60' . ContentCache::CACHE_SEGMENT_SEPARATOR_TOKEN, $segement);
    }

    /**
     * @test
     */
    public function processCacheSegmentsSetsLifetimeFromMetadata()
    {
        $contentCache = new ContentCache();
        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
        $this->inject($contentCache, 'securityContext', $mockSecurityContext);
        $this->inject($contentCache, 'parser', new CacheSegmentParser());

        $mockCache = $this->getMock('TYPO3\Flow\Cache\Frontend\FrontendInterface');
        $this->inject($contentCache, 'cache', $mockCache);

        $segement = $contentCache->createCacheSegment('My content', '/foo/bar', array(42), array('Foo', 'Bar'), 60);

        $mockCache->expects($this->once())->method('set')->with($this->anything(), $this->anything(), $this->anything(), 60);

        $contentCache->processCacheSegments($segement);
    }
}
