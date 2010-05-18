<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Content Context
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ContentContextTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentServiceReturnsTheContentServiceBoundToTheContext() {
		$mockContentService = $this->getMock('F3\TYPO3\Domain\Service\ContentService', array(), array(), '', FALSE);

		$contentContext = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Service\ContentContext'), array('dummy'));
		$contentContext->_set('contentService', $mockContentService);
		$this->assertSame($mockContentService, $contentContext->getContentService());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNodeServiceReturnsTheNodeServiceBoundToTheContext() {
		$mockNodeService = $this->getMock('F3\TYPO3\Domain\Service\NodeService', array(), array(), '', FALSE);

		$contentContext = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Service\ContentContext'), array('dummy'));
		$contentContext->_set('nodeService', $mockNodeService);
		$this->assertSame($mockNodeService, $contentContext->getNodeService());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTypoScriptServiceReturnsTheTypoScriptServiceBoundToTheContext() {
		$mockTypoScriptService = $this->getMock('F3\TYPO3\Domain\Service\TypoScriptService', array(), array(), '', FALSE);

		$contentContext = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Service\ContentContext'), array('dummy'));
		$contentContext->_set('typoScriptService', $mockTypoScriptService);
		$this->assertSame($mockTypoScriptService, $contentContext->getTypoScriptService());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCurrentDateTimeReturnsACurrentDateAndTime() {
		$almostCurrentTime = new \DateTime();
		date_sub($almostCurrentTime, new \DateInterval('P0DT1S'));

		$contentContext = new \F3\TYPO3\Domain\Service\ContentContext();
		$currentTime = $contentContext->getCurrentDateTime();
		$this->assertTrue($almostCurrentTime < $currentTime);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setDateTimeAllowsForMockingTheCurrentTime() {
		$simulatedCurrentTime = new \DateTime();
		date_add($simulatedCurrentTime, new \DateInterval('P1D'));

		$contentContext = new \F3\TYPO3\Domain\Service\ContentContext();
		$contentContext->setCurrentDateTime($simulatedCurrentTime);

		$this->assertEquals($simulatedCurrentTime, $contentContext->getCurrentDateTime());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLocaleReturnsByDefaultAnInternationalMultilingualLocale() {
		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getHTTPHost')->will($this->returnValue('myhost'));

		$mockDomainRepository = $this->getMock('F3\TYPO3\Domain\Repository\Configuration\DomainRepository', array(), array(), '', FALSE);
		$mockDomainRepository->expects($this->once())->method('findByHost')->with('myhost')->will($this->returnValue(array()));

		$mockSiteRepository = $this->getMock('F3\TYPO3\Domain\Repository\Structure\SiteRepository', array('findFirst'), array(), '', FALSE);

		$locale = new \F3\FLOW3\Locale\Locale('mul-ZZ');

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(3))->method('create')->with('F3\FLOW3\Locale\Locale', 'mul-ZZ')->will($this->returnValue($locale));

		$contentContext = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Service\ContentContext'), array('dummy'));
		$contentContext->_set('objectManager', $mockObjectManager);
		$contentContext->_set('environment', $mockEnvironment);
		$contentContext->_set('domainRepository', $mockDomainRepository);
		$contentContext->_set('siteRepository', $mockSiteRepository);
		$contentContext->initializeObject();

		$this->assertSame($locale, $contentContext->getLocale());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObjectResolvesTheBestMatchingDomainAndSetsTheCurrentSiteAndDomain() {
		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getHTTPHost')->will($this->returnValue('myhost'));

		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site', array(), array(), '', FALSE);

		$mockMatchingDomains = array(
			$this->getMock('F3\TYPO3\Domain\Model\Configuration\Domain', array(), array(), '', FALSE),
			$this->getMock('F3\TYPO3\Domain\Model\Configuration\Domain', array(), array(), '', FALSE)
		);

		$mockMatchingDomains[0]->expects($this->once())->method('getSite')->will($this->returnValue($mockSite));

		$mockDomainRepository = $this->getMock('F3\TYPO3\Domain\Repository\Configuration\DomainRepository', array(), array(), '', FALSE);
		$mockDomainRepository->expects($this->once())->method('findByHost')->with('myhost')->will($this->returnValue($mockMatchingDomains));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');

		$contentContext = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Service\ContentContext'), array('dummy'));
		$contentContext->_set('objectManager', $mockObjectManager);
		$contentContext->_set('domainRepository', $mockDomainRepository);
		$contentContext->_set('environment', $mockEnvironment);

		$contentContext->initializeObject();

		$this->assertSame($mockMatchingDomains[0], $contentContext->getCurrentDomain());
		$this->assertSame($mockSite, $contentContext->getCurrentSite());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObjectSetsTheCurrentSiteToTheFirstSiteFoundIfNoDomainsMatchedTheCurrentRequest() {
		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getHTTPHost')->will($this->returnValue('myhost'));

		$mockSites = array(
			$this->getMock('F3\TYPO3\Domain\Model\Structure\Site', array(), array(), '', FALSE),
			$this->getMock('F3\TYPO3\Domain\Model\Structure\Site', array(), array(), '', FALSE)
		);

		$mockSiteRepository = $this->getMock('F3\TYPO3\Domain\Repository\Structure\SiteRepository', array('findFirst'), array(), '', FALSE);
		$mockSiteRepository->expects($this->once())->method('findFirst')->will($this->returnValue($mockSites[0]));

		$mockDomainRepository = $this->getMock('F3\TYPO3\Domain\Repository\Configuration\DomainRepository', array(), array(), '', FALSE);
		$mockDomainRepository->expects($this->once())->method('findByHost')->with('myhost')->will($this->returnValue(array()));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');

		$contentContext = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Service\ContentContext'), array('dummy'));
		$contentContext->_set('objectManager', $mockObjectManager);
		$contentContext->_set('domainRepository', $mockDomainRepository);
		$contentContext->_set('siteRepository', $mockSiteRepository);
		$contentContext->_set('environment', $mockEnvironment);

		$contentContext->initializeObject();

		$this->assertSame(NULL, $contentContext->getCurrentDomain());
		$this->assertSame($mockSites[0], $contentContext->getCurrentSite());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCurrentSiteReturnsTheCurrentSite() {
		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site', array(), array(), '', FALSE);

		$contentContext = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Service\ContentContext'), array('dummy'));
		$contentContext->_set('currentSite', $mockSite);
		$this->assertSame($mockSite, $contentContext->getCurrentSite());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCurrentDomainReturnsTheCurrentDomainIfAny() {
		$mockDomain = $this->getMock('F3\TYPO3\Domain\Model\Configuration\Domain', array(), array(), '', FALSE);

		$contentContext = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Service\ContentContext'), array('dummy'));

		$this->assertNull($contentContext->getCurrentDomain());
		$contentContext->_set('currentDomain', $mockDomain);
		$this->assertSame($mockDomain, $contentContext->getCurrentDomain());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setNodePathAndNodeGetPathAllowsForSettingTheCurrentNodePath() {
		$contentContext = new \F3\TYPO3\Domain\Service\ContentContext;

		$contentContext->setNodePath('foo/bar/baz');
		$this->assertSame('foo/bar/baz', $contentContext->getNodePath());
	}
}


?>