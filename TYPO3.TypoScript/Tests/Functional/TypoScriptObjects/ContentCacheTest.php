<?php
namespace TYPO3\TypoScript\Tests\Functional\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TypoScript\Core\Cache\ContentCache;
use TYPO3\TypoScript\Tests\Functional\TypoScriptObjects\Fixtures\Model\TestModel;

/**
 * Test case for the TypoScript ContentCache
 */
class ContentCacheTest extends AbstractTypoScriptObjectTest {

	/**
	 * @var ContentCache
	 */
	protected $contentCache;

	public function setUp() {
		parent::setUp();
		$this->contentCache = $this->objectManager->get('TYPO3\TypoScript\Core\Cache\ContentCache');
		$this->contentCache->flush();
	}

	/**
	 * @test
	 */
	public function renderCachedSegmentTwiceYieldsSameResult() {
		$object = new TestModel(42, 'Object value 1');

		$view = $this->buildView();
		$view->setOption('enableContentCache', TRUE);
		$view->assign('object', $object);
		$view->setTypoScriptPath('contentCache/cachedSegment');

			// This render call should create the cache entry
		$firstRenderResult = $view->render();

		$object->setValue('Object value 2');

			// And this render call should use the existing cache entry
		$secondRenderResult = $view->render();

		$this->assertSame('Cached segment|Object value 1', $firstRenderResult);
		$this->assertSame($firstRenderResult, $secondRenderResult);
	}

	/**
	 * @test
	 */
	public function cacheUsesContextValuesAsDefaultCacheIdentifier() {
		$object1 = new TestModel(42, 'Object value 1');
		$object2 = new TestModel(21, 'Object value 2');

		$view = $this->buildView();
		$view->setOption('enableContentCache', TRUE);
		$view->setTypoScriptPath('contentCache/cachedSegment');

		$view->assign('object', $object1);
		$firstRenderResult = $view->render();
			// Render again to use the cached segment (assert that the Runtime needs to be in a correct state for the next render)
		$secondRenderResult = $view->render();

		$this->assertSame('Cached segment|Object value 1', $firstRenderResult);
		$this->assertSame($firstRenderResult, $secondRenderResult);

		$view->assign('object', $object2);
		$anotherObjectRenderResult = $view->render();

		$this->assertSame('Cached segment|Object value 2', $anotherObjectRenderResult);
	}

	/**
	 * @test
	 */
	public function nestedCacheSegmentsAreFetchedFromCache() {
		$object = new TestModel(42, 'Object value 1');

		$view = $this->buildView();
		$view->setOption('enableContentCache', TRUE);
		$view->setTypoScriptPath('contentCache/nestedCacheSegments');

		$view->assign('site', 'site1');
		$view->assign('object', $object);

		$firstRenderResult = $view->render();

		$this->assertSame('Outer segment|site=site1|Inner segment|object=Object value 1|End inner|End outer', $firstRenderResult);

			// This must not influence the output, since the inner segment should be fetched from cache
		$object->setValue('Object value 2');

		$view->assign('site', 'site2');
		$secondRenderResult = $view->render();
		$this->assertSame('Outer segment|site=site2|Inner segment|object=Object value 1|End inner|End outer', $secondRenderResult);
	}

	/**
	 * @test
	 */
	public function uncachedSegmentOnTopLevelIsProcessedWithoutChanges() {
		$object = new TestModel(42, 'Object value 1');

		$view = $this->buildView();
		$view->setOption('enableContentCache', TRUE);
		$view->setTypoScriptPath('contentCache/uncachedSegmentOnTopLevel');

		$view->assign('object', $object);

		$firstRenderResult = $view->render();
		$this->assertSame('Uncached segment|counter=1|End uncached', $firstRenderResult);

		$secondRenderResult = $view->render();
		$this->assertSame('Uncached segment|counter=2|End uncached', $secondRenderResult);
	}

