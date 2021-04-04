<?php
namespace Neos\Neos\Tests\Functional\Domain;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\Uri;
use Neos\Cache\Backend\TransientMemoryBackend;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\RouterCachingService;
use Neos\Http\Factories\ServerRequestFactory;
use Neos\Http\Factories\UriFactory;
use Neos\Neos\Tests\Functional\AbstractNodeTest;
use Neos\Utility\ObjectAccess;

/**
 * Tests checking correct Uri behavior for Neos nodes.
 */
class NodeUriTest extends AbstractNodeTest
{
    /**
     * @var string the Nodes fixture
     */
    protected $fixtureFileName = 'Domain/Fixtures/NodeUriTestStructure.xml';

    /**
     * @var string the context path of the node to load initially
     */
    protected $nodeContextPath = '/sites/uri-test/home';

    /**
     * @var TransientMemoryBackend
     */
    protected $transientRouteCacheBackend;

    /**
     * @var TransientMemoryBackend
     */
    protected $transientResolveCacheBackend;

    public function setUp(): void
    {
        parent::setUp();

        /** @var RouterCachingService $routerCachingService */
        $routerCachingService = ObjectAccess::getProperty($this->router, 'routerCachingService', true);
        $this->transientRouteCacheBackend = new TransientMemoryBackend();
        $routeCache = new VariableFrontend('Flow_Mvc_Routing_Route', $this->transientRouteCacheBackend);
        $this->transientRouteCacheBackend->setCache($routeCache);
        ObjectAccess::setProperty($routerCachingService, 'routeCache', $routeCache, true);

        $this->transientResolveCacheBackend = new TransientMemoryBackend();
        $resolveCache = new VariableFrontend('Flow_Mvc_Routing_Resolve', $this->transientResolveCacheBackend);
        $this->transientResolveCacheBackend->setCache($resolveCache);
        ObjectAccess::setProperty($routerCachingService, 'resolveCache', $resolveCache, true);
    }

    /**
     * Note: You cannot hide a node in a context that doesn't show invisible content and afterwards move it because moving breaks then.
     * The context used in this test therefor needs to be able to show hidden nodes.
     * TODO: Investigate this behavior, currently it executes without problems but the result is wrong.
     *
     * @test
     */
    public function hiddenNodeGetsNewUriSegmentOnMoveIfUriAlreadyExists()
    {
        $contextProperties = array_merge($this->node->getContext()->getProperties(), ['invisibleContentShown' => true]);
        $context = $this->contextFactory->create($contextProperties);
        $homeNode = $context->getNode($this->nodeContextPath);

        $historyNode = $homeNode->getNode('about-us/history');
        // History node will be moved inside products and gets an uriPathSegment that exists there already.
        $historyNode->setProperty('uriPathSegment', 'neos');
        $historyNode->setHidden(true);

        $this->persistenceManager->persistAll();

        $historyNode->moveInto($homeNode->getNode('products'));

        $uriPathSegment = $historyNode->getProperty('uriPathSegment');
        self::assertEquals('neos-1', $uriPathSegment);
    }

    /**
     * @test
     */
    public function nodeInNonDefaultDimensionGetsNewUriSegmentOnMoveIfUriAlreadyExists()
    {
        $homeNodeInNonDefaultDimension = $this->getNodeWithContextPath($this->nodeContextPath . '@live;language=de');

        $historyNode = $homeNodeInNonDefaultDimension->getNode('about-us/history');
        // History node will be moved inside products and gets an uriPathSegment that exists there already.
        $historyNode->setProperty('uriPathSegment', 'neos');

        $this->persistenceManager->persistAll();

        $historyNode->moveInto($homeNodeInNonDefaultDimension->getNode('products'));

        $uriPathSegment = $historyNode->getProperty('uriPathSegment');
        self::assertEquals('neos-1', $uriPathSegment);
    }

    /**
     * @test
     */
    public function matchedRouteIsTaggedWithNodeIdentifiersAndUriSegments(): void
    {
        $serverRequestFactory = new ServerRequestFactory(new UriFactory());
        $request = $serverRequestFactory->createServerRequest('GET', 'http://localhost/en/home/about-us/history.html');
        $routeContext = new RouteContext($request, RouteParameters::createEmpty()->withParameter('requestUriHost', 'localhost'));
        self::assertNotNull($this->router->route($routeContext));

        $actualCacheTags = $this->extractCapturedCacheTags($this->transientRouteCacheBackend);

        $expectedCacheTags = [
            '65acb152-0c26-4c3a-83de-7701bcafb681', // id of "history"
            'not-a-uuid', // id of "about-us"
            '3f72b314-13f9-405b-abc7-04c787784d01', // id of "home"
            '76f9079b-d885-4ce4-9628-cb3d13a9feb4', // id of "uri-test"
            md5('en'),
            md5('en/home'),
            md5('en/home/about-us'),
            md5('en/home/about-us/history.html'),
        ];
        self::assertSame($expectedCacheTags, $actualCacheTags);
    }

    /**
     * @test
     */
    public function resolvedRouteIsTaggedWithNodeIdentifiersAndUriSegments(): void
    {
        $routeValues = [
            '@package' => 'Neos.Neos',
            '@controller' => 'Frontend\\Node',
            '@action' => 'show',
            '@format' => 'html',
            'node' => '/sites/uri-test/home/about-us/history@live;language=en_US',
        ];
        $resolveContext = new ResolveContext(new Uri('http://localhost'), $routeValues, false, '', RouteParameters::createEmpty()->withParameter('requestUriHost', 'localhost'));
        $this->router->resolve($resolveContext);

        $actualCacheTags = $this->extractCapturedCacheTags($this->transientResolveCacheBackend);
        $expectedCacheTags = [
            '65acb152-0c26-4c3a-83de-7701bcafb681', // id of "history"
            'not-a-uuid', // id of "about-us"
            '3f72b314-13f9-405b-abc7-04c787784d01', // id of "home"
            '76f9079b-d885-4ce4-9628-cb3d13a9feb4', // id of "uri-test"
            md5('en'),
            md5('en/home'),
            md5('en/home/about-us'),
            md5('en/home/about-us/history.html'),
        ];
        self::assertSame($expectedCacheTags, $actualCacheTags);
    }

    private function extractCapturedCacheTags(TransientMemoryBackend $backend): array
    {
        $tagsAndEntries = ObjectAccess::getProperty($backend, 'tagsAndEntries', true);
        return array_keys($tagsAndEntries);
    }
}
