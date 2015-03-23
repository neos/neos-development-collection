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

	public function tearDown() {
		// Re-inject the original cache since some tests might replace it with a mock object
		$cacheManager = $this->objectManager->get('TYPO3\Flow\Cache\CacheManager');
		$cacheFrontend = $cacheManager->getCache('TYPO3_TypoScript_Content');
		$this->inject($this->contentCache, 'cache', $cacheFrontend);
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
	public function uncachedSegmentWithWrongContextConfigurationWillTriggerErrorOnFirstHit() {
		$object = new TestModel(42, 'Object value 1');
		$otherContextVariable = 'foo';

		$view = $this->buildView();
		$view->setOption('enableContentCache', TRUE);
		$view->setTypoScriptPath('contentCache/uncachedSegmentWithWronglyConfiguredContext');

		$view->assign('object', $object);
		$view->assign('otherContextVariable', $otherContextVariable);

		$firstRenderResult = $view->render();
		$this->assertSame('Uncached segment|counter=|End uncached', $firstRenderResult);

		$secondRenderResult = $view->render();
		$this->assertSame('Uncached segment|counter=|End uncached', $secondRenderResult);
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

	/**
	 * @test
	 */
	public function processorsAreAppliedBeforeCachingASegment() {
		$object = new TestModel(42, 'Object value 1');

		$view = $this->buildView();
		$view->setOption('enableContentCache', TRUE);
		$view->setTypoScriptPath('contentCache/cachedSegmentWithProcessor');

		$view->assign('object', $object);

		$firstRenderResult = $view->render();
		$this->assertSame('Processor start|counter=1|Cached segment|object=Object value 1|End cached|Processor end', $firstRenderResult);

		$secondRenderResult = $view->render();
		$this->assertSame($firstRenderResult, $secondRenderResult);
	}

	/**
	 * @test
	 */
	public function processorsAreAppliedAfterUncachedSegments() {
		$object = new TestModel(42, 'Object value 1');

		$view = $this->buildView();
		$view->setOption('enableContentCache', TRUE);
		$view->setTypoScriptPath('contentCache/uncachedSegmentWithProcessor');

		$view->assign('object', $object);

		$firstRenderResult = $view->render();
		$this->assertSame('Cached segment|Processor start|counter=1|Uncached segment|object=Object value 1|End cached|Processor end|End segment', $firstRenderResult);

		$secondRenderResult = $view->render();
		$this->assertSame('Cached segment|Processor start|counter=2|Uncached segment|object=Object value 1|End cached|Processor end|End segment', $secondRenderResult);
	}

	/**
	 * @test
	 */
	public function conditionsAreAppliedAfterGettingCachedSegment() {
		$object = new TestModel(42, 'Object value 1');

		$view = $this->buildView();
		$view->setOption('enableContentCache', TRUE);
		$view->setTypoScriptPath('contentCache/cachedSegmentWithCondition');

		$view->assignMultiple(array(
			'object' => $object,
			'condition' => TRUE
		));

		$firstRenderResult = $view->render();
		$this->assertSame('Cached segment|object=Object value 1|End cached', $firstRenderResult);

		$secondRenderResult = $view->render();
		$this->assertSame($firstRenderResult, $secondRenderResult);

		$view->assign('condition', FALSE);

		$updatedRenderResult = $view->render();
		$this->assertSame('', $updatedRenderResult);
	}

	/**
	 * @test
	 */
	public function conditionsAreAppliedForUncachedSegment() {
		$object = new TestModel(42, 'Object value 1');

		$view = $this->buildView();
		$view->setOption('enableContentCache', TRUE);
		$view->setTypoScriptPath('contentCache/uncachedSegmentWithCondition');

		$view->assignMultiple(array(
			'object' => $object
		));

		/** @var \TYPO3\Flow\Mvc\ActionRequest $actionRequest */
		$actionRequest = $this->controllerContext->getRequest();
		$actionRequest->setArgument('currentPage', 1);

		$firstRenderResult = $view->render();
		$this->assertSame('Cached segment|Uncached segment|request.currentPage=1|End cached|End segment', $firstRenderResult, 'Initial cached result');

		$actionRequest->setArgument('currentPage', 2);

		$secondRenderResult = $view->render();
		$this->assertSame('Cached segment|Uncached segment|request.currentPage=2|End cached|End segment', $secondRenderResult, 'Evaluated result with updated request');

		$actionRequest->setArgument('currentPage', 3);

		$updatedRenderResult = $view->render();
		$this->assertSame('Cached segment||End segment', $updatedRenderResult);
	}

	/**
	 * @test
	 */
	public function handlingInnerRenderingExceptionsDisablesTheContentCache() {
		$object = new TestModel(42, 'Object value 1');

		$view = $this->buildView();
		$view->setOption('enableContentCache', TRUE);
		$view->setTypoScriptPath('contentCache/nestedCacheSegmentsWithInnerException');

		$view->assign('object', $object);

		$firstRenderResult = $view->render();
		$this->assertStringStartsWith('Cached segment|counter=1|Exception', $firstRenderResult);

		$secondRenderResult = $view->render();
		$this->assertStringStartsWith('Cached segment|counter=2|Exception', $secondRenderResult);
	}

	/**
	 * @test
	 */
	public function exceptionInAlreadyCachedSegmentShouldNotLeaveSegmentMarkersInOutput() {
		$object = new TestModel(42, 'Object value 1');

		$view = $this->buildView();
		$view->setOption('enableContentCache', TRUE);
		$view->setTypoScriptPath('contentCache/nestedCacheSegmentsWithConditionalException');

		$view->assign('object', $object);
		$view->assign('throwException', FALSE);

		$firstRenderResult = $view->render();
		$this->assertEquals('Cached segment|counter=1|It depends|End segment', $firstRenderResult);

		$this->contentCache->flushByTag('Inner');
		$view->assign('throwException', TRUE);

		$secondRenderResult = $view->render();
		$this->assertStringStartsWith('Cached segment|counter=1|Exception', $secondRenderResult);
	}

	/**
	 * @test
	 */
	public function maximumLifetimeForCachedSegmentWillBeMinimumOfNestedEmbedSegmentsAndSelf() {
		$view = $this->buildView();
		$view->setOption('enableContentCache', TRUE);
		$view->setTypoScriptPath('contentCache/maximumLifetimeInNestedEmbedAndCachedSegments');

		$mockCache = $this->getMock('TYPO3\Flow\Cache\Frontend\FrontendInterface');
		$this->inject($this->contentCache, 'cache', $mockCache);

		$mockCache->expects($this->any())->method('get')->will($this->returnValue(FALSE));
		$mockCache->expects($this->any())->method('has')->will($this->returnValue(FALSE));

		$entriesWritten = array();

		$mockCache->expects($this->atLeastOnce())->method('set')->will($this->returnCallback(function($entryIdentifier, $data, $tags, $lifetime) use (&$entriesWritten) {
			$entriesWritten[$entryIdentifier] = array(
				'lifetime' => $lifetime
			);
		}));

		$firstRenderResult = $view->render();
		$this->assertEquals('Foo|Bar|Baz', $firstRenderResult);

		$this->assertCount(3, $entriesWritten);
		$this->assertEquals(array(
			// contentCache.maximumLifetimeInNestedEmbedAndCachedSegments.5
			'bd35f9d36e0e24992cb4810f759bced4' => array(
				'lifetime' => 60
			),
			// contentCache.maximumLifetimeInNestedEmbedAndCachedSegments.25
			'd5bfcc617aa269282dbfa0887b7e1c65' => array(
				'lifetime' => NULL
			),
			// contentCache.maximumLifetimeInNestedEmbedAndCachedSegments
			'a604a8f56ba95f256b3df4769b42bc6a' => array(
				'lifetime' => 5
			)
		), $entriesWritten);
	}

	/**
	 * @test
	 */
	public function cacheUsesGlobalCacheIdentifiersAsDefaultPrototypeForEntryIdentifier() {
		$entriesWritten = array();
		$mockCache = $this->getMock('TYPO3\Flow\Cache\Frontend\FrontendInterface');
		$mockCache->expects($this->any())->method('get')->will($this->returnValue(FALSE));
		$mockCache->expects($this->any())->method('has')->will($this->returnValue(FALSE));
		$mockCache->expects($this->atLeastOnce())->method('set')->will($this->returnCallback(function ($entryIdentifier, $data, $tags, $lifetime) use (&$entriesWritten) {
			$entriesWritten[$entryIdentifier] = array(
				'tags' => $tags
			);
		}));
		$this->inject($this->contentCache, 'cache', $mockCache);

		$object = new TestModel(42, 'Object value 1');

		$view = $this->buildView();
		$view->setOption('enableContentCache', TRUE);
		$view->setTypoScriptPath('contentCache/entryIdentifiersAreMergedWithGlobalIdentifiers');

		$view->assign('object', $object);
		$view->assign('site', 'site1');

		$firstRenderResult = $view->render();

		$this->assertSame('Cached segment|Object value 1', $firstRenderResult);

		// As the site should be added to the entry identifier because it is in the TYPO3.TypoScript:GlobalCacheIdentifiers prototype, changing the value should give us a different identifier
		$view->assign('site', 'site2');
		$secondRenderResult = $view->render();
		$this->assertSame($firstRenderResult, $secondRenderResult);
		$this->assertCount(2, $entriesWritten);
		$this->assertEquals(array(
			'49c7f1e2dde942ea9cc6c658a7ece943' => array(
				'tags' => array('site1')
			),
			'a932d6d5860b204e82079255e224c613' => array(
				'tags' => array('site2')
			),
		), $entriesWritten);
	}

	/**
	 * @test
	 */
	public function cacheIdentifierPrototypeCanBeOverwritten() {
		$entriesWritten = array();
		$mockCache = $this->getMock('TYPO3\Flow\Cache\Frontend\FrontendInterface');
		$mockCache->expects($this->any())->method('get')->will($this->returnCallback(function ($entryIdentifier) use ($entriesWritten) {
			if (isset($entriesWritten[$entryIdentifier])) {
				return $entriesWritten[$entryIdentifier]['data'];
			} else {
				return FALSE;
			}
		}));
		$mockCache->expects($this->any())->method('has')->will($this->returnCallback(function ($entryIdentifier) use ($entriesWritten) {
			if (isset($entriesWritten[$entryIdentifier])) {
				return TRUE;
			} else {
				return FALSE;
			}
		}));
		$mockCache->expects($this->atLeastOnce())->method('set')->will($this->returnCallback(function ($entryIdentifier, $data, $tags, $lifetime) use (&$entriesWritten) {
			if (!isset($entriesWritten[$entryIdentifier])) {
				$entriesWritten[$entryIdentifier] = array(
					'tags' => $tags,
					'data' => $data
				);
			}
		}));
		$this->inject($this->contentCache, 'cache', $mockCache);

		$object = new TestModel(42, 'Object value 1');

		$view = $this->buildView();
		$view->setOption('enableContentCache', TRUE);
		$view->setTypoScriptPath('contentCache/entryIdentifierPrototypeCanBeOverwritten');

		$view->assign('object', $object);
		$view->assign('site', 'site1');

		$firstRenderResult = $view->render();

		$this->assertSame('Cached segment|Object value 1', $firstRenderResult);

		// We overwrote the prototype for cacheIdentifier, so site is not part of the identifier and therefor the same identifier should be created.
		$view->assign('site', 'site2');
		$secondRenderResult = $view->render();
		$this->assertSame($firstRenderResult, $secondRenderResult);
		$this->assertCount(1, $entriesWritten);
		$this->assertEquals(array(
			'21fe7cb71a709292398e766a9bb45662' => array(
				'tags' => array('site1'),
				'data' => 'Cached segment|Object value 1'
			),
		), $entriesWritten);
	}

}
