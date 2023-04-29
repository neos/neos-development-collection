<?php
namespace Neos\Fusion\Tests\Functional\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Cache\CacheManager;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Fusion\Core\Cache\ContentCache;
use Neos\Fusion\Tests\Functional\FusionObjects\Fixtures\Model\TestModel;

/**
 * Test case for the Fusion ContentCache
 */
class ContentCacheTest extends AbstractFusionObjectTest
{
    /**
     * @var ContentCache
     */
    protected $contentCache;

    public function setUp(): void
    {
        parent::setUp();
        $this->contentCache = $this->objectManager->get(ContentCache::class);
        $this->contentCache->flush();
    }

    public function tearDown(): void
    {
        // Re-inject the original cache since some tests might replace it with a mock object
        $cacheManager = $this->objectManager->get(CacheManager::class);
        $cacheFrontend = $cacheManager->getCache('Neos_Fusion_Content');
        $this->inject($this->contentCache, 'cache', $cacheFrontend);
    }

    /**
     * @test
     */
    public function renderCachedSegmentTwiceYieldsSameResult()
    {
        $object = new TestModel(42, 'Object value 1');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->assign('object', $object);
        $view->setFusionPath('contentCache/cachedSegment');

        // This render call should create the cache entry
        $firstRenderResult = $view->render();

        $object->setValue('Object value 2');

        // And this render call should use the existing cache entry
        $secondRenderResult = $view->render();

        self::assertSame('Cached segment|Object value 1', $firstRenderResult);
        self::assertSame($firstRenderResult, $secondRenderResult);
    }

    /**
     * @test
     */
    public function nestedCacheSegmentsAreFetchedFromCache()
    {
        $object = new TestModel(42, 'Object value 1');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->setFusionPath('contentCache/nestedCacheSegments');

        $view->assign('site', 'site1');
        $view->assign('object', $object);

        $firstRenderResult = $view->render();

        self::assertSame('Outer segment|site=site1|Inner segment|object=Object value 1|End inner|End outer', $firstRenderResult);

        // This must not influence the output, since the inner segment should be fetched from cache
        $object->setValue('Object value 2');

        $view->assign('site', 'site2');
        $secondRenderResult = $view->render();
        self::assertSame('Outer segment|site=site2|Inner segment|object=Object value 1|End inner|End outer', $secondRenderResult);
    }

    /**
     * @test
     */
    public function uncachedSegmentOnTopLevelIsProcessedWithoutChanges()
    {
        $object = new TestModel(42, 'Object value 1');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->setFusionPath('contentCache/uncachedSegmentOnTopLevel');

        $view->assign('object', $object);

        $firstRenderResult = $view->render();
        self::assertSame('Uncached segment|counter=1|End uncached', $firstRenderResult);

        $secondRenderResult = $view->render();
        self::assertSame('Uncached segment|counter=2|End uncached', $secondRenderResult);
    }

    /**
     * @test
     */
    public function uncachedSegmentWithWrongContextConfigurationWillTriggerErrorOnFirstHit()
    {
        $object = new TestModel(42, 'Object value 1');
        $otherContextVariable = 'foo';

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->setFusionPath('contentCache/uncachedSegmentWithWronglyConfiguredContext');

        $view->assign('object', $object);
        $view->assign('otherContextVariable', $otherContextVariable);

        $firstRenderResult = $view->render();
        self::assertSame('Uncached segment|counter=|End uncached', $firstRenderResult);

        $secondRenderResult = $view->render();
        self::assertSame('Uncached segment|counter=|End uncached', $secondRenderResult);
    }

    /**
     * @test
     */
    public function uncachedSegmentInCachedSegmentIsEvaluatedFromSerializedContext()
    {
        $object = new TestModel(42, 'Object value 1');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->setFusionPath('contentCache/uncachedSegmentInCachedSegment');

        $view->assign('object', $object);

        $firstRenderResult = $view->render();
        self::assertSame('Outer segment|object=Object value 1|Uncached segment|counter=1|End uncached|End outer', $firstRenderResult);

        // Update the object value to see that the outer segment is really cached
        $object->setValue('Object value 2');

        $secondRenderResult = $view->render();
        self::assertSame('Outer segment|object=Object value 1|Uncached segment|counter=2|End uncached|End outer', $secondRenderResult);
    }

