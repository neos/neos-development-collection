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
use TYPO3\TypoScript\Core\Cache\ContentCache;

/**
 * Test case for the ContentCache
 */
class ContentCacheTest extends UnitTestCase {

	/**
	 * @return array
	 */
	public function tags() {
		return array(
			array('Everything', 'Everything'),
			array('Node_f6dc5e8e-03d9-306f-1572-92ab7a7bc4ef', 'Node_f6dc5e8e-03d9-306f-1572-92ab7a7bc4ef'),
			array('NodeType_TYPO3.Neos:Page', 'NodeType_TYPO3_Neos-Page'),
			array('DescendentOf_f6dc5e8e-03d9-306f-1572-92ab7a7bc4ef', 'DescendentOf_f6dc5e8e-03d9-306f-1572-92ab7a7bc4ef')
		);
	}

	/**
	 * @dataProvider tags()
	 * @test
	 */
	public function flushByTagSanitizesTagsForCacheFrontend($tag, $sanitizedTag) {
		$mockCache = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\StringFrontend')->disableOriginalConstructor()->getMock();
		$mockCache->expects($this->once())->method('flushByTag')->with($sanitizedTag);
		$contentCache = new ContentCache();
		$this->inject($contentCache, 'cache', $mockCache);
		$contentCache->flushByTag($tag);
	}

}
