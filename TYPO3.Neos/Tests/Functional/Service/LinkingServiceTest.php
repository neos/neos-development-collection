<?php
namespace TYPO3\Neos\Tests\Functional\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Neos\Exception as NeosException;

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
     * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @var \TYPO3\Flow\Property\PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
     */
    protected $controllerContext;

    /**
     * @var \TYPO3\Neos\Domain\Service\ContentContext
     */
    protected $contentContext;

    /**
     * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var \TYPO3\Neos\Service\LinkingService
     */
    protected $linkingService;

    /**
     * @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface
     */
    protected $baseNode;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->nodeDataRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository');
        $domainRepository = $this->objectManager->get('TYPO3\Neos\Domain\Repository\DomainRepository');
        $siteRepository = $this->objectManager->get('TYPO3\Neos\Domain\Repository\SiteRepository');
        $this->contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
        $contextProperties = array(
            'workspaceName' => 'live'
        );
        $contentContext = $this->contextFactory->create($contextProperties);
        $siteImportService = $this->objectManager->get('TYPO3\Neos\Domain\Service\SiteImportService');
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

        $this->propertyMapper = $this->objectManager->get('TYPO3\Flow\Property\PropertyMapper');

        $this->baseNode = $this->contentContext->getCurrentSiteNode()->getNode('home');

        $this->linkingService = $this->objectManager->get('TYPO3\Neos\Service\LinkingService');
        /** @var $requestHandler \TYPO3\Flow\Tests\FunctionalTestRequestHandler */
        $requestHandler = self::$bootstrap->getActiveRequestHandler();
        $this->controllerContext = new ControllerContext(new ActionRequest($requestHandler->getHttpRequest()), $requestHandler->getHttpResponse(), new Arguments(array()), new UriBuilder());
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $this->inject($this->contextFactory, 'contextInstances', array());
        $this->inject($this->objectManager->get('TYPO3\Media\TypeConverter\AssetInterfaceConverter'), 'resourcesAlreadyConvertedToAssets', array());
    }

    /**
     * @test
     */
    public function linkingServiceCreatesUriViaGivenNodeObject()
    {
        $targetNode = $this->propertyMapper->convert('/sites/example/home', 'TYPO3\TYPO3CR\Domain\Model\Node');
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
        return array(
            array('node://aeabe76a-551a-495f-a324-ad9a86b2aff7', true),
            array('asset://aeabe76a-551a-495f-a324-ad9a86b2aff7', true),
            array('random://aeabe76a-551a-495f-a324-ad9a86b2aff7', false)
        );
    }

    /**
     * @dataProvider supportedSchemesDataProvider
     * @test
     */
    public function linkingServiceOnlySupportsNodesAndAssetSchemes($scheme, $match)
    {
        $this->assertSame($match, $this->linkingService->hasSupportedScheme($scheme));
    }

    /**
     * @test
     */
    public function linkingServiceCanGetSchemeFromUrl()
    {
        $this->assertSame('node', $this->linkingService->getScheme('node://aeabe76a-551a-495f-a324-ad9a86b2aff7'));
    }

    /**
     * @test
     */
    public function linkingServiceCanResolveNodeUri()
    {
        $this->assertSame('/en/home.html', $this->linkingService->resolveNodeUri('node://3239baee-3e7f-785c-0853-f4302ef32570', $this->baseNode, $this->controllerContext));
    }

    /**
     * @test
     */
    public function linkingServiceResolveNodeUriReturnsNullForUnresolvableNodes()
    {
        $this->assertSame(null, $this->linkingService->resolveNodeUri('node://3239baee-3e7f-785c-0853-f4302ef3257x', $this->baseNode, $this->controllerContext));
    }

    /**
     * @test
     */
    public function linkingServiceCanResolveAssetUri()
    {
        $this->assertSame('http://localhost/_Resources/Testing/Persistent/bed9a3e45070e97b921877e2bd9c35ba368beca0/TYPO3_Neos-logo_sRGB_color.pdf', $this->linkingService->resolveAssetUri('asset://1af89e5c-9e23-9a9d-ae15-1d77160cfb57'));
    }

    /**
     * @test
     */
    public function linkingServiceResolveAssetUriReturnsNullForUnresolvableAssets()
    {
        $this->assertSame(null, $this->linkingService->resolveAssetUri('asset://89cd85cc-270e-0902-7113-d14ac7539c7x'));
    }

    /**
     * @test
     */
    public function linkingServiceCanConvertUriToObject()
    {
        $assetRepository = $this->objectManager->get('TYPO3\Media\Domain\Repository\AssetRepository');
        $asset = $assetRepository->findByIdentifier('89cd85cc-270e-0902-7113-d14ac7539c75');

        $this->assertSame($this->baseNode, $this->linkingService->convertUriToObject('node://3239baee-3e7f-785c-0853-f4302ef32570', $this->baseNode));
        $this->assertSame($asset, $this->linkingService->convertUriToObject('asset://89cd85cc-270e-0902-7113-d14ac7539c75'));
    }

    /**
     * @test
     * @expectedException \TYPO3\Neos\Exception
     */
    public function linkingServiceThrowsAnExceptionWhenTryingToLinkToANonExistingNode()
    {
        $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/not-found', $this->baseNode);
    }

    /**
     * @test
     * @expectedException \TYPO3\Neos\Exception
     */
    public function linkingServiceThrowsAnExceptionWhenItIsGivenAnEmptyString()
    {
        $this->linkingService->createNodeUri($this->controllerContext, '', $this->baseNode);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function linkingServiceThrowsAnExceptionWhenItIsGivenADifferentObject()
    {
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
        $this->assertSame($targetNodeA, $this->linkingService->getLastLinkedNode());
        $this->linkingService->createNodeUri($this->controllerContext, $targetNodeB);
        $this->assertSame($targetNodeB, $this->linkingService->getLastLinkedNode());
    }

    /**
     * @test
     */
    public function linkingServiceStoresLastLinkedNodeEvenIfItsAShortcutToAnExternalUri()
    {
        $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/home/shortcuts/shortcut-to-target-uri', $this->baseNode);
        $this->assertNotNull($this->linkingService->getLastLinkedNode());
    }

    /**
     * @test
     */
    public function linkingServiceUnsetsLastLinkedNodeOnFailure()
    {
        $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/home', $this->baseNode);
        $this->assertNotNull($this->linkingService->getLastLinkedNode());
        try {
            $this->linkingService->createNodeUri($this->controllerContext, '/sites/example/non-existing-node', $this->baseNode);
        } catch (NeosException $exception) {
        }
        $this->assertNull($this->linkingService->getLastLinkedNode());
    }


    /**
     * A wrapper function for the appropriate assertion for the Link- and its Uri-ViewHelper derivate.
     * Is overridden in the FunctionalTest for the LinkViewHelper.
     */
    protected function assertOutputLinkValid($expected, $actual)
    {
        $this->assertStringEndsWith($expected, $actual);
    }
}