    /**
     * @test
     */
    public function uncachedSegmentInUpdatedCachedSegmentIsEvaluatedFromContextValue()
    {
        $object = new TestModel(42, 'Object value 1');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->setFusionPath('contentCache/uncachedSegmentInCachedSegment');

        $view->assign('object', $object);

        $firstRenderResult = $view->render();
        self::assertSame('Outer segment|object=Object value 1|Uncached segment|counter=1|End uncached|End outer', $firstRenderResult);

        // Assigning a new object changes the identifier and therefore a new outer cache segment is created
        $newObject = new TestModel(21, 'New object value');
        $view->assign('object', $newObject);

        $renderResultAfterNewObject = $view->render();
        self::assertSame('Outer segment|object=New object value|Uncached segment|counter=1|End uncached|End outer', $renderResultAfterNewObject);

        $secondRenderResult = $view->render();
        self::assertSame('Outer segment|object=New object value|Uncached segment|counter=2|End uncached|End outer', $secondRenderResult);
    }

    /**
     * @test
     */
    public function flushByTagFlushesCacheEntriesWithSpecificEntryTagsAndRerenderCreatesOuterSegment()
    {
        $object = new TestModel(42, 'Object value 1');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->setFusionPath('contentCache/cacheSegmentsWithTags');

        $view->assign('object', $object);
        $view->assign('site', 'site1');

        $firstRenderResult = $view->render();
        self::assertSame('Outer segment|counter=1|Inner segment 1|object=Object value 1|End innerInner segment 2|object=Object value 1|End inner|End outer', $firstRenderResult);

        $object->setValue('Object value 2');

        $secondRenderResult = $view->render();
        self::assertSame($firstRenderResult, $secondRenderResult);

        // This should flush "Inner segment 1"
        $this->contentCache->flushByTag('Object_' . $object->getId());

        // Since the cache entry for "Inner segment 1" is missing, the outer segment is also evaluated, but not "Inner segment 2"
        $secondRenderResult = $view->render();
        self::assertSame('Outer segment|counter=2|Inner segment 1|object=Object value 2|End innerInner segment 2|object=Object value 1|End inner|End outer', $secondRenderResult);
    }

    /**
     * @test
     */
    public function entryTagsUseSanitizedTagValue()
    {
        $object = new TestModel(42, 'Object value 1');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->setFusionPath('contentCache/cacheSegmentsWithTags');

        $view->assign('object', $object);
        $view->assign('site', 'site1');

        $firstRenderResult = $view->render();
        self::assertSame('Outer segment|counter=1|Inner segment 1|object=Object value 1|End innerInner segment 2|object=Object value 1|End inner|End outer', $firstRenderResult);

        $object->setValue('Object value 2');

        // This should flush "Inner segment 1"
        $this->contentCache->flushByTag('NodeType_Acme.Demo:SampleNodeType');

        // Since the cache entry for "Inner segment 1" is missing, the outer segment is also evaluated, but not "Inner segment 2"
        $secondRenderResult = $view->render();
        self::assertSame('Outer segment|counter=2|Inner segment 1|object=Object value 2|End innerInner segment 2|object=Object value 1|End inner|End outer', $secondRenderResult);

        // This should flush "Inner segment 2"
        $this->contentCache->flushByTag('Node_47a6ee72-936a-4489-abc1-3666a63cdc4a');

        // Since the cache entry for "Inner segment 2" is missing, the outer segment is also evaluated, but not "Inner segment 1"
        $secondRenderResult = $view->render();
        self::assertSame('Outer segment|counter=3|Inner segment 1|object=Object value 2|End innerInner segment 2|object=Object value 2|End inner|End outer', $secondRenderResult);
    }

    /**
     * @test
     */
    public function processorsAreAppliedBeforeCachingASegment()
    {
        $object = new TestModel(42, 'Object value 1');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->setFusionPath('contentCache/cachedSegmentWithProcessor');

        $view->assign('object', $object);

        $firstRenderResult = $view->render();
        self::assertSame('Processor start|counter=1|Cached segment|object=Object value 1|End cached|Processor end', $firstRenderResult);

        $secondRenderResult = $view->render();
        self::assertSame($firstRenderResult, $secondRenderResult);
    }

