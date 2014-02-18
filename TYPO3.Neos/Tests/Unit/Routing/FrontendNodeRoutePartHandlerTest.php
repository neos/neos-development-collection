<?php
namespace TYPO3\Neos\Tests\Unit\Routing;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\Routing\FrontendNodeRoutePartHandler;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

/**
 * Testcase for the Content Routepart Handler
 */
class FrontendNodeRoutePartHandlerTest extends UnitTestCase {

	/**
	 * @var FrontendNodeRoutePartHandler
	 */
	protected $frontendNodeRoutePartHandler;

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
	 * @var ContentContext
	 */
	protected $mockContext;

	/**
	 * @var NodeInterface
	 */
	protected $mockNode;

	/**
	 * @var NodeInterface
	 */
	protected $mockSiteNode;

	/**
	 * @var NodeType
	 */
	protected $mockNodeType;

	public function setUp() {
		$this->frontendNodeRoutePartHandler = $this->getAccessibleMock('TYPO3\Neos\Routing\FrontendNodeRoutePartHandler', array('dummy'));

		$this->mockContextFactory = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface')->getMock();
		$this->inject($this->frontendNodeRoutePartHandler, 'contextFactory', $this->mockContextFactory);

		$this->mockDomainRepository = $this->getMockBuilder('TYPO3\Neos\Domain\Repository\DomainRepository')->disableOriginalConstructor()->getMock();
		$this->inject($this->frontendNodeRoutePartHandler, 'domainRepository', $this->mockDomainRepository);

		$this->mockSiteRepository = $this->getMockBuilder('TYPO3\Neos\Domain\Repository\SiteRepository')->disableOriginalConstructor()->getMock();
		$mockQueryResult = $this->getMock('TYPO3\Flow\Persistence\QueryResultInterface');
		$this->mockSiteRepository->expects($this->any())->method('findOnline')->will($this->returnValue($mockQueryResult));
		$this->inject($this->frontendNodeRoutePartHandler, 'siteRepository', $this->mockSiteRepository);

		$this->mockContext = $this->getMockBuilder('TYPO3\Neos\Domain\Service\ContentContext')->disableOriginalConstructor()->getMock();

		$this->mockNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();
		$this->mockSiteNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();

		$this->mockNodeType = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeType')->disableOriginalConstructor()->getMock();
		$this->mockNodeType->expects($this->any())->method('isOfType')->with('TYPO3.Neos:Document')->will($this->returnValue(TRUE));
		$this->mockNode->expects($this->any())->method('getNodeType')->will($this->returnValue($this->mockNodeType));
		$this->mockSiteNode->expects($this->any())->method('getNodeType')->will($this->returnValue($this->mockNodeType));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Neos\Routing\Exception\NoHomepageException
	 */
	public function matchValueThrowsAnExceptionIfNoHomepageExists() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));
		$this->frontendNodeRoutePartHandler->_call('matchValue', '');
	}

	/**
	 * @test
	 */
	public function matchValueCreatesContextForLiveWorkspaceByDefault() {
		$self = $this;
		$mockContext = $this->mockContext;
		$this->mockContextFactory->expects($this->once())->method('create')->will($this->returnCallback(function($contextProperties) use ($self, $mockContext) {
			$self->assertSame('live', $contextProperties['workspaceName']);
			return $mockContext;
		}));
		$this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path');
	}

	/**
	 * @test
	 */
	public function matchValueCreatesContextForCustomWorkspaceIfSpecifiedInNodeContextPath() {
		$self = $this;
		$mockContext = $this->mockContext;
		$this->mockContextFactory->expects($this->once())->method('create')->will($this->returnCallback(function($contextProperties) use ($self, $mockContext) {
			$self->assertSame('some-workspace', $contextProperties['workspaceName']);
			return $mockContext;
		}));
		$this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path@some-workspace');
	}

	/**
	 * @test
	 */
	public function matchValueCreatesContextForCurrentDomainIfOneIsFound() {
		$mockDomain = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Domain')->disableOriginalConstructor()->getMock();

		$mockSite = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Site')->disableOriginalConstructor()->getMock();
		$mockDomain->expects($this->atLeastOnce())->method('getSite')->will($this->returnValue($mockSite));

		$this->mockDomainRepository->expects($this->atLeastOnce())->method('findOneByActiveRequest')->will($this->returnValue($mockDomain));

		$self = $this;
		$mockContext = $this->mockContext;
		$this->mockContextFactory->expects($this->once())->method('create')->will($this->returnCallback(function($contextProperties) use ($self, $mockSite, $mockDomain, $mockContext) {
			$self->assertSame($mockDomain, $contextProperties['currentDomain']);
			$self->assertSame($mockSite, $contextProperties['currentSite']);
			return $mockContext;
		}));
		$this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path');
	}

	/**
	 * @test
	 */
	public function matchValueReturnsFalseIfNoWorkspaceCanBeResolved() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));
		$this->mockContext->expects($this->atLeastOnce())->method('getWorkspace')->with(FALSE)->will($this->returnValue(NULL));

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path'));
	}

	/**
	 * @test
	 */
	public function matchValueReturnsFalseIfNoSiteCanBeResolved() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->any())->method('getWorkspace')->with(FALSE)->will($this->returnValue($mockWorkspace));

		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSite')->will($this->returnValue(NULL));

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path'));
	}

	/**
	 * @test
	 */
	public function matchValueReturnsFalseIfNoSiteNodeCanBeResolved() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->any())->method('getWorkspace')->with(FALSE)->will($this->returnValue($mockWorkspace));

		$mockSite = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Site')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSite')->will($this->returnValue($mockSite));
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSiteNode')->will($this->returnValue(NULL));

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path'));
	}

	/**
	 * @test
	 */
	public function matchValueReturnsFalseIfNodeCantBeFetchedFromSiteNode() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->any())->method('getWorkspace')->with(FALSE)->will($this->returnValue($mockWorkspace));

		$mockSite = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Site')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$this->mockSiteNode->expects($this->atLeastOnce())->method('getNode')->with('some/path')->will($this->returnValue(NULL));
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSiteNode')->will($this->returnValue($this->mockSiteNode));

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path'));
	}

	/**
	 * @test
	 */
	public function matchValueReturnsFalseIfNodeIsNoDocument() {
		$mockNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();

		$mockNodeType = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeType')->disableOriginalConstructor()->getMock();
		$mockNodeType->expects($this->any())->method('isOfType')->with('TYPO3.Neos:Document')->will($this->returnValue(FALSE));
		$mockNode->expects($this->any())->method('getNodeType')->will($this->returnValue($mockNodeType));

		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->any())->method('getWorkspace')->with(FALSE)->will($this->returnValue($mockWorkspace));

		$mockSite = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Site')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$this->mockSiteNode->expects($this->atLeastOnce())->method('getNode')->with('some/path')->will($this->returnValue($mockNode));
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSiteNode')->will($this->returnValue($this->mockSiteNode));

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path'));
	}

	/**
	 * Note: In this case the ".html" suffix is not stripped of the context path because no split string is set
	 *
	 * @test
	 */
	public function matchValueReturnsFalseIfContextPathIsInvalid() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('not-live'));
		$this->mockContext->expects($this->any())->method('getWorkspace')->with(FALSE)->will($this->returnValue($mockWorkspace));
		$this->mockNode->expects($this->any())->method('getContext')->will($this->returnValue($this->mockContext));

		$mockSite = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Site')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->any())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$this->mockSiteNode->expects($this->any())->method('getNode')->with('some/path')->will($this->returnValue($this->mockNode));
		$this->mockContext->expects($this->any())->method('getCurrentSiteNode')->will($this->returnValue($this->mockSiteNode));

		$this->mockNode->expects($this->any())->method('getContextPath')->will($this->returnValue('some/path@not-live'));
		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path@not-live.html'));
	}

	/**
	 * @test
	 */
	public function matchValueSetsValueToTheNodeContextPathAndReturnsTrueIfNodePathCouldBeResolvedAndWorkspaceIsNotLive() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('not-live'));
		$this->mockContext->expects($this->any())->method('getWorkspace')->with(FALSE)->will($this->returnValue($mockWorkspace));
		$this->mockNode->expects($this->any())->method('getContext')->will($this->returnValue($this->mockContext));

		$mockSite = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Site')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$this->mockSiteNode->expects($this->atLeastOnce())->method('getNode')->with('some/path')->will($this->returnValue($this->mockNode));
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSiteNode')->will($this->returnValue($this->mockSiteNode));

		$this->mockNode->expects($this->atLeastOnce())->method('getContextPath')->will($this->returnValue('some/context/path'));
		$this->assertTrue($this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path'));
		$this->assertSame('some/context/path', $this->frontendNodeRoutePartHandler->getValue());
	}

	/**
	 * @test
	 */
	public function matchValueReturnsFalseIfOnlyMatchSiteNodesOptionIsSetAndMatchingNodeIsNoSiteNode() {
		$this->frontendNodeRoutePartHandler->setOptions(array('onlyMatchSiteNodes' => TRUE));
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->any())->method('getWorkspace')->with(FALSE)->will($this->returnValue($mockWorkspace));

		$mockSite = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Site')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$this->mockNode->expects($this->atLeastOnce())->method('getContext')->will($this->returnValue($this->mockContext));
		$this->mockSiteNode->expects($this->atLeastOnce())->method('getNode')->with('some/path')->will($this->returnValue($this->mockNode));
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSiteNode')->will($this->returnValue($this->mockSiteNode));

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path'));
	}

	/**
	 * @test
	 */
	public function matchValueReturnsTrueIfOnlyMatchSiteNodesOptionIsSetAndMatchingNodeIsASiteNode() {
		$this->frontendNodeRoutePartHandler->setOptions(array('onlyMatchSiteNodes' => TRUE));
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->any())->method('getWorkspace')->with(FALSE)->will($this->returnValue($mockWorkspace));

		$mockSite = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Site')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$this->mockSiteNode->expects($this->atLeastOnce())->method('getContextPath')->will($this->returnValue(''));
		$this->mockSiteNode->expects($this->any())->method('getWorkspace')->will($this->returnValue($mockWorkspace));

		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSiteNode')->will($this->returnValue($this->mockSiteNode));
		$this->mockSiteNode->expects($this->atLeastOnce())->method('getContext')->will($this->returnValue($this->mockContext));

		$this->assertTrue($this->frontendNodeRoutePartHandler->_call('matchValue', '@some-context'));
	}


	/**
	 * @test
	 */
	public function matchValueSetsValueToTheNodeIdentifierAndReturnsTrueIfNodePathCouldBeResolvedAndWorkspaceIsLive() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue('live'));
		$this->mockContext->expects($this->any())->method('getWorkspace')->with(FALSE)->will($this->returnValue($mockWorkspace));
		$this->mockNode->expects($this->any())->method('getContext')->will($this->returnValue($this->mockContext));

		$mockSite = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Site')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$this->mockSiteNode->expects($this->atLeastOnce())->method('getNode')->with('some/path')->will($this->returnValue($this->mockNode));
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSiteNode')->will($this->returnValue($this->mockSiteNode));

		$this->mockNode->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('TheNodeIdentifier'));
		$this->assertTrue($this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path'));
		$this->assertSame('TheNodeIdentifier', $this->frontendNodeRoutePartHandler->getValue());
	}

	/**
	 * Data provider for findValueToMatchRespectsSplitString() see below
	 */
	public function findValueToMatchRespectsSplitStringDataProvider() {
		return array(
			array(
				'requestPath' => 'homepage',
				'splitString' => NULL,
				'expectedResult' => 'homepage'
			),
			array(
				'requestPath' => 'homepage.html',
				'splitString' => NULL,
				'expectedResult' => 'homepage.html'
			),
			array(
				'requestPath' => 'homepage.html',
				'splitString' => '.html',
				'expectedResult' => 'homepage'
			),
			array(
				'requestPath' => 'homepage/subpage',
				'splitString' => NULL,
				'expectedResult' => 'homepage/subpage'
			),
			array(
				'requestPath' => 'homepage/subpage.html',
				'splitString' => NULL,
				'expectedResult' => 'homepage/subpage.html'
			),
			array(
				'requestPath' => 'homepage/subpage.html',
				'splitString' => '.html',
				'expectedResult' => 'homepage/subpage'
			),
			array(
				'requestPath' => 'homepage/subpage.rss.xml',
				'splitString' => '.html',
				'expectedResult' => 'homepage/subpage.rss.xml'
			),
			array(
				'requestPath' => 'homepage/subpage.rss.xml',
				'splitString' => '.rss.xml',
				'expectedResult' => 'homepage/subpage'
			),
			array(
				'requestPath' => 'homepage/subpage/suffix',
				'splitString' => '/suffix',
				'expectedResult' => 'homepage/subpage'
			),
		);
	}

	/**
	 * @param string $requestPath
	 * @param string $splitString
	 * @param string $expectedResult
	 * @test
	 * @dataProvider findValueToMatchRespectsSplitStringDataProvider
	 */
	public function findValueToMatchRespectsSplitString($requestPath, $splitString, $expectedResult) {
		if ($splitString !== NULL) {
			$this->frontendNodeRoutePartHandler->setSplitString($splitString);
		}

		$actualResult = $this->frontendNodeRoutePartHandler->_call('findValueToMatch', $requestPath);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsFalseIfGivenValueIsNull() {
		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('resolveValue', NULL));
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsFalseIfGivenValueIsNumeric() {
		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('resolveValue', 123));
	}

	/**
	 * @test
	 */
	public function resolveValueCreatesContextForLiveWorkspaceIfGivenValueIsAStringWithoutWorkspaceToken() {
		$self = $this;
		$mockContext = $this->mockContext;
		$this->mockContextFactory->expects($this->once())->method('create')->will($this->returnCallback(function($contextProperties) use ($self, $mockContext) {
			$self->assertSame('live', $contextProperties['workspaceName']);
			return $mockContext;
		}));
		$this->frontendNodeRoutePartHandler->_call('resolveValue', 'some/path');
	}

	/**
	 * @test
	 */
	public function resolveValueCreatesContextForLiveWorkspaceIfGivenValueIsAStringWithWorkspaceToken() {
		$self = $this;
		$mockContext = $this->mockContext;
		$this->mockContextFactory->expects($this->once())->method('create')->will($this->returnCallback(function($contextProperties) use ($self, $mockContext) {
			$self->assertSame('some-workspace', $contextProperties['workspaceName']);
			return $mockContext;
		}));
		$this->frontendNodeRoutePartHandler->_call('resolveValue', 'some/path@some-workspace');
	}

	/**
	 * @test
	 */
	public function resolveValueCreatesContextForCurrentDomainIfGivenValueIsAStringAndADomainIsFound() {
		$mockDomain = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Domain')->disableOriginalConstructor()->getMock();

		$mockSite = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Site')->disableOriginalConstructor()->getMock();
		$mockDomain->expects($this->atLeastOnce())->method('getSite')->will($this->returnValue($mockSite));

		$this->mockDomainRepository->expects($this->atLeastOnce())->method('findOneByActiveRequest')->will($this->returnValue($mockDomain));

		$self = $this;
		$mockContext = $this->mockContext;
		$this->mockContextFactory->expects($this->once())->method('create')->will($this->returnCallback(function($contextProperties) use ($self, $mockSite, $mockDomain, $mockContext) {
			$self->assertSame($mockDomain, $contextProperties['currentDomain']);
			$self->assertSame($mockSite, $contextProperties['currentSite']);
			return $mockContext;
		}));
		$this->frontendNodeRoutePartHandler->_call('resolveValue', 'some/path');
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsFalseIfNoWorkspaceCanBeResolved() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));
		$this->mockContext->expects($this->atLeastOnce())->method('getWorkspace')->with(FALSE)->will($this->returnValue(NULL));

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('resolveValue', 'some/path'));
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsFalseIfNodeCantBeRetrievedFromContext() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->atLeastOnce())->method('getWorkspace')->with(FALSE)->will($this->returnValue($mockWorkspace));

		$this->mockContext->expects($this->atLeastOnce())->method('getNode')->with('some/path')->will($this->returnValue(NULL));

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('resolveValue', 'some/path@context'));
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsFalseIfNodeIsNoDocument() {
		$mockNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();

		$mockNodeType = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeType')->disableOriginalConstructor()->getMock();
		$mockNodeType->expects($this->any())->method('isOfType')->with('TYPO3.Neos:Document')->will($this->returnValue(FALSE));
		$mockNode->expects($this->any())->method('getNodeType')->will($this->returnValue($mockNodeType));

		$mockNode->expects($this->atLeastOnce())->method('getContext')->will($this->returnValue($this->mockContext));

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('resolveValue', $mockNode));
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsFalseIfSpecifiedValueIsAUuidButLiveWorkspaceCantBeRetrieved() {
		$nodeIdentifier = '044412ab-5bd7-45a5-ba17-95fc87a42dac';

		$mockWorkspaceRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository')->setMethods(array('findOneByName'))->disableOriginalConstructor()->getMock();
		$mockWorkspaceRepository->expects($this->atLeastOnce())->method('findOneByName')->with('live')->will($this->returnValue(NULL));
		$this->inject($this->frontendNodeRoutePartHandler, 'workspaceRepository', $mockWorkspaceRepository);

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('resolveValue', $nodeIdentifier));
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsFalseIfSpecifiedValueIsAUuidWithNoCorrespondingNodeDataInLiveWorkspace() {
		$nodeIdentifier = '044412ab-5bd7-45a5-ba17-95fc87a42dac';

		$mockLiveWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();

		$mockWorkspaceRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository')->setMethods(array('findOneByName'))->disableOriginalConstructor()->getMock();
		$mockWorkspaceRepository->expects($this->atLeastOnce())->method('findOneByName')->with('live')->will($this->returnValue($mockLiveWorkspace));
		$this->inject($this->frontendNodeRoutePartHandler, 'workspaceRepository', $mockWorkspaceRepository);

		$mockNodeDataRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->disableOriginalConstructor()->getMock();
		$mockNodeDataRepository->expects($this->atLeastOnce())->method('findOneByIdentifier')->with($nodeIdentifier, $mockLiveWorkspace)->will($this->returnValue(NULL));
		$this->inject($this->frontendNodeRoutePartHandler, 'nodeDataRepository', $mockNodeDataRepository);

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('resolveValue', $nodeIdentifier));
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsFalseIfSpecifiedValueIsAUuidThatCantBeConvertedToANode() {
		$nodeIdentifier = '044412ab-5bd7-45a5-ba17-95fc87a42dac';

		$mockLiveWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();

		$mockWorkspaceRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository')->setMethods(array('findOneByName'))->disableOriginalConstructor()->getMock();
		$mockWorkspaceRepository->expects($this->atLeastOnce())->method('findOneByName')->with('live')->will($this->returnValue($mockLiveWorkspace));
		$this->inject($this->frontendNodeRoutePartHandler, 'workspaceRepository', $mockWorkspaceRepository);

		$mockNodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();

		$mockNodeDataRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->disableOriginalConstructor()->getMock();
		$mockNodeDataRepository->expects($this->atLeastOnce())->method('findOneByIdentifier')->with($nodeIdentifier, $mockLiveWorkspace)->will($this->returnValue($mockNodeData));
		$this->inject($this->frontendNodeRoutePartHandler, 'nodeDataRepository', $mockNodeDataRepository);

		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$mockNodeFactory = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Factory\NodeFactory')->disableOriginalConstructor()->getMock();
		$mockNodeFactory->expects($this->atLeastOnce())->method('createFromNodeData')->with($mockNodeData)->will($this->returnValue(NULL));
		$this->inject($this->frontendNodeRoutePartHandler, 'nodeFactory', $mockNodeFactory);

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('resolveValue', $nodeIdentifier));
	}

	/**
	 * @test
	 */
	public function resolveValueSetsValueToContextPathAndReturnsTrueIfSpecifiedValueIsAValidNode() {
		$this->mockNode->expects($this->atLeastOnce())->method('getContext')->will($this->returnValue($this->mockContext));
		$this->mockNode->expects($this->atLeastOnce())->method('getContextPath')->will($this->returnValue('the/site/root/the/context/path@some-workspace'));

		$this->mockSiteNode->expects($this->atLeastOnce())->method('getPath')->will($this->returnValue('the/site/root'));
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSiteNode')->will($this->returnValue($this->mockSiteNode));

		$this->assertTrue($this->frontendNodeRoutePartHandler->_call('resolveValue', $this->mockNode));
		$this->assertSame('the/context/path@some-workspace', $this->frontendNodeRoutePartHandler->getValue());
	}

	/**
	 * @test
	 */
	public function resolveValueSetsValueToContextPathAndReturnsTrueIfSpecifiedValueIsAValidNodeContextPath() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$this->mockNode->expects($this->atLeastOnce())->method('getContextPath')->will($this->returnValue('the/site/root/the/context/path@some-workspace'));

		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->atLeastOnce())->method('getWorkspace')->with(FALSE)->will($this->returnValue($mockWorkspace));

		$this->mockContext->expects($this->atLeastOnce())->method('getNode')->with('the/context/path')->will($this->returnValue($this->mockNode));
		$this->mockSiteNode->expects($this->atLeastOnce())->method('getPath')->will($this->returnValue('the/site/root'));
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSiteNode')->will($this->returnValue($this->mockSiteNode));

		$this->assertTrue($this->frontendNodeRoutePartHandler->_call('resolveValue', 'the/context/path@some-workspace'));
		$this->assertSame('the/context/path@some-workspace', $this->frontendNodeRoutePartHandler->getValue());
	}

	/**
	 * @test
	 */
	public function resolveValueSetsValueToContextPathAndReturnsTrueIfSpecifiedValueIsAValidNodeIdentifier() {
		$nodeIdentifier = '044412ab-5bd7-45a5-ba17-95fc87a42dac';

		$mockLiveWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();

		$mockWorkspaceRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository')->setMethods(array('findOneByName'))->disableOriginalConstructor()->getMock();
		$mockWorkspaceRepository->expects($this->atLeastOnce())->method('findOneByName')->with('live')->will($this->returnValue($mockLiveWorkspace));
		$this->inject($this->frontendNodeRoutePartHandler, 'workspaceRepository', $mockWorkspaceRepository);

		$mockNodeData = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeData')->disableOriginalConstructor()->getMock();

		$mockNodeDataRepository = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository')->disableOriginalConstructor()->getMock();
		$mockNodeDataRepository->expects($this->atLeastOnce())->method('findOneByIdentifier')->with($nodeIdentifier, $mockLiveWorkspace)->will($this->returnValue($mockNodeData));
		$this->inject($this->frontendNodeRoutePartHandler, 'nodeDataRepository', $mockNodeDataRepository);

		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$mockNodeFactory = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Factory\NodeFactory')->disableOriginalConstructor()->getMock();
		$mockNodeFactory->expects($this->atLeastOnce())->method('createFromNodeData')->with($mockNodeData)->will($this->returnValue($this->mockNode));
		$this->inject($this->frontendNodeRoutePartHandler, 'nodeFactory', $mockNodeFactory);

		$this->mockNode->expects($this->atLeastOnce())->method('getContextPath')->will($this->returnValue('the/site/root/the/context/path'));
		$this->mockSiteNode->expects($this->atLeastOnce())->method('getPath')->will($this->returnValue('the/site/root'));
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSiteNode')->will($this->returnValue($this->mockSiteNode));

		$this->assertTrue($this->frontendNodeRoutePartHandler->_call('resolveValue', $nodeIdentifier));
		$this->assertSame('the/context/path', $this->frontendNodeRoutePartHandler->getValue());
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsFalseIfOnlyMatchSiteNodesOptionIsSetAndResolvedNodeIsNoSiteNode() {
		$this->frontendNodeRoutePartHandler->setOptions(array('onlyMatchSiteNodes' => TRUE));
		$this->mockNode->expects($this->atLeastOnce())->method('getContext')->will($this->returnValue($this->mockContext));
		$this->mockNode->expects($this->atLeastOnce())->method('getContextPath')->will($this->returnValue('the/site/root/the/context/path@some-workspace'));

		$this->mockSiteNode->expects($this->atLeastOnce())->method('getPath')->will($this->returnValue('the/site/root'));
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSiteNode')->will($this->returnValue($this->mockSiteNode));

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('resolveValue', $this->mockNode));
	}


}
