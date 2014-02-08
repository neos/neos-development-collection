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

class LocalizedFrontendNodeRoutePartHandlerTest extends UnitTestCase {

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
	 * @var \TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface
	 */
	protected $mockContentDimensionPresetSource;

	/**
	 * @var \TYPO3\Neos\Routing\LocalizedFrontendNodeRoutePartHandler
	 */
	protected $handler;

	protected function setUp() {
		$this->handler = new \TYPO3\Neos\Routing\LocalizedFrontendNodeRoutePartHandler();
		$this->handler->setName('node');

		$this->mockContextFactory = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface')->getMock();
		$this->inject($this->handler, 'contextFactory', $this->mockContextFactory);

		$this->mockDomainRepository = $this->getMockBuilder('TYPO3\Neos\Domain\Repository\DomainRepository')->disableOriginalConstructor()->getMock();
		$this->inject($this->handler, 'domainRepository', $this->mockDomainRepository);

		$this->mockSiteRepository = $this->getMockBuilder('TYPO3\Neos\Domain\Repository\SiteRepository')->disableOriginalConstructor()->getMock();
		$mockQueryResult = $this->getMock('TYPO3\Flow\Persistence\QueryResultInterface');
		$this->mockSiteRepository->expects($this->any())->method('findOnline')->will($this->returnValue($mockQueryResult));
		$this->inject($this->handler, 'siteRepository', $this->mockSiteRepository);

		$this->mockContentDimensionPresetSource = $this->getMockBuilder('TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface')->getMock();
		$this->inject($this->handler, 'contentDimensionPresetSource', $this->mockContentDimensionPresetSource);
	}