    /**
     * @test
     */
    public function processorsAreAppliedAfterUncachedSegments()
    {
        $object = new TestModel(42, 'Object value 1');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->setFusionPath('contentCache/uncachedSegmentWithProcessor');

        $view->assign('object', $object);

        $firstRenderResult = $view->render();
        self::assertSame('Cached segment|Processor start|counter=1|Uncached segment|object=Object value 1|End cached|Processor end|End segment', $firstRenderResult);

        $secondRenderResult = $view->render();
        self::assertSame('Cached segment|Processor start|counter=2|Uncached segment|object=Object value 1|End cached|Processor end|End segment', $secondRenderResult);
    }

    /**
     * @test
     */
    public function conditionsAreAppliedAfterGettingCachedSegment()
    {
        $object = new TestModel(42, 'Object value 1');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->setFusionPath('contentCache/cachedSegmentWithCondition');

        $view->assignMultiple([
            'object' => $object,
            'condition' => true
        ]);

        $firstRenderResult = $view->render();
        self::assertSame('Cached segment|object=Object value 1|End cached', $firstRenderResult);

        $secondRenderResult = $view->render();
        self::assertSame($firstRenderResult, $secondRenderResult);

        $view->assign('condition', false);

        $updatedRenderResult = $view->render();
        self::assertSame('', $updatedRenderResult);
    }

    /**
     * @test
     */
    public function conditionsAreAppliedForUncachedSegment()
    {
        $object = new TestModel(42, 'Object value 1');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->setFusionPath('contentCache/uncachedSegmentWithCondition');

        $view->assignMultiple([
            'object' => $object
        ]);

        /** @var \Neos\Flow\Mvc\ActionRequest $actionRequest */
        $actionRequest = $this->controllerContext->getRequest();
        $actionRequest->setArgument('currentPage', 1);

        $firstRenderResult = $view->render();
        self::assertSame('Cached segment|Uncached segment|request.currentPage=1|End cached|End segment', $firstRenderResult, 'Initial cached result');

        $actionRequest->setArgument('currentPage', 2);

        $secondRenderResult = $view->render();
        self::assertSame('Cached segment|Uncached segment|request.currentPage=2|End cached|End segment', $secondRenderResult, 'Evaluated result with updated request');

        $actionRequest->setArgument('currentPage', 3);

        $updatedRenderResult = $view->render();
        self::assertSame('Cached segment||End segment', $updatedRenderResult);
    }

    /**
     * @test
     */
    public function handlingInnerRenderingExceptionsDisablesTheContentCache()
    {
        $object = new TestModel(42, 'Object value 1');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->setFusionPath('contentCache/nestedCacheSegmentsWithInnerException');

        $view->assign('object', $object);

        $firstRenderResult = $view->render();
        self::assertStringStartsWith('Cached segment|counter=1|Exception', $firstRenderResult);

        $secondRenderResult = $view->render();
        self::assertStringStartsWith('Cached segment|counter=2|Exception', $secondRenderResult);
    }

    /**
     * @test
     */
    public function exceptionInAlreadyCachedSegmentShouldNotLeaveSegmentMarkersInOutput()
    {
        $object = new TestModel(42, 'Object value 1');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->setFusionPath('contentCache/nestedCacheSegmentsWithConditionalException');

        $view->assign('object', $object);
        $view->assign('throwException', false);

        $firstRenderResult = $view->render();
        self::assertEquals('Cached segment|counter=1|It depends|End segment', $firstRenderResult);

        $this->contentCache->flushByTag('Inner');
        $view->assign('throwException', true);

        $secondRenderResult = $view->render();
        self::assertStringStartsWith('Cached segment|counter=1|Exception', $secondRenderResult);
    }

