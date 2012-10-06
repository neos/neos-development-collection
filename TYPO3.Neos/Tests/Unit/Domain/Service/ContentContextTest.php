<?php
namespace TYPO3\TYPO3\Tests\Unit\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Content Context
 *
 */
class ContentContextTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getCurrentDateTimeReturnsACurrentDateAndTime() {
		$mockHttpRequestHandler = $this->getMock('TYPO3\Flow\Http\HttpRequestHandlerInterface');
		$mockHttpRequestHandler->expects($this->any())->method('getHttpRequest')->will($this->returnValue(\TYPO3\Flow\Http\Request::create(new \TYPO3\Flow\Http\Uri('http://myhost/'))));
		$mockBootstrap = $this->getMock('TYPO3\Flow\Core\Bootstrap', array('getActiveRequestHandler'), array(), '', FALSE);
		$mockBootstrap->expects($this->any())->method('getActiveRequestHandler')->will($this->returnValue($mockHttpRequestHandler));
		$mockDomainRepository = $this->getMock('TYPO3\TYPO3\Domain\Repository\DomainRepository', array(), array(), '', FALSE);
		$mockDomainRepository->expects($this->once())->method('findByHost')->with('myhost')->will($this->returnValue(array()));
		$mockSiteRepository = $this->getMock('TYPO3\TYPO3\Domain\Repository\SiteRepository', array('findFirst'), array(), '', FALSE);
		$contentContext = $this->getMock($this->buildAccessibleProxy('TYPO3\TYPO3\Domain\Service\ContentContext'), array('dummy'), array('live'));
		$contentContext->_set('bootstrap', $mockBootstrap);
		$contentContext->_set('domainRepository', $mockDomainRepository);
		$contentContext->_set('siteRepository', $mockSiteRepository);
		$contentContext->initializeObject();

