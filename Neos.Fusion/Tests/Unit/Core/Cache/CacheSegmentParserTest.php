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

use Neos\Flow\Tests\UnitTestCase;
use Neos\Fusion\Core\Cache\CacheSegmentParser;

/**
 * Test case for the CacheSegmentParser
 */
class CacheSegmentParserTest extends UnitTestCase
{
    protected $content = "
		outer content
		\x02123456789\x1fAllDocumentNodes\x1ffoo bar baz
			bar bar \x022345678901\x1fChildOf_abcd-efgh-1234-5678,AllNodes\x1florem ipsum\x03
			foo foo \x023456789012\x1f\x1fipsum lorem\x03
			baz baz \x024567890123\x1f*\x1fdolor
				\x025678901234\x1f\x1fcool stuff\x03
			sit\x03\x03
		with some text
		\x026789012345\x1fAllNodes\x1ffooter\x03
	";

    protected $expectedOutput = '
		outer content
		foo bar baz
			bar bar lorem ipsum
			foo foo ipsum lorem
			baz baz dolor
				cool stuff
			sit
		with some text
		footer
	';

    protected $expectedOuterContent = "
		outer content
		\x02CONTENT_CACHE123456789\x03CONTENT_CACHE
		with some text
		\x02CONTENT_CACHE6789012345\x03CONTENT_CACHE
	";

    protected $expectedEntries = array(
        '2345678901' => array(
            'content' => 'lorem ipsum',
            'identifier' => '2345678901',
            'type' => 'cached',
            'metadata' => 'ChildOf_abcd-efgh-1234-5678,AllNodes'
        ),

        '3456789012' => array(
            'content' => 'ipsum lorem',
            'identifier' => '3456789012',
            'type' => 'cached',
            'metadata' => ''
        ),

        '5678901234' => array(
            'content' => 'cool stuff',
            'identifier' => '5678901234',
            'type' => 'cached',
            'metadata' => ''
        ),

        '4567890123' => array(
            'content' => "dolor
				\x02CONTENT_CACHE5678901234\x03CONTENT_CACHE
			sit",
            'identifier' => '4567890123',
            'type' => 'cached',
            'metadata' => '*'
        ),

        '123456789' => array(
            'identifier' => '123456789',
            'type' => 'cached',
            'content' => "foo bar baz
			bar bar \x02CONTENT_CACHE2345678901\x03CONTENT_CACHE
			foo foo \x02CONTENT_CACHE3456789012\x03CONTENT_CACHE
			baz baz \x02CONTENT_CACHE4567890123\x03CONTENT_CACHE",
            'metadata' => 'AllDocumentNodes'
        ),

        '6789012345' => array(
            'identifier' => '6789012345',
            'type' => 'cached',
            'content' => 'footer',
            'metadata' => 'AllNodes'
        )
    );

    protected $invalidContentWithMissingEnd = "
		outer content
		\x021234567890\x1ffoo bar baz
	";

    protected $invalidContentWithExceedingEnd = "
		outer content
		\x021234567890\x1f\x1ffoo bar baz
			baz baz \x024567890123\x1f\x1ffoo\x03
		<\x03><\x03>
	";

    protected $invalidContentWithMissingSeparator = "
		outer content
		\x021234567890foo bar baz\x03
		\x024567890123\x1ffoo\x03
	";