    /**
     * @test
     */
    public function maximumLifetimeForCachedSegmentWillBeMinimumOfNestedEmbedSegmentsAndSelf()
    {
        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->setFusionPath('contentCache/maximumLifetimeInNestedEmbedAndCachedSegments');

        $mockCache = $this->createMock(\Neos\Cache\Frontend\FrontendInterface::class);
        $this->inject($this->contentCache, 'cache', $mockCache);

        $mockCache->expects(self::any())->method('get')->will(self::returnValue(false));
        $mockCache->expects(self::any())->method('has')->will(self::returnValue(false));

        $entriesWritten = [];

        $mockCache->expects(self::atLeastOnce())->method('set')->will(self::returnCallback(function ($entryIdentifier, $data, $tags, $lifetime) use (&$entriesWritten) {
            $entriesWritten[$entryIdentifier] = [
                'lifetime' => $lifetime
            ];
        }));

        $firstRenderResult = $view->render();
        self::assertEquals('Foo|Bar|Baz|Qux', $firstRenderResult);

        self::assertCount(4, $entriesWritten);
        self::assertEquals([
            // contentCache.maximumLifetimeInNestedEmbedAndCachedSegments.5
            '217f4e026faf0fbd4cb0e86d77d934d1' => [
                'lifetime' => 60
            ],
            // contentCache.maximumLifetimeInNestedEmbedAndCachedSegments.25
            '2f0c5ec12ea60245110d35e86e8ce021' => [
                'lifetime' => null
            ],
            // contentCache.maximumLifetimeInNestedEmbedAndCachedSegments.35
            'b414ac4545ebfe9585bd8019acbc0b17' => [
                'lifetime' => 0
            ],
            // contentCache.maximumLifetimeInNestedEmbedAndCachedSegments
            'a604a8f56ba95f256b3df4769b42bc6a' => [
                'lifetime' => 5
            ]
        ], $entriesWritten);
    }

    /**
     * @test
     */
    public function cacheUsesGlobalCacheIdentifiersAsDefaultPrototypeForEntryIdentifier()
    {
        $entriesWritten = [];
        $mockCache = $this->createMock(\Neos\Cache\Frontend\FrontendInterface::class);
        $mockCache->expects(self::any())->method('get')->will(self::returnValue(false));
        $mockCache->expects(self::any())->method('has')->will(self::returnValue(false));
        $mockCache->expects(self::atLeastOnce())->method('set')->will(self::returnCallback(function ($entryIdentifier, $data, $tags, $lifetime) use (&$entriesWritten) {
            $entriesWritten[$entryIdentifier] = [
                'tags' => $tags
            ];
        }));
        $this->inject($this->contentCache, 'cache', $mockCache);

        $object = new TestModel(42, 'Object value 1');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->setFusionPath('contentCache/entryIdentifiersAreMergedWithGlobalIdentifiers');

        $view->assign('object', $object);
        $view->assign('site', 'site1');

        $firstRenderResult = $view->render();

        self::assertSame('Cached segment|Object value 1', $firstRenderResult);

        // As the site should be added to the entry identifier because it is in the Neos.Fusion:GlobalCacheIdentifiers prototype, changing the value should give us a different identifier
        $view->assign('site', 'site2');
        $secondRenderResult = $view->render();
        self::assertSame($firstRenderResult, $secondRenderResult);
        self::assertCount(2, $entriesWritten);
        self::assertEquals([
            '49c7f1e2dde942ea9cc6c658a7ece943' => [
                'tags' => ['site1']
            ],
            'a932d6d5860b204e82079255e224c613' => [
                'tags' => ['site2']
            ],
        ], $entriesWritten);
    }