		$almostCurrentTime = new \DateTime();
		date_sub($almostCurrentTime, new \DateInterval('P0DT1S'));
		$currentTime = $contentContext->getCurrentDateTime();
		$this->assertTrue($almostCurrentTime < $currentTime);
	}

	/**
	 * @test
	 */
	public function setDateTimeAllowsForMockingTheCurrentTime() {
		$simulatedCurrentTime = new \DateTime();
		date_add($simulatedCurrentTime, new \DateInterval('P1D'));

		$contentContext = new \TYPO3\TYPO3\Domain\Service\ContentContext('live');
		$contentContext->setCurrentDateTime($simulatedCurrentTime);

		$this->assertEquals($simulatedCurrentTime, $contentContext->getCurrentDateTime());
	}

	/**
	 * @test
	 */
	public function getLocaleReturnsByDefaultAnInternationalMultilingualLocale() {
		$mockHttpRequestHandler = $this->getMock('TYPO3\Flow\Http\HttpRequestHandlerInterface');
		$mockHttpRequestHandler->expects($this->any())->method('getHttpRequest')->will($this->returnValue(\TYPO3\Flow\Http\Request::create(new \TYPO3\Flow\Http\Uri('http://myhost/'))));
		$mockBootstrap = $this->getMock('TYPO3\Flow\Core\Bootstrap', array('getActiveRequestHandler'), array(), '', FALSE);
		$mockBootstrap->expects($this->any())->method('getActiveRequestHandler')->will($this->returnValue($mockHttpRequestHandler));

		$mockDomainRepository = $this->getMock('TYPO3\TYPO3\Domain\Repository\DomainRepository', array(), array(), '', FALSE);
		$mockDomainRepository->expects($this->once())->method('findByHost')->with('myhost')->will($this->returnValue(array()));

		$mockSiteRepository = $this->getMock('TYPO3\TYPO3\Domain\Repository\SiteRepository', array('findFirst'), array(), '', FALSE);

		$contentContext = $this->getMock($this->buildAccessibleProxy('TYPO3\TYPO3\Domain\Service\ContentContext'), array('dummy'), array('live'));
		$contentContext->_set('bootstrap', $mockBootstrap);
		$contentContext->_set('domainRepository', $mockDomainRepository);
		$contentContext->_set('siteRepository', $mockSiteRepository);
		$contentContext->initializeObject();

		$this->assertEquals('mul_ZZ', (string)$contentContext->getLocale());
	}

	/**
	 * @test
	 */
	public function initializeObjectResolvesTheBestMatchingDomainAndSetsTheCurrentSiteAndDomain() {
		$mockHttpRequestHandler = $this->getMock('TYPO3\Flow\Http\HttpRequestHandlerInterface');
		$mockHttpRequestHandler->expects($this->any())->method('getHttpRequest')->will($this->returnValue(\TYPO3\Flow\Http\Request::create(new \TYPO3\Flow\Http\Uri('http://myhost/'))));
		$mockBootstrap = $this->getMock('TYPO3\Flow\Core\Bootstrap', array('getActiveRequestHandler'), array(), '', FALSE);
		$mockBootstrap->expects($this->any())->method('getActiveRequestHandler')->will($this->returnValue($mockHttpRequestHandler));

		$mockSite = $this->getMock('TYPO3\TYPO3\Domain\Model\Site', array(), array(), '', FALSE);

		$mockMatchingDomains = array(
			$this->getMock('TYPO3\TYPO3\Domain\Model\Domain', array(), array(), '', FALSE),
			$this->getMock('TYPO3\TYPO3\Domain\Model\Domain', array(), array(), '', FALSE)
		);

		$mockMatchingDomains[0]->expects($this->once())->method('getSite')->will($this->returnValue($mockSite));

		$mockDomainRepository = $this->getMock('TYPO3\TYPO3\Domain\Repository\DomainRepository', array(), array(), '', FALSE);
		$mockDomainRepository->expects($this->once())->method('findByHost')->with('myhost')->will($this->returnValue($mockMatchingDomains));

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');

		$contentContext = $this->getMock($this->buildAccessibleProxy('TYPO3\TYPO3\Domain\Service\ContentContext'), array('dummy'), array('live'));
		$contentContext->_set('objectManager', $mockObjectManager);
		$contentContext->_set('domainRepository', $mockDomainRepository);
		$contentContext->_set('bootstrap', $mockBootstrap);

		$contentContext->initializeObject();

		$this->assertSame($mockMatchingDomains[0], $contentContext->getCurrentDomain());
		$this->assertSame($mockSite, $contentContext->getCurrentSite());
	}

	/**
	 * @test
	 */
	public function initializeObjectSetsTheCurrentSiteToTheFirstSiteFoundIfNoDomainsMatchedTheCurrentRequest() {
		$mockHttpRequestHandler = $this->getMock('TYPO3\Flow\Http\HttpRequestHandlerInterface');
		$mockHttpRequestHandler->expects($this->any())->method('getHttpRequest')->will($this->returnValue(\TYPO3\Flow\Http\Request::create(new \TYPO3\Flow\Http\Uri('http://myhost/'))));
		$mockBootstrap = $this->getMock('TYPO3\Flow\Core\Bootstrap', array('getActiveRequestHandler'), array(), '', FALSE);
		$mockBootstrap->expects($this->any())->method('getActiveRequestHandler')->will($this->returnValue($mockHttpRequestHandler));

		$mockSites = array(
			$this->getMock('TYPO3\TYPO3\Domain\Model\Site', array(), array(), '', FALSE),
			$this->getMock('TYPO3\TYPO3\Domain\Model\Site', array(), array(), '', FALSE)
		);

		$mockSiteRepository = $this->getMock('TYPO3\TYPO3\Domain\Repository\SiteRepository', array('findFirst'), array(), '', FALSE);
		$mockSiteRepository->expects($this->once())->method('findFirst')->will($this->returnValue($mockSites[0]));

		$mockDomainRepository = $this->getMock('TYPO3\TYPO3\Domain\Repository\DomainRepository', array(), array(), '', FALSE);
		$mockDomainRepository->expects($this->once())->method('findByHost')->with('myhost')->will($this->returnValue(array()));

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');

		$contentContext = $this->getMock($this->buildAccessibleProxy('TYPO3\TYPO3\Domain\Service\ContentContext'), array('dummy'), array('live'));
		$contentContext->_set('objectManager', $mockObjectManager);
		$contentContext->_set('domainRepository', $mockDomainRepository);
		$contentContext->_set('siteRepository', $mockSiteRepository);
		$contentContext->_set('bootstrap', $mockBootstrap);

		$contentContext->initializeObject();

		$this->assertSame(NULL, $contentContext->getCurrentDomain());
		$this->assertSame($mockSites[0], $contentContext->getCurrentSite());
	}

	/**
	 * @test
	 */
	public function getCurrentSiteReturnsTheCurrentSite() {
		$mockSite = $this->getMock('TYPO3\TYPO3\Domain\Model\Site', array(), array(), '', FALSE);

		$contentContext = $this->getMock($this->buildAccessibleProxy('TYPO3\TYPO3\Domain\Service\ContentContext'), array('dummy'), array('live'));
		$contentContext->_set('currentSite', $mockSite);
		$this->assertSame($mockSite, $contentContext->getCurrentSite());
	}

	/**
	 * @test
	 */
	public function getCurrentDomainReturnsTheCurrentDomainIfAny() {
		$mockDomain = $this->getMock('TYPO3\TYPO3\Domain\Model\Domain', array(), array(), '', FALSE);

		$contentContext = $this->getMock($this->buildAccessibleProxy('TYPO3\TYPO3\Domain\Service\ContentContext'), array('dummy'), array('live'));

		$this->assertNull($contentContext->getCurrentDomain());
		$contentContext->_set('currentDomain', $mockDomain);
		$this->assertSame($mockDomain, $contentContext->getCurrentDomain());
	}

}


?>