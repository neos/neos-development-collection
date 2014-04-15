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

/**
 * Test case for the CacheSegmentParser
 */
class CacheSegmentParserTest extends UnitTestCase {

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
		\x02123456789\x03
		with some text
		\x026789012345\x03
	";

	protected $expectedEntries = array(
		'2345678901' => array(
			'content' => 'lorem ipsum',
			'identifier' => '2345678901',
			'type' => 'cached',
			'tags' => array('ChildOf_abcd-efgh-1234-5678', 'AllNodes')
		),

		'3456789012' => array(
			'content' => 'ipsum lorem',
			'identifier' => '3456789012',
			'type' => 'cached',
			'tags' => array()
		),

		'5678901234' => array(
			'content' => 'cool stuff',
			'identifier' => '5678901234',
			'type' => 'cached',
			'tags' => array()
		),

		'4567890123' => array(
			'content' => "dolor
				\x025678901234\x03
			sit",
			'identifier' => '4567890123',
			'type' => 'cached',
			'tags' => FALSE
		),

		'123456789' => array(
			'identifier' => '123456789',
			'type' => 'cached',
			'content' => "foo bar baz
			bar bar \x022345678901\x03
			foo foo \x023456789012\x03
			baz baz \x024567890123\x03",
			'tags' => array('AllDocumentNodes')
		),

		'6789012345' => array(
			'identifier' => '6789012345',
			'type' => 'cached',
			'content' => 'footer',
			'tags' => array('AllNodes')
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
		\x02123456789\x03
		with some text
		\x02eval=bar/baz\x1f{\"node\":\"/sites/demo/home\"}\x03
		\x026789012345\x03
	";

	protected $expectedEntriesWithUncachedSegments = array(
		'4567890123' => array(
			'content' => "dolor
				\x02eval=foo/bar\x1f{}\x03
			sit",
			'identifier' => '4567890123',
			'type' => 'cached',
			'tags' => FALSE
		),

		'123456789' => array(
			'identifier' => '123456789',
			'type' => 'cached',
			'content' => "foo bar baz
			baz baz \x024567890123\x03",
			'tags' => array('AllDocumentNodes')
		),

		'6789012345' => array(
			'identifier' => '6789012345',
			'type' => 'cached',
			'content' => 'footer',
			'tags' => array('AllNodes')
		)
	);

	/**
	 * @test
	 */
	public function getOutputAfterExtractReturnsOriginalTextWithoutAnnotations() {
		$parser = new CacheSegmentParser();
		$parser->extractRenderedSegments($this->content);

		$output = $parser->getOutput();

		$this->assertEquals($this->expectedOutput, $output);
	}

	/**
	 * @test
	 */
	public function extractReturnsOuterContentWithPlaceholders() {
		$parser = new CacheSegmentParser();
		$outerContent = $parser->extractRenderedSegments($this->content);

		$this->assertEquals($this->expectedOuterContent, $outerContent);
	}

	/**
	 * @test
	 */
	public function getCacheEntriesAfterExtractReturnsInnerContentWithPlaceholders() {
		$parser = new CacheSegmentParser();
		$parser->extractRenderedSegments($this->content);

		$entries = $parser->getCacheSegments();

		$this->assertEquals($this->expectedEntries, $entries);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 * @expectedExceptionCode 1391853500
	 */
	public function invalidContentWithMissingEndThrowsException() {
		$parser = new CacheSegmentParser();
		$parser->extractRenderedSegments($this->invalidContentWithMissingEnd);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 * @expectedExceptionCode 1391853689
	 */
	public function invalidContentWithExceedingEndThrowsException() {
		$parser = new CacheSegmentParser();
		$parser->extractRenderedSegments($this->invalidContentWithExceedingEnd);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TypoScript\Exception
	 * @expectedExceptionCode 1391855139
	 */
	public function invalidContentWithMissingSeparatorThrowsException() {
		$parser = new CacheSegmentParser();
		$parser->extractRenderedSegments($this->invalidContentWithMissingSeparator);
	}

	/**
	 * @test
	 */
	public function extractWithUncachedSegmentsReturnsOuterContentWithPlaceholders() {
		$parser = new CacheSegmentParser();
		$outerContent = $parser->extractRenderedSegments($this->contentWithUncachedSegments);

		$this->assertEquals($this->expectedOuterContentWithUncachedSegments, $outerContent);
	}

	/**
	 * @test
	 */
	public function getOutputAfterExtractWithUncachedSegmentsReturnsOriginalTextWithoutAnnotations() {
		$parser = new CacheSegmentParser();
		$parser->extractRenderedSegments($this->contentWithUncachedSegments);

		$output = $parser->getOutput();

		$this->assertEquals($this->expectedOutputWithUncachedSegments, $output);
	}

	/**
	 * @test
	 */
	public function getCacheSegmentsAfterExtractWithUncachedSegmentsReturnsContentWithPlaceholder() {
		$parser = new CacheSegmentParser();
		$parser->extractRenderedSegments($this->contentWithUncachedSegments);

		$entries = $parser->getCacheSegments();

		$this->assertEquals($this->expectedEntriesWithUncachedSegments, $entries);
	}

}