    /**
     * @test
     */
    public function globalIdentifiersAreUsedWithBlankEntryIdentifiers()
    {
        $entriesWritten = [];
        $mockCache = $this->createMock(\Neos\Cache\Frontend\FrontendInterface::class);
        $mockCache->expects(self::any())->method('get')->will(self::returnValue(false));
        $mockCache->expects(self::any())->method('has')->will(self::returnValue(false));
        $mockCache->expects(self::atLeastOnce())->method('set')->will(self::returnCallback(function ($entryIdentifier, $data, $tags, $lifetime) use (&$entriesWritten) {
            $entriesWritten[$entryIdentifier] = [
                'tags' => $tags
            ];
        }));
        $this->inject($this->contentCache, 'cache', $mockCache);

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->setFusionPath('contentCache/globalIdentifiersAreUsedWithBlankEntryIdentifiers');

        $view->assign('site', 'site1');

        $firstRenderResult = $view->render();

        $view->assign('site', 'site2');
        $secondRenderResult = $view->render();
        self::assertSame($firstRenderResult, $secondRenderResult);
        self::assertCount(2, $entriesWritten);
        self::assertEquals([
            'd9deea3648c9bfb24afdcb26bab8c023' => [
                'tags' => ['site1']
            ],
            '00e5aff1779f8f65ec4abf801834a682' => [
                'tags' => ['site2']
            ],
        ], $entriesWritten);
    }

    /**
     * @test
     */
    public function cacheIdentifierPrototypeCanBeOverwritten()
    {
        $entriesWritten = [];
        $mockCache = $this->createMock(FrontendInterface::class);
        $mockCache->expects(self::any())->method('get')->will(self::returnCallback(function ($entryIdentifier) use ($entriesWritten) {
            if (isset($entriesWritten[$entryIdentifier])) {
                return $entriesWritten[$entryIdentifier]['data'];
            } else {
                return false;
            }
        }));
        $mockCache->expects(self::any())->method('has')->will(self::returnCallback(function ($entryIdentifier) use ($entriesWritten) {
            if (isset($entriesWritten[$entryIdentifier])) {
                return true;
            } else {
                return false;
            }
        }));
        $mockCache->expects(self::atLeastOnce())->method('set')->will(self::returnCallback(function ($entryIdentifier, $data, $tags, $lifetime) use (&$entriesWritten) {
            if (!isset($entriesWritten[$entryIdentifier])) {
                $entriesWritten[$entryIdentifier] = [
                    'tags' => $tags,
                    'data' => $data
                ];
            }
        }));
        $this->inject($this->contentCache, 'cache', $mockCache);

        $object = new TestModel(42, 'Object value 1');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->setFusionPath('contentCache/entryIdentifierPrototypeCanBeOverwritten');

        $view->assign('object', $object);
        $view->assign('site', 'site1');

        $firstRenderResult = $view->render();

        self::assertSame('Cached segment|Object value 1', $firstRenderResult);

        // We overwrote the prototype for cacheIdentifier, so site is not part of the identifier and therefor the same identifier should be created.
        $view->assign('site', 'site2');
        $secondRenderResult = $view->render();
        self::assertSame($firstRenderResult, $secondRenderResult);
        self::assertCount(1, $entriesWritten);
        self::assertEquals([
            '21fe7cb71a709292398e766a9bb45662' => [
                'tags' => ['site1'],
                'data' => 'Cached segment|Object value 1'
            ],
        ], $entriesWritten);
    }


    /**
     * @test
     */
    public function uncachedSegmentInCachedSegmentCanOverrideContextVariables()
    {
        $object = new TestModel(42, 'Object value 1');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->setFusionPath('contentCache/uncachedSegmentInCachedSegmentCanOverrideContextVariables');

        $view->assign('object', $object);

        $firstRenderResult = $view->render();
        self::assertSame('Outer segment|object=Object value 1|Uncached segment|counter=1|End uncached|End outer', $firstRenderResult);

        // Update the object value to see that the outer segment is really cached
        $object->setValue('Object value 2');

        $secondRenderResult = $view->render();
        self::assertSame('Outer segment|object=Object value 1|Uncached segment|counter=2|End uncached|End outer', $secondRenderResult);
    }

    /**
     * @test
     */
    public function dynamicSegmentIsCachedIfDiscriminatorIsNotChanged()
    {
        $renderObject = new TestModel(42, 'Render object');
        $discriminatorObject = new TestModel(43, 'Discriminator object');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->assign('renderObject', $renderObject);
        $view->assign('discriminatorObject', $discriminatorObject);
        $view->setFusionPath('contentCache/dynamicSegment');

        $firstRenderResult = $view->render();

        $renderObject->setValue('Should not affect the cache');

        $secondRenderResult = $view->render();

        self::assertSame('Dynamic segment|counter=1', $secondRenderResult);
        self::assertSame($firstRenderResult, $secondRenderResult);
    }

