<?php
namespace Neos\Neos\Tests\Unit\Routing;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ConfigurationContentDimensionPresetSource;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Routing\Exception\NoHomepageException;
use Neos\Neos\Routing\FrontendNodeRoutePartHandler;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Service\ContextFactory;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

/**
 * Test case for the frontend node route part handler
 *
 * This test case still falls into the category of unit tests but makes use of some build methods to create rather
 * extensive set of mock objects which mocks the Content Context, the Site and a node structure as needed by the
 * individual tests. The mocked nodes simulate most of the common functions, but they are note and are not intended to
 * be a complete and realistic replacement for real nodes.
 *
 * Some of the mock object (context and node specifically) use public properties (!) which can be modified by the
 * individual tests to set which child nodes should be available, what the current site node is etc. Check carefully
 * which of these public properties exist and how they are used because they are introduced dynamically (!), like in
 * good old PHP 3 times ...
 */
class FrontendNodeRoutePartHandlerTest extends UnitTestCase
{
    /**
     * @var SystemLoggerInterface
     */
    protected $mockSystemLogger;

    /**
     * @var ContextFactoryInterface
     */
    protected $mockContextFactory;

    /**
     * @var DomainRepository
     */
    protected $mockDomainRepository;

    /**
     * @var SiteRepository
     */
    protected $mockSiteRepository;

    /**
     * @var ConfigurationContentDimensionPresetSource
     */
    protected $contentDimensionPresetSource;

    /**
     * @var FrontendNodeRoutePartHandler
     */
    protected $routePartHandler;

    /**
     * Setup the most commonly used mocks and a real FrontendRoutePartHandler. The mock objects created by this function
     * will not be sufficient for most tests, but they are the lowest common denominator.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->routePartHandler = new FrontendNodeRoutePartHandler();
        $this->routePartHandler->setName('node');

        // The mockContextFactory is configured to return the last mock context which has been built with buildMockContext():
        $mockContextFactory = $this->getMockBuilder(ContextFactory::class)->setMethods(array('create'))->getMock();
        $mockContextFactory->mockContext = null;
        $mockContextFactory->expects($this->any())->method('create')->will($this->returnCallback(function ($contextProperties) use ($mockContextFactory) {
            if (isset($contextProperties['currentSite'])) {
                $mockContextFactory->mockContext->mockSite = $contextProperties['currentSite'];
            }
            if (isset($contextProperties['currentDomain'])) {
                $mockContextFactory->mockContext->mockDomain = $contextProperties['currentDomain'];
            }
            if (isset($contextProperties['dimensions'])) {
                $mockContextFactory->mockContext->mockDimensions = $contextProperties['dimensions'];
            }
            if (isset($contextProperties['targetDimensions'])) {
                $mockContextFactory->mockContext->mockTargetDimensions = $contextProperties['targetDimensions'];
            }
            return $mockContextFactory->mockContext;
        }));
        $this->mockContextFactory = $mockContextFactory;
        $this->inject($this->routePartHandler, 'contextFactory', $this->mockContextFactory);

        $this->mockSystemLogger = $this->createMock(SystemLoggerInterface::class);
        $this->inject($this->routePartHandler, 'systemLogger', $this->mockSystemLogger);

        $this->inject($this->routePartHandler, 'securityContext', new SecurityContext());

        $this->mockDomainRepository = $this->getMockBuilder(DomainRepository::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->routePartHandler, 'domainRepository', $this->mockDomainRepository);

        $this->mockSiteRepository = $this->getMockBuilder(SiteRepository::class)->disableOriginalConstructor()->getMock();
        $this->mockSiteRepository->expects($this->any())->method('findFirstOnline')->will($this->returnValue(null));
        $this->inject($this->routePartHandler, 'siteRepository', $this->mockSiteRepository);

        $this->contentDimensionPresetSource = new ConfigurationContentDimensionPresetSource();
        $this->contentDimensionPresetSource->setConfiguration(array());
        $this->inject($this->routePartHandler, 'contentDimensionPresetSource', $this->contentDimensionPresetSource);
    }

    /**
     * Data provider for
     *
     *    resolveConsidersDimensionValuesPassedViaTheContextPathForRenderingTheUrl()
     *    matchConsidersDimensionValuePresetsSpecifiedInTheRequestUriWhileBuildingTheContext()
     *
     * @return array
     */
    public function contextPathsAndRequestPathsDataProvider()
    {
        return array(
            array('/sites/examplecom@live;language=en_US', ''),
            array('/sites/examplecom@user-robert;language=de_DE,en_US', 'de_global'),
            array('/sites/examplecom/features@user-robert;language=de_DE,en_US', 'de_global/features'),
            array('/sites/examplecom/features@user-robert;language=en_US', 'en_global/features'),
            array('/sites/examplecom/features@user-robert;language=de_DE,en_US&country=global', 'de_global/features'),
            array('/sites/examplecom/features@user-robert;country=de', 'en_de/features')
        );
    }

