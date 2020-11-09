<?php
namespace Neos\Neos\Tests\Functional\Service;

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
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Tests\FunctionalTestRequestHandler;
use Neos\Media\TypeConverter\AssetInterfaceConverter;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\SiteImportService;
use Neos\Neos\Exception as NeosException;
use Neos\Neos\Exception;
use Neos\Neos\Service\LinkingService;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

/**
 * Test case for the LinkingService
 */
class LinkingServiceTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected $testableSecurityEnabled = true;

    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * @var ContentContext
     */
    protected $contentContext;

    /**
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var LinkingService
     */
    protected $linkingService;

    /**
     * @var NodeInterface
     */
    protected $baseNode;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->nodeDataRepository = $this->objectManager->get(NodeDataRepository::class);
        $domainRepository = $this->objectManager->get(DomainRepository::class);
        $siteRepository = $this->objectManager->get(SiteRepository::class);
        $this->contextFactory = $this->objectManager->get(ContextFactoryInterface::class);
        $contextProperties = [
            'workspaceName' => 'live'
        ];
        $contentContext = $this->contextFactory->create($contextProperties);
        $siteImportService = $this->objectManager->get(SiteImportService::class);
        $siteImportService->importFromFile(__DIR__ . '/../Fixtures/NodeStructure.xml', $contentContext);
        $this->persistenceManager->persistAll();

        $currentDomain = $domainRepository->findOneByActiveRequest();
        if ($currentDomain !== null) {
            $contextProperties['currentSite'] = $currentDomain->getSite();
            $contextProperties['currentDomain'] = $currentDomain;
        } else {
            $contextProperties['currentSite'] = $siteRepository->findFirst();
        }
        $contentContext = $this->contextFactory->create($contextProperties);

        $this->contentContext = $contentContext;

        $this->propertyMapper = $this->objectManager->get(PropertyMapper::class);

        $this->baseNode = $this->contentContext->getCurrentSiteNode()->getNode('home');

        $this->linkingService = $this->objectManager->get(LinkingService::class);
        /** @var $requestHandler FunctionalTestRequestHandler */
        $requestHandler = self::$bootstrap->getActiveRequestHandler();
        $httpRequest = $requestHandler->getComponentContext()->getHttpRequest();
        $httpRequest = $httpRequest->withUri(new Uri('http://neos.test/'));
        $httpRequest = $httpRequest->withAttribute(ServerRequestAttributes::ROUTING_PARAMETERS, RouteParameters::createEmpty()->withParameter('requestUriHost', 'neos.test'));
        $requestHandler->getComponentContext()->replaceHttpRequest($httpRequest);


        $this->controllerContext = new ControllerContext(ActionRequest::fromHttpRequest($httpRequest), new ActionResponse(), new Arguments([]), new UriBuilder());
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->inject($this->contextFactory, 'contextInstances', []);
        $this->inject($this->objectManager->get(AssetInterfaceConverter::class), 'resourcesAlreadyConvertedToAssets', []);
    }

    /**
     * @test
     */
    public function linkingServiceCreatesUriViaGivenNodeObject()
    {
        $targetNode = $this->propertyMapper->convert('/sites/example/home', Node::class);
        $this->assertOutputLinkValid('en/home.html', $this->linkingService->createNodeUri($this->controllerContext, $targetNode));
    }

    /**
     * @test
     */
    public function linkingServiceCreatesUriViaAbsoluteNodePathString()
    {
        $this->assertOutputLinkValid('en/home.html', $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/home', $this->baseNode));
        $this->assertOutputLinkValid('en/home/about-us.html', $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/home/about-us', $this->baseNode));
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/home/about-us/mission', $this->baseNode));
    }

    /**
     * @test
     */
    public function linkingServiceCreatesUriViaStringStartingWithTilde()
    {
        $this->assertOutputLinkValid('en/home.html', $this->linkingService->createNodeUri($this->controllerContext, '~', $this->baseNode));
        $this->assertOutputLinkValid('en/home.html', $this->linkingService->createNodeUri($this->controllerContext, '~/home', $this->baseNode));
        $this->assertOutputLinkValid('en/home/about-us.html', $this->linkingService->createNodeUri($this->controllerContext, '~/home/about-us', $this->baseNode));
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->linkingService->createNodeUri($this->controllerContext, '~/home/about-us/mission', $this->baseNode));
    }

    /**
     * @test
     */
    public function linkingServiceCreatesUriViaStringPointingToSubNodes()
    {
        $this->assertOutputLinkValid('en/home/about-us/history.html', $this->linkingService->createNodeUri($this->controllerContext, '../history', $this->contentContext->getCurrentSiteNode()->getNode('home/about-us/mission')));
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->linkingService->createNodeUri($this->controllerContext, 'about-us/mission', $this->baseNode));
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->linkingService->createNodeUri($this->controllerContext, './about-us/mission', $this->baseNode));
    }

    /**
     * We empty the TemplateVariableContainer for this test, as it shouldn't be needed for rendering a link to a node
     * identified by ContextNodePath
     *
     * @test
     */
    public function linkingServiceCreatesUriViaContextNodePathString()
    {
        $this->assertOutputLinkValid('en/home.html', $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/home@live'));
        $this->assertOutputLinkValid('en/home/about-us.html', $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/home/about-us@live'));
        $this->assertOutputLinkValid('en/home/about-us/our-mission.html', $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/home/about-us/mission@live'));
    }

    public function supportedSchemesDataProvider()
    {
        return [
            ['node://aeabe76a-551a-495f-a324-ad9a86b2aff7', true],
            ['asset://aeabe76a-551a-495f-a324-ad9a86b2aff7', true],
            ['random://aeabe76a-551a-495f-a324-ad9a86b2aff7', false]
        ];
    }

    /**
     * @dataProvider supportedSchemesDataProvider
     * @test
     */
    public function linkingServiceOnlySupportsNodesAndAssetSchemes($scheme, $match)
    {
        self::assertSame($match, $this->linkingService->hasSupportedScheme($scheme));
    }

    /**
     * @test
     */
    public function linkingServiceCanGetSchemeFromUrl()
    {
        self::assertSame('node', $this->linkingService->getScheme('node://aeabe76a-551a-495f-a324-ad9a86b2aff7'));
    }

    /**
     * @test
     */
    public function linkingServiceCanResolveNodeUri()
    {
        self::assertSame('/en/home.html', $this->linkingService->resolveNodeUri('node://3239baee-3e7f-785c-0853-f4302ef32570', $this->baseNode, $this->controllerContext));
    }

    /**
     * @test
     */
    public function linkingServiceResolveNodeUriReturnsNullForUnresolvableNodes()
    {
        self::assertSame(null, $this->linkingService->resolveNodeUri('node://3239baee-3e7f-785c-0853-f4302ef3257x', $this->baseNode, $this->controllerContext));
    }

    /**
     * @test
     */
    public function linkingServiceCanResolveAssetUri()
    {
        self::assertSame('http://neos.test/_Resources/Testing/Persistent/bed9a3e45070e97b921877e2bd9c35ba368beca0/Neos-logo_sRGB_color.pdf', $this->linkingService->resolveAssetUri('asset://1af89e5c-9e23-9a9d-ae15-1d77160cfb57'));
    }

    /**
     * @test
     */
    public function linkingServiceResolveAssetUriReturnsNullForUnresolvableAssets()
    {
        self::assertSame(null, $this->linkingService->resolveAssetUri('asset://89cd85cc-270e-0902-7113-d14ac7539c7x'));
    }

    /**
     * @test
     */
    public function linkingServiceCanConvertUriToObject()
    {
        $assetRepository = $this->objectManager->get(\Neos\Media\Domain\Repository\AssetRepository::class);
        $asset = $assetRepository->findByIdentifier('89cd85cc-270e-0902-7113-d14ac7539c75');

        self::assertSame($this->baseNode, $this->linkingService->convertUriToObject('node://3239baee-3e7f-785c-0853-f4302ef32570', $this->baseNode));
        self::assertSame($asset, $this->linkingService->convertUriToObject('asset://89cd85cc-270e-0902-7113-d14ac7539c75'));
    }

    /**
     * @test
     */
    public function linkingServiceThrowsAnExceptionWhenTryingToLinkToANonExistingNode()
    {
        $this->expectException(Exception::class);
        $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/not-found', $this->baseNode);
    }

    /**
     * @test
     */
    public function linkingServiceThrowsAnExceptionWhenItIsGivenAnEmptyString()
    {
        $this->expectException(Exception::class);
        $this->linkingService->createNodeUri($this->controllerContext, '', $this->baseNode);
    }

    /**
     * @test
     */
    public function linkingServiceThrowsAnExceptionWhenItIsGivenADifferentObject()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->linkingService->createNodeUri($this->controllerContext, new \stdClass());
    }

    /**
     * @test
     */
    public function linkingServiceStoresLastLinkedNode()
    {
        $targetNodeA = $this->baseNode;
        $targetNodeB = $this->baseNode->getNode('about-us');
        $this->linkingService->createNodeUri($this->controllerContext, $targetNodeA);
        self::assertSame($targetNodeA, $this->linkingService->getLastLinkedNode());
        $this->linkingService->createNodeUri($this->controllerContext, $targetNodeB);
        self::assertSame($targetNodeB, $this->linkingService->getLastLinkedNode());
    }

    /**
     * @test
     */
    public function linkingServiceStoresLastLinkedNodeEvenIfItsAShortcutToAnExternalUri()
    {
        $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/home/shortcuts/shortcut-to-target-uri', $this->baseNode);
        self::assertNotNull($this->linkingService->getLastLinkedNode());
    }

    /**
     * @test
     */
    public function linkingServiceUnsetsLastLinkedNodeOnFailure()
    {
        $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/home', $this->baseNode);
        self::assertNotNull($this->linkingService->getLastLinkedNode());
        try {
            $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/non-existing-node', $this->baseNode);
        } catch (NeosException $exception) {
        }
        self::assertNull($this->linkingService->getLastLinkedNode());
    }


    /**
     * A wrapper function for the appropriate assertion for the Link- and its Uri-ViewHelper derivate.
     * Is overridden in the FunctionalTest for the LinkViewHelper.
     */
    protected function assertOutputLinkValid($expected, $actual)
    {
        self::assertStringEndsWith($expected, $actual);
    }
}