    /**
     * @test
     */
    public function dynamicSegmentCacheIsFlushedIfDiscriminatorIsChanged()
    {
        $renderObject = new TestModel(42, 'Render object');
        $discriminatorObject = new TestModel(43, 'Discriminator object');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->assign('renderObject', $renderObject);
        $view->assign('discriminatorObject', $discriminatorObject);
        $view->setFusionPath('contentCache/dynamicSegment');

        $firstRenderResult = $view->render();

        $discriminatorObject->setValue('This should affect the cache');

        $secondRenderResult = $view->render();

        self::assertSame('Dynamic segment|counter=2', $secondRenderResult);
        self::assertNotSame($firstRenderResult, $secondRenderResult);
    }

    /**
     * @test
     */
    public function dynamicSegmentCacheBehavesLikeUncachedIfDiscriminatorIsDisabled()
    {
        $renderObject = new TestModel(42, 'Render object');
        $discriminatorObject = new TestModel(43, 'Discriminator object');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->assign('renderObject', $renderObject);
        $view->assign('discriminatorObject', $discriminatorObject);
        $view->setFusionPath('contentCache/dynamicSegmentWithDisabledDiscriminator');

        $firstRenderResult = $view->render();
        $secondRenderResult = $view->render();

        $discriminatorObject->setValue('disable');

        $thirdRenderResult = $view->render();
        $fourthRenderResult = $view->render();

        self::assertSame('Dynamic segment|counter=1', $firstRenderResult);
        self::assertSame($firstRenderResult, $secondRenderResult);
        self::assertSame('Dynamic segment|counter=2', $thirdRenderResult);
        self::assertSame('Dynamic segment|counter=3', $fourthRenderResult);
    }

    /**
     * @test
     */
    public function cachedSegmentsCanBeNestedWithinDynamicSegments()
    {
        $renderObject = new TestModel(42, 'Render object');

        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->assign('renderObject', $renderObject);
        $view->setFusionPath('contentCache/dynamicSegmentWithNestedCachedSegment');

        $firstRenderResult = $view->render();
        $secondRenderResult = $view->render();

        self::assertSame('Cached segment|counter=1|Nested dynamic segment|counter=2|Nested cached segment|counter=3', $firstRenderResult);
        self::assertSame($firstRenderResult, $secondRenderResult);
    }

    /**
     * @test
     */
    public function cachedSegmentWithNestedDynamicSegmentCanReRenderWithCacheEntryFlushTest()
    {
        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->assign('someContextVariable', 'prettyUnused');
        $view->setFusionPath('contentCache/cachedSegmentWithNestedDynamicSegment');

        $firstRenderResult = $view->render();

        $this->contentCache->flushByTag('testing');

        $secondRenderResult = $view->render();
        $thirdRenderResult = $view->render();

        self::assertSame('prettyUnused', $firstRenderResult);
        self::assertSame('prettyUnused', $secondRenderResult);
        self::assertSame('prettyUnused', $thirdRenderResult);
    }
    /**
     * @test
     */
    public function contextIsCorrectlyEvaluated()
    {
        $view = $this->buildView();
        $view->setOption('enableContentCache', true);
        $view->assign('someContextVariable', 'prettyUnused');
        $view->setFusionPath('contentCache/dynamicWithChangingDiscriminator');

        /** @var ActionRequest $actionRequest */
        $actionRequest = $this->controllerContext->getRequest();
        $actionRequest->setArgument('testArgument', '1');
        $firstRenderResult = $view->render();

        $this->contentCache->flushByTag('testing');

        $actionRequest->setArgument('testArgument', '2');
        $secondRenderResult = $view->render();

        $actionRequest->setArgument('testArgument', '3');
        $thirdRenderResult = $view->render();

        $actionRequest->setArgument('testArgument', '4');
        $fourthRenderResult = $view->render();

        self::assertSame('1', $firstRenderResult);
        self::assertSame('2', $secondRenderResult);
        self::assertSame('3', $thirdRenderResult);
        self::assertSame('4', $fourthRenderResult);
    }
}