	/**
	 * @test
	 */
	public function uncachedSegmentInCachedSegmentIsEvaluatedFromSerializedContext() {
		$object = new TestModel(42, 'Object value 1');

		$view = $this->buildView();
		$view->setOption('enableContentCache', TRUE);
		$view->setTypoScriptPath('contentCache/uncachedSegmentInCachedSegment');

		$view->assign('object', $object);

		$firstRenderResult = $view->render();
		$this->assertSame('Outer segment|object=Object value 1|Uncached segment|counter=1|End uncached|End outer', $firstRenderResult);

			// Update the object value to see that the outer segment is really cached
		$object->setValue('Object value 2');

		$secondRenderResult = $view->render();
		$this->assertSame('Outer segment|object=Object value 1|Uncached segment|counter=2|End uncached|End outer', $secondRenderResult);
	}

	/**
	 * @test
	 */
	public function uncachedSegmentInUpdatedCachedSegmentIsEvaluatedFromContextValue() {
		$object = new TestModel(42, 'Object value 1');

		$view = $this->buildView();
		$view->setOption('enableContentCache', TRUE);
		$view->setTypoScriptPath('contentCache/uncachedSegmentInCachedSegment');

		$view->assign('object', $object);

		$firstRenderResult = $view->render();
		$this->assertSame('Outer segment|object=Object value 1|Uncached segment|counter=1|End uncached|End outer', $firstRenderResult);

			// Assigning a new object changes the identifier and therefore a new outer cache segment is created
		$newObject = new TestModel(21, 'New object value');
		$view->assign('object', $newObject);

		$renderResultAfterNewObject = $view->render();
		$this->assertSame('Outer segment|object=New object value|Uncached segment|counter=1|End uncached|End outer', $renderResultAfterNewObject);

		$secondRenderResult = $view->render();
		$this->assertSame('Outer segment|object=New object value|Uncached segment|counter=2|End uncached|End outer', $secondRenderResult);
	}

	/**
	 * @test
	 */
	public function flushByTagFlushesCacheEntriesWithSpecificEntryTagsAndRerenderCreatesOuterSegment() {
		$object = new TestModel(42, 'Object value 1');

		$view = $this->buildView();
		$view->setOption('enableContentCache', TRUE);
		$view->setTypoScriptPath('contentCache/cacheSegmentsWithTags');

		$view->assign('object', $object);
		$view->assign('site', 'site1');

		$firstRenderResult = $view->render();
		$this->assertSame('Outer segment|counter=1|Inner segment 1|object=Object value 1|End innerInner segment 2|object=Object value 1|End inner|End outer', $firstRenderResult);

		$object->setValue('Object value 2');

		$secondRenderResult = $view->render();
		$this->assertSame($firstRenderResult, $secondRenderResult);

			// This should flush "Inner segment 1"
		$this->contentCache->flushByTag('Object_' . $object->getId());

			// Since the cache entry for "Inner segment 1" is missing, the outer segment is also evaluated, but not "Inner segment 2"
		$secondRenderResult = $view->render();
		$this->assertSame('Outer segment|counter=2|Inner segment 1|object=Object value 2|End innerInner segment 2|object=Object value 1|End inner|End outer', $secondRenderResult);
	}

	/**
	 * @test
	 */
	public function entryTagsUseSanitizedTagValue() {
		$object = new TestModel(42, 'Object value 1');

		$view = $this->buildView();
		$view->setOption('enableContentCache', TRUE);
		$view->setTypoScriptPath('contentCache/cacheSegmentsWithTags');

		$view->assign('object', $object);
		$view->assign('site', 'site1');

		$firstRenderResult = $view->render();
		$this->assertSame('Outer segment|counter=1|Inner segment 1|object=Object value 1|End innerInner segment 2|object=Object value 1|End inner|End outer', $firstRenderResult);

		$object->setValue('Object value 2');

			// This should flush "Inner segment 1"
		$this->contentCache->flushByTag('NodeType_Acme.Demo:SampleNodeType');

			// Since the cache entry for "Inner segment 1" is missing, the outer segment is also evaluated, but not "Inner segment 2"
		$secondRenderResult = $view->render();
		$this->assertSame('Outer segment|counter=2|Inner segment 1|object=Object value 2|End innerInner segment 2|object=Object value 1|End inner|End outer', $secondRenderResult);
	}

}