	/**
	 * @test
	 */
	public function matchValueWithEmptyRoutePathUsesLocaleChainResolverWithNullValue() {
		$expectedLocales = array('en_UK', 'en_ZZ', 'mul_ZZ');
		$mockContext = $this->buildMockContext($expectedLocales, 'live');
		$mockSiteNode = $this->buildSiteNode($mockContext);

		$mockSiteNode->expects($this->any())->method('getContextPath')->will($this->returnValue('/sites/foo;locales=en_UK,en_ZZ,mul_ZZ'));

		$this->mockContentDimensionPresetSource->expects($this->atLeastOnce())->method('getDefaultPreset')->with('locales')->will($this->returnValue(array('values' => array('en_UK', 'en_ZZ', 'mul_ZZ'))));

		$routePath = '';
		$matches = $this->handler->match($routePath);

		$this->assertSame(TRUE, $matches, 'Route part should match');

		$value = $this->handler->getValue();

		$this->assertEquals('/sites/foo;locales=en_UK,en_ZZ,mul_ZZ', $value);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Neos\Routing\Exception\NoSuchLocaleException
	 */
	public function matchValueWithoutMatchingLocaleThrowsException() {
		$this->mockContentDimensionPresetSource->expects($this->any())->method('findPresetByUriSegment')->with('locales', 'features')->will($this->returnValue(NULL));

		$nodePath = 'features';
		$this->handler->match($nodePath);
	}

	/**
	 * @test
	 */
	public function matchValueWithMatchingLocaleAndNodePath() {

		$expectedLocales = array('de_DE', 'de_ZZ', 'mul_ZZ');
		$mockContext = $this->buildMockContext($expectedLocales, 'live');
		$mockSiteNode = $this->buildSiteNode($mockContext);
		$mockSubNode = $this->buildSubNode($mockSiteNode, 'features/foo');

		$mockSubNode->expects($this->any())->method('getContextPath')->will($this->returnValue('/sites/foo/features/foo;locales=de_DE,de_ZZ,mul_ZZ'));

		$this->mockContentDimensionPresetSource->expects($this->atLeastOnce())->method('findPresetByUriSegment')->with('locales', 'de')->will($this->returnValue(array('values' => array('de_DE', 'de_ZZ', 'mul_ZZ'))));

		$routePath = 'de/features/foo';
		$matches = $this->handler->match($routePath);

		$this->assertSame(TRUE, $matches, 'Route part should match');

		$value = $this->handler->getValue();

		$this->assertEquals('/sites/foo/features/foo;locales=de_DE,de_ZZ,mul_ZZ', $value);
	}

	/**
	 * @test
	 */
	public function matchValueWithMatchingLocaleAndEmptyNodePath() {

		$expectedLocales = array('de_DE', 'mul_ZZ');
		$mockContext = $this->buildMockContext($expectedLocales, 'live');
		$mockSiteNode = $this->buildSiteNode($mockContext);

		$mockSiteNode->expects($this->any())->method('getContextPath')->will($this->returnValue('/sites/foo;locales=de_DE,mul_ZZ'));

		$this->mockContentDimensionPresetSource->expects($this->atLeastOnce())->method('findPresetByUriSegment')->with('locales', 'de')->will($this->returnValue(array('values' => array('de_DE', 'mul_ZZ'))));

		$routePath = 'de';
		$matches = $this->handler->match($routePath);

		$this->assertSame(TRUE, $matches, 'Route part should match');

		$value = $this->handler->getValue();

		$this->assertEquals('/sites/foo;locales=de_DE,mul_ZZ', $value);
	}

	/**
	 * @param array $expectedLocales
	 * @param string $workspaceName
	 * @return ContentContext
	 */
	protected function buildMockContext($expectedLocales, $workspaceName) {
		$mockContext = $this->getMockBuilder('TYPO3\Neos\Domain\Service\ContentContext')->disableOriginalConstructor()->getMock();
		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$mockWorkspace->expects($this->any())->method('getName')->will($this->returnValue($workspaceName));
		$mockContext->expects($this->any())->method('getWorkspace')->will($this->returnValue($mockWorkspace));
		$mockSite = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Site')->disableOriginalConstructor()->getMock();
		$mockContext->expects($this->any())->method('getCurrentSite')->will($this->returnValue($mockSite));
		$mockContext->expects($this->any())->method('getDimensions')->will($this->returnValue(array(
			'locales' => $expectedLocales
		)));

		$this->mockContextFactory->expects($this->once())->method('create')->with($this->callback(function ($contextProperties) use ($expectedLocales) {
			\PHPUnit_Framework_Assert::assertEquals($contextProperties['dimensions'], array(
				'locales' => $expectedLocales
			));
			return TRUE;
		}))->will($this->returnValue($mockContext));

		return $mockContext;
	}

	/**
	 * @param ContentContext $mockContext
	 * @return NodeInterface
	 */
	protected function buildSiteNode($mockContext) {
		$mockNodeType = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeType')->disableOriginalConstructor()->getMock();
		$mockNodeType->expects($this->any())->method('isOfType')->with('TYPO3.Neos:Document')->will($this->returnValue(TRUE));
		$mockSiteNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$mockSiteNode->expects($this->any())->method('getNodeType')->will($this->returnValue($mockNodeType));
		$mockSiteNode->expects($this->any())->method('getContext')->will($this->returnValue($mockContext));
		$mockSiteNode->expects($this->any())->method('getIdentifier')->will($this->returnValue('site-node-uuid'));
		$mockContext->expects($this->any())->method('getCurrentSiteNode')->will($this->returnValue($mockSiteNode));

		return $mockSiteNode;
	}

	/**
	 * @param NodeInterface $mockSiteNode
	 * @param string $nodePath
	 * @return NodeInterface
	 */
	protected function buildSubNode($mockSiteNode, $nodePath) {
		$mockNodeType = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeType')->disableOriginalConstructor()->getMock();
		$mockNodeType->expects($this->any())->method('isOfType')->with('TYPO3.Neos:Document')->will($this->returnValue(TRUE));
		$mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$mockNode->expects($this->any())->method('getContext')->will($this->returnValue($mockSiteNode->getContext()));
		$mockNode->expects($this->any())->method('getIdentifier')->will($this->returnValue('sub-node-uuid'));
		$mockNode->expects($this->any())->method('getNodeType')->will($this->returnValue($mockNodeType));
		$mockSiteNode->expects($this->any())->method('getNode')->with($nodePath)->will($this->returnValue($mockNode));

		return $mockNode;
	}

}
