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

use Neos\Flow\Mvc\Routing\Dto\MatchResult;
use Neos\Flow\Mvc\Routing\Dto\ResolveResult;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Utility\Algorithms;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ConfigurationContentDimensionPresetSource;
use Neos\Neos\Domain\Service\NodeShortcutResolver;
use Neos\Neos\Routing\Exception\NoHomepageException;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\Domain\Model\Workspace;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
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
     * @var Site|MockObject
     */
    protected $mockSite;

    /**
     * @var ConfigurationContentDimensionPresetSource
     */
    protected $contentDimensionPresetSource;

    /**
     * @var FrontendNodeRoutePartHandler
     */
    protected $routePartHandler;

    /**
     * @var NodeShortcutResolver|MockObject
     */
    protected $mockNodeShortcutResolver;

    /**
     * Setup the most commonly used mocks and a real FrontendRoutePartHandler. The mock objects created by this function
     * will not be sufficient for most tests, but they are the lowest common denominator.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->routePartHandler = new FrontendNodeRoutePartHandler();
        $this->routePartHandler->setName('node');

        // The mockContextFactory is configured to return the last mock context which has been built with buildMockContext():
        $mockContextFactory = $this->getMockBuilder(ContextFactory::class)->setMethods(['create'])->getMock();
        $mockContextFactory->mockContext = null;
        $mockContextFactory->expects(self::any())->method('create')->will(self::returnCallback(function ($contextProperties) use ($mockContextFactory) {
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

        $this->mockSystemLogger = $this->createMock(LoggerInterface::class);
        $this->inject($this->routePartHandler, 'systemLogger', $this->mockSystemLogger);

        $this->inject($this->routePartHandler, 'securityContext', new SecurityContext());

        $this->mockDomainRepository = $this->getMockBuilder(DomainRepository::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->routePartHandler, 'domainRepository', $this->mockDomainRepository);

        $this->mockSiteRepository = $this->getMockBuilder(SiteRepository::class)->disableOriginalConstructor()->getMock();
        $this->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $this->mockSite->method('getNodeName')->willReturn('examplecom');
        $this->mockSiteRepository->method('findDefault')->willReturn($this->mockSite);
        $this->inject($this->routePartHandler, 'siteRepository', $this->mockSiteRepository);

        $this->contentDimensionPresetSource = new ConfigurationContentDimensionPresetSource();
        $this->contentDimensionPresetSource->setConfiguration([]);
        $this->inject($this->routePartHandler, 'contentDimensionPresetSource', $this->contentDimensionPresetSource);

        $this->mockNodeShortcutResolver = $this->getMockBuilder(NodeShortcutResolver::class)->disableOriginalConstructor()->getMock();
        $this->mockNodeShortcutResolver->method('resolveShortcutTarget')->willReturnCallback(static function (NodeInterface $node) {
            return $node;
        });
        $this->inject($this->routePartHandler, 'nodeShortcutResolver', $this->mockNodeShortcutResolver);
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
        return [
            ['/sites/examplecom@live;language=en_US', '', true],
            ['/sites/examplecom@live;language=en_US', 'en_global', false],
            ['/sites/examplecom@user-robert;language=de_DE,en_US', 'de_global', false],
            ['/sites/examplecom/features@user-robert;language=de_DE,en_US', 'de_global/features', false],
            ['/sites/examplecom/features@user-robert;language=en_US', 'en_global/features', false],
            ['/sites/examplecom/features@user-robert;language=de_DE,en_US&country=global', 'de_global/features', false],
            ['/sites/examplecom/features@user-robert;country=de', 'en_de/features', false]
        ];
    }

    /**
     * @test
     */
    public function matchWithParametersReturnsMatchResultIfTheNodeExists()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $expectedContextPath = '/sites/examplecom/home';

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';
        $mockSubNode->expects(self::any())->method('getContextPath')->will(self::returnValue($expectedContextPath));

        $routePath = 'home';
        $matchResult = $this->matchForHost($routePath, 'localhost');

        self::assertInstanceOf(MatchResult::class, $matchResult);
        self::assertSame($expectedContextPath, $matchResult->getMatchedValue());
    }

    /**
     * If convertRequestPathToNode() throws any exception and the request path is '' a "missing homepage" message should appear.
     *
     * @test
     */
    public function matchWithParametersThrowsAnExceptionIfNoHomepageExists()
    {
        $this->expectException(NoHomepageException::class);
        $this->buildMockContext(['workspaceName' => 'live']);
        $routePath = '';
        $this->matchForHost($routePath, 'localhost');
    }

    /**
     * @test
     */
    public function matchWithParametersReturnsFalseIfASiteExistsButNoSiteNodeExists()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();

        $routePath = 'home';
        self::assertFalse($this->matchForHost($routePath, 'localhost'));
    }

    /**
     * @test
     */
    public function matchWithParametersReturnsFalseIfTheNodeCouldNotBeFound()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';

        $routePath = 'home/about-us';
        self::assertFalse($this->matchForHost($routePath, 'localhost'));
    }

    /**
     * If a node matches the given request path but the context contains now Workspace, match() must return false
     *
     * @test
     */
    public function matchWithParametersReturnsFalseIfTheWorkspaceCouldNotBeFound()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';

        // match() should only return false because we remove the workspace, without the following line it returns true:
        $mockContext->mockWorkspace = null;

        $routePath = 'home';
        self::assertFalse($this->matchForHost($routePath, 'localhost'));
    }

    /**
     * If a node matches the given request path, the node's context path is stored in $this->value and true is returned.
     *
     * @test
     */
    public function valueContainsContextPathOfFoundNode()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'features');
        $mockSubNode->mockProperties['uriPathSegment'] = 'features';
        $mockSubNode->expects(self::any())->method('getContextPath')->will(self::returnValue('/sites/examplecom/features@user-robert'));

        $routePath = 'features';
        $matchResult = $this->matchForHost($routePath, 'localhost');
        self::assertInstanceOf(MatchResult::class, $matchResult);
        self::assertSame('/sites/examplecom/features@user-robert', $matchResult->getMatchedValue());
    }

    /**
     * If the route part handler has been configured to only match on a site node (via the "onlyMatchSiteNodes" option),
     * it returns false if no node matched or if the matched node is not a site node.
     *
     * This case is needed in order allow routes matching "/" without a suffix for a website's homepage even if "defaultUriSuffix"
     * is empty.
     *
     * @test
     */
    public function matchWithParametersReturnsFalseOnNotMatchingSiteNodes()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'features');
        $mockSubNode->mockProperties['uriPathSegment'] = 'features';

        $routePath = 'features';
        self::assertInstanceOf(MatchResult::class, $this->matchForHost($routePath, 'localhost'));
        $routePath = '';
        self::assertInstanceOf(MatchResult::class, $this->matchForHost($routePath, 'localhost'));

        $this->routePartHandler->setOptions(['onlyMatchSiteNodes' => true]);

        $routePath = 'features';
        self::assertFalse($this->matchForHost($routePath, 'localhost'));
        $routePath = '';
        self::assertInstanceOf(MatchResult::class, $this->matchForHost($routePath, 'localhost'));
    }


    /**
     * @test
     */
    public function matchWithParametersRespectsTheSpecifiedNodeTypeOption()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'features');
        $mockSubNode->mockProperties['uriPathSegment'] = 'features';

        $routePath = 'features';
        self::assertInstanceOf(MatchResult::class, $this->matchForHost($routePath, 'localhost'));

        $this->routePartHandler->setOptions(['nodeType' => 'Some.Package:Some.Node.Type']);

        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNodeType->method('isOfType')->willReturn(false);
        $mockSubNode->method('getNodeType')->willReturn($mockNodeType);

        $routePath = 'features';
        self::assertFalse($this->matchForHost($routePath, 'localhost'));
    }

    /**
     * @test
     */
    public function matchWithParametersCreatesContextForLiveWorkspaceByDefault()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';

        $that = $this;
        $this->mockContextFactory->expects(self::once())->method('create')->will(self::returnCallback(function ($contextProperties) use ($that, $mockContext) {
            $that->assertSame('live', $contextProperties['workspaceName']);
            return $mockContext;
        }));

        $routePath = 'home';
        $this->matchForHost($routePath, 'localhost');
    }

    /**
     * @test
     */
    public function matchWithParametersCreatesContextForCustomWorkspaceIfSpecifiedInNodeContextPath()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';

        $that = $this;
        $this->mockContextFactory->expects(self::once())->method('create')->will(self::returnCallback(function ($contextProperties) use ($that, $mockContext) {
            $that->assertSame('user-john', $contextProperties['workspaceName']);
            return $mockContext;
        }));

        $routePath = 'home@user-john';
        $this->matchForHost($routePath, 'localhost');
    }

    /**
     * @test
     * @dataProvider contextPathsAndRequestPathsDataProvider
     */
    public function matchWithParametersConsidersDimensionValuePresetsSpecifiedInTheRequestUriWhileBuildingTheContext($expectedContextPath, $requestPath, $supportEmptySegmentForDimensions)
    {
        $this->contentDimensionPresetSource->setConfiguration([
            'language' => [
                'default' => 'en_US',
                'defaultPreset' => 'en_US',
                'presets' => [
                    'en_US' => [
                        'label' => 'English (US)',
                        'values' => ['en_US'],
                        'uriSegment' => 'en'
                    ],
                    'de_DE' => [
                        'label' => 'Deutsch',
                        'values' => ['de_DE', 'en_US'],
                        'uriSegment' => 'de'
                    ]
                ]
            ],
            'country' => [
                'default' => 'global',
                'defaultPreset' => 'global',
                'presets' => [
                    'global' => [
                        'label' => 'Global',
                        'values' => ['global'],
                        'uriSegment' => 'global'
                    ],
                    'us' => [
                        'label' => 'USA',
                        'values' => ['us'],
                        'uriSegment' => 'us'
                    ],
                    'de' => [
                        'label' => 'Deutschland',
                        'values' => ['de'],
                        'uriSegment' => 'de'
                    ]
                ]
            ]
        ]);

        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'features');
        $mockSubNode->mockProperties['uriPathSegment'] = 'features';

        $this->inject($this->routePartHandler, 'supportEmptySegmentForDimensions', $supportEmptySegmentForDimensions);
        $requestPath = ltrim($requestPath, '/');
        self::assertInstanceOf(MatchResult::class, $this->matchForHost($requestPath, 'localhost'));
    }

    /**
     * Note: In this case the ".html" suffix is not stripped of the context path because no split string is set
     *
     * @test
     */
    public function matchWithParametersReturnsFalseIfContextPathIsInvalid()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';

        $routePath = 'home@user-robert.html';
        self::assertFalse($this->matchForHost($routePath, 'localhost'));
    }

    /**
     * @test
     */
    public function matchWithParametersStripsOffSuffixIfSplitStringIsSpecified()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';

        $this->routePartHandler->setOptions(['splitString' => '.']);

        $routePath = 'home@user-robert.html';
        self::assertFalse($this->matchForHost($routePath, 'localhost'));
    }

    /**
     * @test
     */
    public function resolveSetsValueToContextPathIfPassedNodeCouldBeResolved()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'user-robert']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';
        $mockSubNode->expects(self::any())->method('getContextPath')->will(self::returnValue('/sites/examplecom/home@user-robert'));

        $mockSubSubNode = $this->buildSubNode($mockSubNode, 'ae178bc9184');
        $mockSubSubNode->mockProperties['uriPathSegment'] = 'coffee-brands';
        $mockSubSubNode->expects(self::any())->method('getContextPath')->will(self::returnValue('/sites/examplecom/home/ae178bc9184@user-robert'));
        $mockSubSubNode->method('getPath')->willReturn('/sites/examplecom/home/ae178bc9184');

        $routeValues = ['node' => $mockSubSubNode];
        $resolveResult = $this->resolveForHost($routeValues, 'localhost');
        self::assertInstanceOf(ResolveResult::class, $resolveResult);
        self::assertSame('home/coffee-brands@user-robert', $resolveResult->getResolvedValue());
    }

    /**
     * @test
     */
    public function resolveSetsValueToContextPathIfPassedNodeCouldBeResolvedButIsInAnotherSite()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'user-robert']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/currentdotcom');

        $mockSubNode = $this->buildSubNode($this->buildSiteNode($mockContext, '/sites/otherdotcom'), 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';
        $mockSubNode->expects(self::any())->method('getContextPath')->will(self::returnValue('/sites/otherdotcom/home@user-robert'));

        $mockSubSubNode = $this->buildSubNode($mockSubNode, 'ae178bc9184');
        $mockSubSubNode->mockProperties['uriPathSegment'] = 'coffee-brands';
        $mockSubSubNode->expects(self::any())->method('getContextPath')->will(self::returnValue('/sites/otherdotcom/home/ae178bc9184@user-robert'));

        $mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $this->mockSiteRepository->method('findOneByNodeName')->with('otherdotcom')->willReturn($mockSite);

        $routeValues = ['node' => $mockSubSubNode];
        $resolveResult = $this->resolveForHost($routeValues, 'localhost');
        self::assertInstanceOf(ResolveResult::class, $resolveResult);
        self::assertSame('home/coffee-brands@user-robert', (string)$resolveResult->getResolvedValue());
    }

    /**
     * @test
     */
    public function resolveReturnsFalseIfGivenRouteValueIsNeitherStringNorNode()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';

        $routeValues = ['node' => null];
        self::assertFalse($this->resolveForHost($routeValues, 'localhost'));

        $routeValues = ['node' => 42];
        self::assertFalse($this->resolveForHost($routeValues, 'localhost'));
    }

    /**
     * @test
     */
    public function resolveCreatesContextForLiveWorkspaceByDefault()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';

        // resolveValue() will use $contentContext to retrieve the resolved node:
        $mockContext->expects(self::any())->method('getNode')->will(self::returnCallback(function ($nodePath) use ($mockSubNode) {
            return ($nodePath === '/sites/examplecom/home') ? $mockSubNode : null;
        }));

        $that = $this;
        $this->mockContextFactory->expects(self::atLeastOnce())->method('create')->will(self::returnCallback(function ($contextProperties) use ($that, $mockContext) {
            // The important assertion:
            $that->assertSame('live', $contextProperties['workspaceName']);
            return $mockContext;
        }));

        $routeValues = ['node' => '/sites/examplecom/home'];
        $resolveResult = $this->resolveForHost($routeValues, 'localhost');
        self::assertInstanceOf(ResolveResult::class, $resolveResult);
    }

    /**
     * @test
     */
    public function resolveCreatesContextForTheWorkspaceMentionedInTheContextString()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'user-johndoe']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'home');
        $mockSubNode->mockProperties['uriPathSegment'] = 'home';
        $mockSubNode->method('getContextPath')->willReturn('/sites/examplecom/home@user-johndoe');

        // resolveValue() will use $contentContext to retrieve the resolved node:
        $mockContext->method('getNode')->willReturnCallback(function ($nodePath) use ($mockSubNode) {
            return ($nodePath === '/sites/examplecom/home') ? $mockSubNode : null;
        });

        $that = $this;
        $this->mockContextFactory->expects(self::atLeastOnce())->method('create')->willReturnCallback(function ($contextProperties) use ($that, $mockContext) {
            // The important assertion:
            $that->assertSame('user-johndoe', $contextProperties['workspaceName']);
            return $mockContext;
        });

        $routeValues = ['node' => '/sites/examplecom/home@user-johndoe'];
        $resolveResult = $this->resolveForHost($routeValues, 'localhost');
        self::assertInstanceOf(ResolveResult::class, $resolveResult);
    }

    /**
     * @test
     */
    public function resolveReturnsFalseIfWorkspaceMentionedInTheContextDoesNotExist()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'user-johndoe']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');
        $mockContext->mockSiteNode = $mockSiteNode;

        $mockContext->expects(self::any())->method('getNode')->will(self::returnCallback(function ($nodePath) use ($mockSiteNode) {
            return ($nodePath === '/sites/examplecom') ? $mockSiteNode : null;
        }));

        // resolve() should only return false because we remove the workspace, without the following line it returns true:
        $mockContext->mockWorkspace = null;

        $routeValues = ['node' => '/sites/examplecom@user-johndoe'];
        self::assertFalse($this->resolveForHost($routeValues, 'localhost'));
    }

    /**
     * @test
     */
    public function resolveReturnsFalseIfNodeMentionedInTheContextPathDoesNotExist()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');
        $mockContext->mockSiteNode = $mockSiteNode;

        $routeValues = ['node' => '/sites/examplecom/not-found'];
        self::assertFalse($this->resolveForHost($routeValues, 'localhost'));
    }

    /**
     * @test
     */
    public function resolveReturnsFalseIfNodeIsNoDocument()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        // The important bit: sub node is not a document but Neos.Neos:Content
        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'some-content', 'Neos.Neos:Content');
        $mockSubNode->mockProperties['uriPathSegment'] = 'some-content';

        $mockContext->expects(self::any())->method('getNode')->will(self::returnCallback(function ($nodePath) use ($mockSubNode) {
            return ($nodePath === '/sites/examplecom/some-content') ? $mockSubNode : null;
        }));

        $routeValues = ['node' => '/sites/examplecom/some-content'];
        self::assertFalse($this->resolveForHost($routeValues, 'localhost'));
    }

    /**
     * @test
     */
    public function resolveReturnsFalseIfOnlyMatchSiteNodesOptionIsSetAndResolvedNodeIsNoSiteNode()
    {
        $this->routePartHandler->setOptions(['onlyMatchSiteNodes' => true]);

        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'features');
        $mockSubNode->mockProperties['uriPathSegment'] = 'features';

        $mockContext->expects(self::any())->method('getNode')->will(self::returnCallback(function ($nodePath) use ($mockSubNode) {
            return ($nodePath === '/sites/examplecom/features') ? $mockSubNode : null;
        }));

        $routeValues = ['node' => '/sites/examplecom/features'];
        self::assertFalse($this->resolveForHost($routeValues, 'localhost'));
    }

    /**
     * @test
     */
    public function resolveRespectsTheSpecifiedNodeTypeOption()
    {
        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'features');
        $mockSubNode->mockProperties['uriPathSegment'] = 'features';

        $mockContext->expects(self::any())->method('getNode')->will(self::returnCallback(function ($nodePath) use ($mockSubNode) {
            return ($nodePath === '/sites/examplecom/features') ? $mockSubNode : null;
        }));

        $routeValues = ['node' => $mockSubNode];

        $this->routePartHandler->setOptions(['nodeType' => 'Some.Package:Some.Node.Type']);

        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNodeType->method('isOfType')->willReturn(false);
        $mockSubNode->method('getNodeType')->willReturn($mockNodeType);

        self::assertFalse($this->resolveForHost($routeValues, 'localhost'));
    }

    /**
     * @dataProvider contextPathsAndRequestPathsDataProvider
     * @test
     */
    public function resolveConsidersDimensionValuesPassedViaTheContextPathForRenderingTheUrl($contextPath, $expectedUriPath, $supportEmptySegmentForDimensions)
    {
        $this->contentDimensionPresetSource->setConfiguration([
            'language' => [
                'default' => 'en_US',
                'defaultPreset' => 'en_US',
                'presets' => [
                    'en_US' => [
                        'label' => 'English (US)',
                        'values' => ['en_US'],
                        'uriSegment' => 'en'
                    ],
                    'de_DE' => [
                        'label' => 'Deutsch',
                        'values' => ['de_DE', 'en_US'],
                        'uriSegment' => 'de'
                    ]
                ]
            ],
            'country' => [
                'default' => 'global',
                'defaultPreset' => 'global',
                'presets' => [
                    'global' => [
                        'label' => 'Global',
                        'values' => ['global'],
                        'uriSegment' => 'global'
                    ],
                    'us' => [
                        'label' => 'USA',
                        'values' => ['us'],
                        'uriSegment' => 'us'
                    ],
                    'de' => [
                        'label' => 'Deutschland',
                        'values' => ['de'],
                        'uriSegment' => 'de'
                    ]
                ]
            ]
        ]);

        $mockContext = $this->buildMockContext(['workspaceName' => 'live']);
        $mockContext->mockSite = $this->getMockBuilder(Site::class)->disableOriginalConstructor()->getMock();

        $mockSiteNode = $this->buildSiteNode($mockContext, '/sites/examplecom');
        $mockSiteNode->expects(self::any())->method('getContextPath')->will(self::returnValue('/sites/examplecom'));
        $mockContext->mockSiteNode = $mockSiteNode;

        $mockSubNode = $this->buildSubNode($mockContext->mockSiteNode, 'features');
        $mockSubNode->mockProperties['uriPathSegment'] = 'features';
        $mockSubNode->expects(self::any())->method('getContextPath')->will(self::returnValue('/sites/examplecom/features'));

        $mockContext->expects(self::any())->method('getNode')->will(self::returnCallback(function ($nodePath) use ($mockSubNode, $mockSiteNode) {
            switch ($nodePath) {
                case '/sites/examplecom/features':
                    return $mockSubNode;
                case '/sites/examplecom':
                    return $mockSiteNode;
                default:
                    return null;
            }
        }));

        $routeValues = ['node' => $contextPath];
        $this->inject($this->routePartHandler, 'supportEmptySegmentForDimensions', $supportEmptySegmentForDimensions);
        $resolveResult = $this->resolveForHost($routeValues, 'localhost');
        self::assertSame($expectedUriPath, $resolveResult->getResolvedValue());
    }

    /**
     * data provider for dimensionRequestPathRegex
     */
    public function dimensionRequestPathMatcherDataProvider()
    {
        return [
            'an empty request path does not match' => [
                'requestPath' => '',
                'doesMatch' => false,
                'expected' => []
            ],
            'a request path only containing a dimension matches' => [
                'requestPath' => 'de_global',
                'doesMatch' => true,
                'expected' => [
                    0 => 'de_global',
                    'firstUriPart' => 'de_global',
                    1 => 'de_global',
                    'remainingRequestPath' => '',
                    2 => ''
                ]
            ],
            'a request path only containing a dimension and a workspace matches' => [
                'requestPath' => 'de_global@user-admin',
                'doesMatch' => true,
                'expected' => [
                    0 => 'de_global@user-admin',
                    'firstUriPart' => 'de_global',
                    1 => 'de_global',
                    'remainingRequestPath' => '@user-admin',
                    2 => '@user-admin'
                ]
            ],
            'a longer request path is split correctly' => [
                'requestPath' => 'de_global/foo/bar?baz=foo[]',
                'doesMatch' => true,
                'expected' => [
                    0 => 'de_global/foo/bar?baz=foo[]',
                    'firstUriPart' => 'de_global',
                    1 => 'de_global',
                    'remainingRequestPath' => 'foo/bar?baz=foo[]',
                    2 => 'foo/bar?baz=foo[]'
                ]
            ],
            'a longer request path is split correctly, also if it contains a workspace' => [
                'requestPath' => 'de_global/foo/bar@user-admin',
                'doesMatch' => true,
                'expected' => [
                    0 => 'de_global/foo/bar@user-admin',
                    'firstUriPart' => 'de_global',
                    1 => 'de_global',
                    'remainingRequestPath' => 'foo/bar@user-admin',
                    2 => 'foo/bar@user-admin'
                ]
            ]
        ];
    }

    /**
     * @dataProvider dimensionRequestPathMatcherDataProvider
     * @test
     */
    public function dimensionRequestPathRegex($requestPath, $doesMatch, $expected)
    {
        $matches = [];
        self::assertSame($doesMatch, (boolean)preg_match(FrontendNodeRoutePartHandler::DIMENSION_REQUEST_PATH_MATCHER, $requestPath, $matches));
        self::assertSame($expected, $matches);
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
     * @throws \Exception
     */
    protected function buildMockContext(array $contextProperties)
    {
        if (!isset($contextProperties['currentDateTime'])) {
            $contextProperties['currentDateTime'] = new \DateTime;
        }

        $mockWorkspace = $this->getMockBuilder(Workspace::class)->disableOriginalConstructor()->getMock();
        $mockWorkspace->expects(self::any())->method('getName')->will(self::returnValue($contextProperties['workspaceName']));

        $mockContext = $this->getMockBuilder(ContentContext::class)->disableOriginalConstructor()->getMock();
        $mockContext->mockWorkspace = $mockWorkspace;
        $mockContext->expects(self::any())->method('getWorkspace')->will(self::returnCallback(function () use ($mockContext) {
            return $mockContext->mockWorkspace;
        }));

        $mockContext->expects(self::any())->method('getWorkspaceName')->will(self::returnCallback(function () use ($mockContext) {
            return $mockContext->mockWorkspace->getName();
        }));

        $mockContext->mockDomain = null;
        $mockContext->expects(self::any())->method('getCurrentDomain')->will(self::returnCallback(function () use ($mockContext) {
            return $mockContext->mockDomain;
        }));

        $mockContext->mockSite = null;
        $mockContext->expects(self::any())->method('getCurrentSite')->will(self::returnCallback(function () use ($mockContext) {
            return $mockContext->mockSite;
        }));

        $mockContext->mockDimensions = [];
        $mockContext->expects(self::any())->method('getDimensions')->will(self::returnCallback(function () use ($mockContext) {
            return $mockContext->mockDimensions;
        }));

        $mockContext->mockTargetDimensions = [];
        $mockContext->expects(self::any())->method('getTargetDimensions')->will(self::returnCallback(function () use ($mockContext) {
            return $mockContext->mockTargetDimensions;
        }));

        $mockContext->expects(self::any())->method('getNodeByIdentifier')->will(self::returnCallback(function ($identifier) use ($mockContext) {
            if (array_key_exists($identifier, $mockContext->mockNodesByIdentifier)) {
                return $mockContext->mockNodesByIdentifier[$identifier];
            }
            return null;
        }));

        $mockContext->expects(self::any())->method('getProperties')->will(self::returnCallback(function () use ($mockContext, $contextProperties) {
            return [
                'workspaceName' => $contextProperties['workspaceName'],
                'currentDateTime' => $contextProperties['currentDateTime'],
                'dimensions' => $mockContext->getDimensions(),
                'targetDimensions' => $mockContext->getTargetDimensions(),
                'invisibleContentShown' => isset($contextProperties['invisibleContentShown']) ? $contextProperties['invisibleContentShown'] : false,
                'removedContentShown' => isset($contextProperties['removedContentShown']) ? $contextProperties['removedContentShown'] : false,
                'inaccessibleContentShown' => isset($contextProperties['inaccessibleContentShown']) ? $contextProperties['inaccessibleContentShown'] : false,
                'currentSite' => $mockContext->getCurrentSite(),
                'currentDomain' => $mockContext->getCurrentDomain()
            ];
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
     * @return \PHPUnit\Framework\MockObject\MockObject
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    protected function buildNode(ContentContext $mockContext, $nodeName, $nodeTypeName = 'Neos.Neos:Document')
    {
        $mockNodeType = $this->getMockBuilder(NodeType::class)->disableOriginalConstructor()->getMock();
        $mockNodeType->expects(self::any())->method('isOfType')->will(self::returnCallback(function ($expectedNodeTypeName) use ($nodeTypeName) {
            return $expectedNodeTypeName === $nodeTypeName;
        }));

        $mockNode = $this->createMock(NodeInterface::class);
        $mockNode->expects(self::any())->method('getContext')->will(self::returnValue($mockContext));
        $mockNode->expects(self::any())->method('getName')->will(self::returnValue($nodeName));
        $mockNode->expects(self::any())->method('getNodeType')->will(self::returnValue($mockNodeType));
        $mockNode->expects(self::any())->method('getWorkspace')->will(self::returnValue($mockContext->getWorkspace()));

        $mockNodeIdentifier = Algorithms::generateUUID();
        $mockNode->expects(self::any())->method('getIdentifier')->will(self::returnValue($mockNodeIdentifier));
        $mockContext->mockNodesByIdentifier[$mockNodeIdentifier] = $mockNode;

        // Parent node is set by buildSubNode()
        $mockNode->mockParentNode = null;
        $mockNode->expects(self::any())->method('getParent')->will(self::returnCallback(function () use ($mockNode) {
            return $mockNode->mockParentNode;
        }));

        $mockNode->mockChildNodes = [];
        $mockNode->expects(self::any())->method('getChildNodes')->will(self::returnCallback(function ($nodeTypeFilter) use ($mockNode) {
            return $mockNode->mockChildNodes;
        }));

        $mockNode->expects(self::any())->method('getNode')->will(self::returnCallback(function ($relativeNodePath) use ($mockNode) {
            $foundNode = null;
            foreach ($mockNode->mockChildNodes as $nodeName => $mockChildNode) {
                if ($nodeName === $relativeNodePath) {
                    $foundNode = $mockChildNode;
                }
            }
            return $foundNode;
        }));

        $mockNode->mockProperties = [];
        $mockNode->expects(self::any())->method('getProperties')->will(self::returnCallback(function () use ($mockNode) {
            return $mockNode->mockProperties;
        }));

        $mockNode->mockProperties = [];
        $mockNode->expects(self::any())->method('getProperty')->will(self::returnCallback(function ($propertyName) use ($mockNode) {
            return isset($mockNode->mockProperties[$propertyName]) ? $mockNode->mockProperties[$propertyName] : null;
        }));
        $mockNode->expects(self::any())->method('hasProperty')->will(self::returnCallback(function ($propertyName) use ($mockNode) {
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
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    protected function buildSiteNode(ContentContext $mockContext, $nodePath)
    {
        $nodeName = substr($nodePath, strrpos($nodePath, '/') + 1);
        $parentNodePath = substr($nodePath, 0, strrpos($nodePath, '/'));
        $mockSiteNode = $this->buildNode($mockContext, $nodeName);
        $mockSiteNode->expects(self::any())->method('getPath')->will(self::returnValue($nodePath));
        $mockSiteNode->expects(self::any())->method('getParentPath')->will(self::returnValue($parentNodePath));
        $mockContext->expects(self::any())->method('getCurrentSiteNode')->will(self::returnValue($mockSiteNode));
        return $mockSiteNode;
    }

    /**
     * Creates a mock sub node of the given parent node
     *
     * @param NodeInterface $mockParentNode
     * @param string $nodeName
     * @param string $nodeTypeName
     * @return NodeInterface|MockObject
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    protected function buildSubNode($mockParentNode, $nodeName, $nodeTypeName = 'Neos.Neos:Document')
    {
        $mockNode = $this->buildNode($mockParentNode->getContext(), $nodeName, $nodeTypeName);
        $mockNode->mockParentNode = $mockParentNode;

        $mockParentNode->mockChildNodes[$nodeName] = $mockNode;
        $mockNode->expects(self::any())->method('getChildNodes')->will(self::returnCallback(function ($nodeTypeFilter) use ($mockNode) {
            return $mockNode->mockChildNodes;
        }));
        $mockNode->method('getPath')->willReturn($mockParentNode->getPath() . '/' . $nodeName);
        return $mockNode;
    }

    protected function matchForHost(string &$requestPath, string $requestUriHost)
    {
        return $this->routePartHandler->matchWithParameters($requestPath, RouteParameters::createEmpty()->withParameter('requestUriHost', $requestUriHost));
    }

    protected function resolveForHost(array &$routeValues, string $requestUriHost)
    {
        return $this->routePartHandler->resolveWithParameters($routeValues, RouteParameters::createEmpty()->withParameter('requestUriHost', $requestUriHost));
    }
}