    /**
     * @test
     */
    public function matchReturnsTrueIfTheNodeExists()
    {
        $mockContext = $this->buildMockContext(array('workspaceName' => 'live'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $expectedContextPath = '/sites/examplecom/home';

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';
        $mockSubNode->expects($this->any())->method('getContextPath')->will($this->returnValue($expectedContextPath));

        $routePath = 'home';
        $this->assertTrue($this->routePartHandler->match($routePath));
        $this->assertSame($expectedContextPath, $this->routePartHandler->getValue());
    }

    /**
     * If convertRequestPathToNode() throws any exception and the request path is '' a "missing homepage" message should appear.
     *
     * @test
     * @expectedException \Neos\Neos\Routing\Exception\NoHomepageException
     */
    public function matchThrowsAnExceptionIfNoHomepageExists()
    {
        $this->buildMockContext(array('workspaceName' => 'live'));
        $routePath = '';
        $this->routePartHandler->match($routePath);
    }
    /**
     * @test
     */
    public function matchReturnsFalseIfASiteExistsButNoSiteNodeExists()
    {
        $mockContext = $this->buildMockContext(array('workspaceName' => 'live'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();

        $routePath = 'home';
        $this->assertFalse($this->routePartHandler->match($routePath));
    }

    /**
     * @test
     */
    public function matchReturnsFalseIfTheNodeCouldNotBeFound()
    {
        $mockContext = $this->buildMockContext(array('workspaceName' => 'live'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';

        $routePath = 'home/about-us';
        $this->assertFalse($this->routePartHandler->match($routePath));
    }

    /**
     * If a node matches the given request path but the context contains now Workspace, match() must return FALSE
     *
     * @test
     */
    public function matchReturnsFalseIfTheWorkspaceCouldNotBeFound()
    {
        $mockContext = $this->buildMockContext(array('workspaceName' => 'live'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';

        // match() should only return FALSE because we remove the workspace, without the following line it returns TRUE:
        $mockContext->mockWorkspace = null;

        $routePath = 'home';
        $this->assertFalse($this->routePartHandler->match($routePath));
    }

    /**
     * If a node matches the given request path, the node's context path is stored in $this->value and TRUE is returned.
     *
     * @test
     */
    public function valueContainsContextPathOfFoundNode()
    {
        $mockContext = $this->buildMockContext(array('workspaceName' => 'live'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'features');
        $mockSubNode->mockProperties['uriPathSegment'] = 'features';
        $mockSubNode->expects($this->any())->method('getContextPath')->will($this->returnValue('/sites/examplecom/features@user-robert'));

        $routePath = 'features';
        $this->assertTrue($this->routePartHandler->match($routePath));
        $this->assertEquals('/sites/examplecom/features@user-robert', $this->routePartHandler->getValue());
    }

    /**
     * If the route part handler has been configured to only match on a site node (via the "onlyMatchSiteNodes" option),
     * it returns FALSE if no node matched or if the matched node is not a site node.
     *
     * This case is needed in order allow routes matching "/" without a suffix for a website's homepage even if "defaultUriSuffix"
     * is empty.
     *
     * @test
     */
    public function matchReturnsFalseOnNotMatchingSiteNodes()
    {
        $mockContext = $this->buildMockContext(array('workspaceName' => 'live'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'features');
        $mockSubNode->mockProperties['uriPathSegment'] = 'features';

        $routePath = 'features';
        $this->assertTrue($this->routePartHandler->match($routePath));
        $routePath = '';
        $this->assertTrue($this->routePartHandler->match($routePath));

        $this->routePartHandler->setOptions(array('onlyMatchSiteNodes' => true));

        $routePath = 'features';
        $this->assertFalse($this->routePartHandler->match($routePath));
        $routePath = '';
        $this->assertTrue($this->routePartHandler->match($routePath));
    }


    /**
     * @test
     */
    public function matchCreatesContextForLiveWorkspaceByDefault()
    {
        $mockContext = $this->buildMockContext(array('workspaceName' => 'live'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';

        $that = $this;
        $this->mockContextFactory->expects($this->once())->method('create')->will($this->returnCallback(function ($contextProperties) use ($that, $mockContext) {
            $that->assertSame('live', $contextProperties['workspaceName']);
            return $mockContext;
        }));

        $routePath = 'home';
        $this->routePartHandler->match($routePath);
    }

    /**
     * @test
     */
    public function matchCreatesContextForCustomWorkspaceIfSpecifiedInNodeContextPath()
    {
        $mockContext = $this->buildMockContext(array('workspaceName' => 'live'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';

        $that = $this;
        $this->mockContextFactory->expects($this->once())->method('create')->will($this->returnCallback(function ($contextProperties) use ($that, $mockContext) {
            $that->assertSame('user-john', $contextProperties['workspaceName']);
            return $mockContext;
        }));

        $routePath = 'home@user-john';
        $this->routePartHandler->match($routePath);
    }

    /**
     * @test
     * @dataProvider contextPathsAndRequestPathsDataProvider
     */
    public function matchConsidersDimensionValuePresetsSpecifiedInTheRequestUriWhileBuildingTheContext($expectedContextPath, $requestPath)
    {
        $this->contentDimensionPresetSource->setConfiguration(array(
            'language' => array(
                'default' => 'en_US',
                'defaultPreset' => 'en_US',
                'presets' => array(
                    'en_US' => array(
                        'label' => 'English (US)',
                        'values' => array('en_US'),
                        'uriSegment' => 'en'
                    ),
                    'de_DE' => array(
                        'label' => 'Deutsch',
                        'values' => array('de_DE', 'en_US'),
                        'uriSegment' => 'de'
                    )
                )
            ),
            'country' => array(
                'default' => 'global',
                'defaultPreset' => 'global',
                'presets' => array(
                    'global' => array(
                        'label' => 'Global',
                        'values' => array('global'),
                        'uriSegment' => 'global'
                    ),
                    'us' => array(
                        'label' => 'USA',
                        'values' => array('us'),
                        'uriSegment' => 'us'
                    ),
                    'de' => array(
                        'label' => 'Deutschland',
                        'values' => array('de'),
                        'uriSegment' => 'de'
                    )
                )
            )
        ));

        $mockContext = $this->buildMockContext(array('workspaceName' => 'live'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'features');
        $mockSubNode->mockProperties['uriPathSegment'] = 'features';

        $this->assertTrue($this->routePartHandler->match($requestPath));
        $value = $this->routePartHandler->getValue();
    }

    /**
     * Note: In this case the ".html" suffix is not stripped of the context path because no split string is set
     *
     * @test
     */
    public function matchReturnsFalseIfContextPathIsInvalid()
    {
        $mockContext = $this->buildMockContext(array('workspaceName' => 'live'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';

        $routePath = 'home@user-robert.html';
        $this->assertFalse($this->routePartHandler->match($routePath));
    }

    /**
     * @test
     */
    public function matchStripsOffSuffixIfSplitStringIsSpecified()
    {
        $mockContext = $this->buildMockContext(array('workspaceName' => 'live'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';

        $this->routePartHandler->setOptions(array('splitString' => '.'));

        $routePath = 'home@user-robert.html';
        $this->assertFalse($this->routePartHandler->match($routePath));
    }

    /**
     * @test
     */
    public function resolveSetsValueToContextPathIfPassedNodeCouldBeResolved()
    {
        $mockContext = $this->buildMockContext(array('workspaceName' => 'user-robert'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';
        $mockSubNode->expects($this->any())->method('getContextPath')->will($this->returnValue('/sites/examplecom/home@user-robert'));

        $mockSubSubNode = $this->buildSubNode($mockSubNode, 'ae178bc9184');
        $mockSubSubNode->mockProperties['uriPathSegment'] = 'coffee-brands';
        $mockSubSubNode->expects($this->any())->method('getContextPath')->will($this->returnValue('/sites/examplecom/home/ae178bc9184@user-robert'));

        $routeValues = array('node' => $mockSubSubNode);
        $this->assertTrue($this->routePartHandler->resolve($routeValues));
        $this->assertSame('home/coffee-brands@user-robert', $this->routePartHandler->getValue());
    }

    /**
     * @test
     */
    public function resolveSetsValueToContextPathIfPassedNodeCouldBeResolvedButIsInAnotherSite()
    {
        $mockContext = $this->buildMockContext(array('workspaceName' => 'user-robert'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/currentdotcom');

        $mockSubNode = $this->buildSubNode($this->buildSiteNode($mockContext, '/sites/otherdotcom'), 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';
        $mockSubNode->expects($this->any())->method('getContextPath')->will($this->returnValue('/sites/otherdotcom/home@user-robert'));

        $mockSubSubNode = $this->buildSubNode($mockSubNode, 'ae178bc9184');
        $mockSubSubNode->mockProperties['uriPathSegment'] = 'coffee-brands';
        $mockSubSubNode->expects($this->any())->method('getContextPath')->will($this->returnValue('/sites/otherdotcom/home/ae178bc9184@user-robert'));

        $routeValues = array('node' => $mockSubSubNode);
        $this->assertTrue($this->routePartHandler->resolve($routeValues));
        $this->assertSame('home/coffee-brands@user-robert', $this->routePartHandler->getValue());
    }

    /**
     * @test
     */
    public function resolveReturnsFalseIfGivenRouteValueIsNeitherStringNorNode()
    {
        $mockContext = $this->buildMockContext(array('workspaceName' => 'live'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';

        $routeValues = array('node' => null);
        $this->assertFalse($this->routePartHandler->resolve($routeValues));

        $routeValues = array('node' => 42);
        $this->assertFalse($this->routePartHandler->resolve($routeValues));
    }

    /**
     * @test
     */
    public function resolveCreatesContextForLiveWorkspaceByDefault()
    {
        $mockContext = $this->buildMockContext(array('workspaceName' => 'live'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';

        // resolveValue() will use $contentContext to retrieve the resolved node:
        $mockContext->expects($this->any())->method('getNode')->will($this->returnCallback(function ($nodePath) use ($mockSubNode) {
            return ($nodePath === '/sites/examplecom/home') ? $mockSubNode : null;
        }));

        $that = $this;
        $this->mockContextFactory->expects($this->once())->method('create')->will($this->returnCallback(function ($contextProperties) use ($that, $mockContext) {
            // The important assertion:
            $that->assertSame('live', $contextProperties['workspaceName']);
            return $mockContext;
        }));

        $routeValues = array('node' => '/sites/examplecom/home');
        $this->assertTrue($this->routePartHandler->resolve($routeValues));
    }

    /**
     * @test
     */
    public function resolveCreatesContextForTheWorkspaceMentionedInTheContextString()
    {
        $mockContext = $this->buildMockContext(array('workspaceName' => 'user-johndoe'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';

        // resolveValue() will use $contentContext to retrieve the resolved node:
        $mockContext->expects($this->any())->method('getNode')->will($this->returnCallback(function ($nodePath) use ($mockSubNode) {
            return ($nodePath === '/sites/examplecom/home') ? $mockSubNode : null;
        }));

        $that = $this;
        $this->mockContextFactory->expects($this->once())->method('create')->will($this->returnCallback(function ($contextProperties) use ($that, $mockContext) {
            // The important assertion:
            $that->assertSame('user-johndoe', $contextProperties['workspaceName']);
            return $mockContext;
        }));

        $routeValues = array('node' => '/sites/examplecom/home@user-johndoe');
        $this->assertTrue($this->routePartHandler->resolve($routeValues));
    }

    /**
     * @test
     */
    public function resolveReturnsFalseIfWorkspaceMentionedInTheContextDoesNotExist()
    {
        $mockContext = $this->buildMockContext(array('workspaceName' => 'user-johndoe'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');
        $mockContext->mockSiteNode = $mockSiteNode;

        $mockContext->expects($this->any())->method('getNode')->will($this->returnCallback(function ($nodePath) use ($mockSiteNode) {
            return ($nodePath === '/sites/examplecom') ? $mockSiteNode : null;
        }));

        // resolve() should only return FALSE because we remove the workspace, without the following line it returns TRUE:
        $mockContext->mockWorkspace = null;

        $routeValues = array('node' => '/sites/examplecom@user-johndoe');
        $this->assertFalse($this->routePartHandler->resolve($routeValues));
    }

    /**
     * @test
     */
    public function resolveReturnsFalseIfNodeMentionedInTheContextPathDoesNotExist()
    {
        $mockContext = $this->buildMockContext(array('workspaceName' => 'live'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');
        $mockContext->mockSiteNode = $mockSiteNode;

        $routeValues = array('node' => '/sites/examplecom/not-found');
        $this->assertFalse($this->routePartHandler->resolve($routeValues));
    }

    /**
     * @test
     */
    public function resolveReturnsFalseIfNodeIsNoDocument()
    {
        $mockContext = $this->buildMockContext(array('workspaceName' => 'live'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        // The important bit: sub node is not a document but Neos.Neos:Content
        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'some-content', 'Neos.Neos:Content');
        $mockSubNode->mockProperties['uriPathSegment'] = 'some-content';

        $mockContext->expects($this->any())->method('getNode')->will($this->returnCallback(function ($nodePath) use ($mockSubNode) {
            return ($nodePath === '/sites/examplecom/some-content') ? $mockSubNode : null;
        }));

        $routeValues = array('node' => '/sites/examplecom/some-content');
        $this->assertFalse($this->routePartHandler->resolve($routeValues));
    }

    /**
     * @test
     */
    public function resolveReturnsFalseIfOnlyMatchSiteNodesOptionIsSetAndResolvedNodeIsNoSiteNode()
    {
        $this->routePartHandler->setOptions(array('onlyMatchSiteNodes' => true));

        $mockContext = $this->buildMockContext(array('workspaceName' => 'live'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'features');
        $mockSubNode->mockProperties['uriPathSegment'] = 'features';

        $mockContext->expects($this->any())->method('getNode')->will($this->returnCallback(function ($nodePath) use ($mockSubNode) {
            return ($nodePath === '/sites/examplecom/features') ? $mockSubNode : null;
        }));

        $routeValues = array('node' => '/sites/examplecom/features');
        $this->assertFalse($this->routePartHandler->resolve($routeValues));
    }

    /**
     * @dataProvider contextPathsAndRequestPathsDataProvider
     * @test
     */
    public function resolveConsidersDimensionValuesPassedViaTheContextPathForRenderingTheUrl($contextPath, $expectedUriPath)
    {
        $this->contentDimensionPresetSource->setConfiguration(array(
            'language' => array(
                'default' => 'en_US',
                'defaultPreset' => 'en_US',
                'presets' => array(
                    'en_US' => array(
                        'label' => 'English (US)',
                        'values' => array('en_US'),
                        'uriSegment' => 'en'
                    ),
                    'de_DE' => array(
                        'label' => 'Deutsch',
                        'values' => array('de_DE', 'en_US'),
                        'uriSegment' => 'de'
                    )
                )
            ),
            'country' => array(
                'default' => 'global',
                'defaultPreset' => 'global',
                'presets' => array(
                    'global' => array(
                        'label' => 'Global',
                        'values' => array('global'),
                        'uriSegment' => 'global'
                    ),
                    'us' => array(
                        'label' => 'USA',
                        'values' => array('us'),
                        'uriSegment' => 'us'
                    ),
                    'de' => array(
                        'label' => 'Deutschland',
                        'values' => array('de'),
                        'uriSegment' => 'de'
                    )
                )
            )
        ));

        $mockContext = $this->buildMockContext(array('workspaceName' => 'live'));
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();

        $mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');
        $mockSiteNode->expects($this->any())->method('getContextPath')->will($this->returnValue('/sites/examplecom'));
        $mockContext->mockSiteNode = $mockSiteNode;

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'features');
        $mockSubNode->mockProperties['uriPathSegment'] = 'features';
        $mockSubNode->expects($this->any())->method('getContextPath')->will($this->returnValue('/sites/examplecom/features'));

        $mockContext->expects($this->any())->method('getNode')->will($this->returnCallback(function ($nodePath) use ($mockSubNode, $mockSiteNode) {
            switch ($nodePath) {
                case '/sites/examplecom/features':
                    return $mockSubNode;
                case '/sites/examplecom':
                    return $mockSiteNode;
                default:
                    return null;
            }
        }));

        $routeValues = array('node' => $contextPath);
        $this->assertTrue($this->routePartHandler->resolve($routeValues));
        $this->assertSame($expectedUriPath, $this->routePartHandler->getValue());
    }

    /**
     * data provider for dimensionRequestPathRegex
     */
    public function dimensionRequestPathMatcherDataProvider()
    {
        return array(
            'an empty request path does not match' => array(
                'requestPath' => '',
                'doesMatch' => false,
                'expected' => array()
            ),
            'a request path only containing a dimension matches' => array(
                'requestPath' => 'de_global',
                'doesMatch' => true,
                'expected' => array(
                    0 => 'de_global',
                    'firstUriPart' => 'de_global',
                    1 => 'de_global',
                    'remainingRequestPath' => '',
                    2 => ''
                )
            ),
            'a request path only containing a dimension and a workspace matches' => array(
                'requestPath' => 'de_global@user-admin',
                'doesMatch' => true,
                'expected' => array(
                    0 => 'de_global@user-admin',
                    'firstUriPart' => 'de_global',
                    1 => 'de_global',
                    'remainingRequestPath' => '@user-admin',
                    2 => '@user-admin'
                )
            ),
            'a longer request path is split correctly' => array(
                'requestPath' => 'de_global/foo/bar?baz=foo[]',
                'doesMatch' => true,
                'expected' => array(
                    0 => 'de_global/foo/bar?baz=foo[]',
                    'firstUriPart' => 'de_global',
                    1 => 'de_global',
                    'remainingRequestPath' => 'foo/bar?baz=foo[]',
                    2 => 'foo/bar?baz=foo[]'
                )
            ),
            'a longer request path is split correctly, also if it contains a workspace' => array(
                'requestPath' => 'de_global/foo/bar@user-admin',
                'doesMatch' => true,
                'expected' => array(
                    0 => 'de_global/foo/bar@user-admin',
                    'firstUriPart' => 'de_global',
                    1 => 'de_global',
                    'remainingRequestPath' => 'foo/bar@user-admin',
                    2 => 'foo/bar@user-admin'
                )
            )
        );
    }

    /**
     * @dataProvider dimensionRequestPathMatcherDataProvider
     * @test
     */
    public function dimensionRequestPathRegex($requestPath, $doesMatch, $expected)
    {
        $matches = array();
        $this->assertSame($doesMatch, (boolean)preg_match(FrontendNodeRoutePartHandler::DIMENSION_REQUEST_PATH_MATCHER, $requestPath, $matches));
        $this->assertSame($expected, $matches);
    }

    /********************************************************************************************************************
     *
     *
     * HELPER METHODS
     *
     *
     ********************************************************************************************************************/

    /**
     * Builds a mock ContentContext based on the given context properties and returns it.
     *
     * Note that the mockContextFactory is also configured (in setUp()) to return the mock context built by this method
     * NO MATTER IF THE CONTEXT PROPERTIES MATCH OR NOT! This is to keep mockery a bit simpler - enough for our purpose.
     *
     * Whenever we need to support scenarios where multiple contexts come into play, this method must be refactored.
     *
     * @param array $contextProperties The context properties, "workspaceName" is mandatory
     * @return ContentContext
     */
    protected function buildMockContext(array $contextProperties)
    {
        if (!isset($contextProperties['currentDateTime'])) {
            $contextProperties['currentDateTime'] = new \DateTime;
        }

        $mockWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue($contextProperties['workspaceName']));

        $mockContext = $this->getMockBuilder(ContentContext::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockWorkspace = $mockWorkspace;
        $mockContext->expects($this->any())->method('getWorkspace')->will($this->returnCallback(function () use ($mockContext) {
            return $mockContext->mockWorkspace;
        }));

        $mockContext->expects($this->any())->method('getWorkspaceName')->will($this->returnCallback(function () use ($mockContext) {
            return $mockContext->mockWorkspace->getName();
        }));

        $mockContext->mockDomain = null;
        $mockContext->expects($this->any())->method('getCurrentDomain')->will($this->returnCallback(function () use ($mockContext) {
            return $mockContext->mockDomain;
        }));

        $mockContext->mockSite = null;
        $mockContext->expects($this->any())->method('getCurrentSite')->will($this->returnCallback(function () use ($mockContext) {
            return $mockContext->mockSite;
        }));

        $mockContext->mockDimensions = array();
        $mockContext->expects($this->any())->method('getDimensions')->will($this->returnCallback(function () use ($mockContext) {
            return $mockContext->mockDimensions;
        }));

        $mockContext->mockTargetDimensions = array();
        $mockContext->expects($this->any())->method('getTargetDimensions')->will($this->returnCallback(function () use ($mockContext) {
            return $mockContext->mockTargetDimensions;
        }));

        $mockContext->expects($this->any())->method('getProperties')->will($this->returnCallback(function () use ($mockContext, $contextProperties) {
            return array(
                'workspaceName' => $contextProperties['workspaceName'],
                'currentDateTime' => $contextProperties['currentDateTime'],
                'dimensions' => $mockContext->getDimensions(),
                'targetDimensions' => $mockContext->getTargetDimensions(),
                'invisibleContentShown' => isset($contextProperties['invisibleContentShown']) ? $contextProperties['invisibleContentShown'] : false,
                'removedContentShown' => isset($contextProperties['removedContentShown']) ? $contextProperties['removedContentShown'] : false,
                'inaccessibleContentShown' => isset($contextProperties['inaccessibleContentShown']) ? $contextProperties['inaccessibleContentShown'] : false,
                'currentSite' => $mockContext->getCurrentSite(),
                'currentDomain' => $mockContext->getCurrentDomain()
            );
        }));

        $this->mockContextFactory->mockContext = $mockContext;

        return $mockContext;
    }

    /**
     * Builds a mock node which responds to most function calls like a real node. Your mileage may vary. Carefully read
     * what the mock can actually do before you use it in your own additional tests.
     *
     * @param ContentContext $mockContext
     * @param string $nodeName
     * @param string $nodeTypeName
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function buildNode(ContentContext $mockContext, $nodeName, $nodeTypeName = 'Neos.Neos:Document')
    {
        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNodeType->expects($this->any())->method('isOfType')->will($this->returnCallback(function ($expectedNodeTypeName) use ($nodeTypeName) {
            return $expectedNodeTypeName === $nodeTypeName;
        }));

        $mockNode = $this->createMock(NodeInterface::class);
        $mockNode->expects($this->any())->method('getContext')->will($this->returnValue($mockContext));
        $mockNode->expects($this->any())->method('getIdentifier')->will($this->returnValue('site-node-uuid'));
        $mockNode->expects($this->any())->method('getName')->will($this->returnValue($nodeName));
        $mockNode->expects($this->any())->method('getNodeType')->will($this->returnValue($mockNodeType));

        // Parent node is set by buildSubNode()
        $mockNode->mockParentNode = null;
        $mockNode->expects($this->any())->method('getParent')->will($this->returnCallback(function () use ($mockNode) {
            return $mockNode->mockParentNode;
        }));

        $mockNode->mockChildNodes = array();
        $mockNode->expects($this->any())->method('getChildNodes')->will($this->returnCallback(function ($nodeTypeFilter) use ($mockNode) {
            return $mockNode->mockChildNodes;
        }));

        $mockNode->expects($this->any())->method('getNode')->will($this->returnCallback(function ($relativeNodePath) use ($mockNode) {
            $foundNode = null;
            foreach ($mockNode->mockChildNodes as $nodeName => $mockChildNode) {
                if ($nodeName === $relativeNodePath) {
                    $foundNode = $mockChildNode;
                }
            }
            return $foundNode;
        }));

        $mockNode->mockProperties = array();
        $mockNode->expects($this->any())->method('getProperties')->will($this->returnCallback(function () use ($mockNode) {
            return $mockNode->mockProperties;
        }));

        $mockNode->mockProperties = array();
        $mockNode->expects($this->any())->method('getProperty')->will($this->returnCallback(function ($propertyName) use ($mockNode) {
            return isset($mockNode->mockProperties[$propertyName]) ? $mockNode->mockProperties[$propertyName] : null;
        }));
        $mockNode->expects($this->any())->method('hasProperty')->will($this->returnCallback(function ($propertyName) use ($mockNode) {
            return array_key_exists($propertyName, $mockNode->mockProperties);
        }));

        return $mockNode;
    }

    /**
     * Creates a mock site node
     *
     * @param ContentContext $mockContext
     * @param string $nodePath
     * @return NodeInterface
     */
    protected function buildSiteNode(ContentContext $mockContext, $nodePath)
    {
        $nodeName = substr($nodePath, strrpos($nodePath, '/') + 1);
        $parentNodePath = substr($nodePath, 0, strrpos($nodePath, '/'));
        $mockSiteNode = $this->buildNode($mockContext, $nodeName);
        $mockSiteNode->expects($this->any())->method('getPath')->will($this->returnValue($nodePath));
        $mockSiteNode->expects($this->any())->method('getParentPath')->will($this->returnValue($parentNodePath));
        $mockContext->expects($this->any())->method('getCurrentSiteNode')->will($this->returnValue($mockSiteNode));
        return $mockSiteNode;
    }

    /**
     * Creates a mock sub node of the given parent node
     *
     * @param NodeInterface $mockParentNode
     * @param string $nodeName
     * @return NodeInterface
     */
    protected function buildSubNode($mockParentNode, $nodeName, $nodeTypeName = 'Neos.Neos:Document')
    {
        $mockNode = $this->buildNode($mockParentNode->getContext(), $nodeName, $nodeTypeName);
        $mockNode->mockParentNode = $mockParentNode;

        $mockParentNode->mockChildNodes[$nodeName] = $mockNode;
        $mockNode->expects($this->any())->method('getChildNodes')->will($this->returnCallback(function ($nodeTypeFilter) use ($mockNode) {
            return $mockNode->mockChildNodes;
        }));
        return $mockNode;
    }
}