    protected $contentWithUncachedSegments = "
		outer content
		\x02123456789\x1fAllDocumentNodes\x1ffoo bar baz
			baz baz \x024567890123\x1f*\x1fdolor
				\x02eval=foo/bar\x1f{}\x1funcached content #1\x03
			sit\x03\x03
		with some text
		\x02eval=bar/baz\x1f{\"node\":\"/sites/demo/home\"}\x1funcached content #2 \x02eval=foo/bar/baz\x1f{}\x1fwith uncached content\x03\x03
		\x026789012345\x1fAllNodes\x1ffooter\x03
	";

    protected $expectedOutputWithUncachedSegments = '
		outer content
		foo bar baz
			baz baz dolor
				uncached content #1
			sit
		with some text
		uncached content #2 with uncached content
		footer
	';

    protected $expectedOuterContentWithUncachedSegments = "
		outer content
		\x02CONTENT_CACHE123456789\x03CONTENT_CACHE
		with some text
		\x02CONTENT_CACHEeval=bar/baz\x1fCONTENT_CACHE{\"node\":\"/sites/demo/home\"}\x03CONTENT_CACHE
		\x02CONTENT_CACHE6789012345\x03CONTENT_CACHE
	";

    protected $expectedEntriesWithUncachedSegments = array(
        '4567890123' => array(
            'content' => "dolor
				\x02CONTENT_CACHEeval=foo/bar\x1fCONTENT_CACHE{}\x03CONTENT_CACHE
			sit",
            'identifier' => '4567890123',
            'type' => 'cached',
            'metadata' => '*'
        ),

        '123456789' => array(
            'identifier' => '123456789',
            'type' => 'cached',
            'content' => "foo bar baz
			baz baz \x02CONTENT_CACHE4567890123\x03CONTENT_CACHE",
            'metadata' => 'AllDocumentNodes'
        ),

        '6789012345' => array(
            'identifier' => '6789012345',
            'type' => 'cached',
            'content' => 'footer',
            'metadata' => 'AllNodes'
        )
    );

    /**
     * @test
     */
    public function getOutputAfterExtractReturnsOriginalTextWithoutAnnotations()
    {
        $parser = new CacheSegmentParser();
        $parser->extractRenderedSegments($this->content);

        $output = $parser->getOutput();

        $this->assertEquals($this->expectedOutput, $output);
    }

    /**
     * @test
     */
    public function extractReturnsOuterContentWithPlaceholders()
    {
        $parser = new CacheSegmentParser();
        $outerContent = $parser->extractRenderedSegments($this->content);

        $this->assertEquals($this->expectedOuterContent, $outerContent);
    }

    /**
     * @test
     */
    public function getCacheEntriesAfterExtractReturnsInnerContentWithPlaceholders()
    {
        $parser = new CacheSegmentParser();
        $parser->extractRenderedSegments($this->content);

        $entries = $parser->getCacheSegments();

        $this->assertEquals($this->expectedEntries, $entries);
    }

    /**
     * @test
     * @expectedException \Neos\Fusion\Exception
     * @expectedExceptionCode 1391853500
     */
    public function invalidContentWithMissingEndThrowsException()
    {
        $parser = new CacheSegmentParser();
        $parser->extractRenderedSegments($this->invalidContentWithMissingEnd);
    }

    /**
     * @test
     * @expectedException \Neos\Fusion\Exception
     * @expectedExceptionCode 1391853689
     */
    public function invalidContentWithExceedingEndThrowsException()
    {
        $parser = new CacheSegmentParser();
        $parser->extractRenderedSegments($this->invalidContentWithExceedingEnd);
    }

    /**
     * @test
     * @expectedException \Neos\Fusion\Exception
     * @expectedExceptionCode 1391855139
     */
    public function invalidContentWithMissingSeparatorThrowsException()
    {
        $parser = new CacheSegmentParser();
        $parser->extractRenderedSegments($this->invalidContentWithMissingSeparator);
    }

    /**
     * @test
     */
    public function extractWithUncachedSegmentsReturnsOuterContentWithPlaceholders()
    {
        $parser = new CacheSegmentParser();
        $outerContent = $parser->extractRenderedSegments($this->contentWithUncachedSegments);

        $this->assertEquals($this->expectedOuterContentWithUncachedSegments, $outerContent);
    }

    /**
     * @test
     */
    public function getOutputAfterExtractWithUncachedSegmentsReturnsOriginalTextWithoutAnnotations()
    {
        $parser = new CacheSegmentParser();
        $parser->extractRenderedSegments($this->contentWithUncachedSegments);

        $output = $parser->getOutput();

        $this->assertEquals($this->expectedOutputWithUncachedSegments, $output);
    }

    /**
     * @test
     */
    public function getCacheSegmentsAfterExtractWithUncachedSegmentsReturnsContentWithPlaceholder()
    {
        $parser = new CacheSegmentParser();
        $parser->extractRenderedSegments($this->contentWithUncachedSegments);

        $entries = $parser->getCacheSegments();

        $this->assertEquals($this->expectedEntriesWithUncachedSegments, $entries);
    }
}
