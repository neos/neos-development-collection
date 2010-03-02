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
class FrontendContentContextTest extends \F3\Testing\BaseTestCase {

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

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface');

		$frontendContentContext = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Service\FrontendContentContext'), array('dummy'));
		$frontendContentContext->_set('objectFactory', $mockObjectFactory);
		$frontendContentContext->_set('domainRepository', $mockDomainRepository);
		$frontendContentContext->_set('environment', $mockEnvironment);

		$frontendContentContext->initializeObject();

		$this->assertSame($mockMatchingDomains[0], $frontendContentContext->getCurrentDomain());
		$this->assertSame($mockSite, $frontendContentContext->getCurrentSite());
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

		$mockSiteRepository = $this->getMock('F3\TYPO3\Domain\Repository\Structure\SiteRepository', array('findAll'), array(), '', FALSE);
		$mockSiteRepository->expects($this->once())->method('findAll')->will($this->returnValue($mockSites));

		$mockDomainRepository = $this->getMock('F3\TYPO3\Domain\Repository\Configuration\DomainRepository', array(), array(), '', FALSE);
		$mockDomainRepository->expects($this->once())->method('findByHost')->with('myhost')->will($this->returnValue(array()));

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface');

		$frontendContentContext = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Service\FrontendContentContext'), array('dummy'));
		$frontendContentContext->_set('objectFactory', $mockObjectFactory);
		$frontendContentContext->_set('domainRepository', $mockDomainRepository);
		$frontendContentContext->_set('siteRepository', $mockSiteRepository);
		$frontendContentContext->_set('environment', $mockEnvironment);

		$frontendContentContext->initializeObject();

		$this->assertSame(NULL, $frontendContentContext->getCurrentDomain());
		$this->assertSame($mockSites[0], $frontendContentContext->getCurrentSite());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCurrentSiteReturnsTheCurrentSite() {
		$mockSite = $this->getMock('F3\TYPO3\Domain\Model\Structure\Site', array(), array(), '', FALSE);

		$frontendContentContext = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Service\FrontendContentContext'), array('dummy'));
		$frontendContentContext->_set('currentSite', $mockSite);
		$this->assertSame($mockSite, $frontendContentContext->getCurrentSite());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCurrentDomainReturnsTheCurrentDomainIfAny() {
		$mockDomain = $this->getMock('F3\TYPO3\Domain\Model\Configuration\Domain', array(), array(), '', FALSE);

		$frontendContentContext = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Service\FrontendContentContext'), array('dummy'));

		$this->assertNull($frontendContentContext->getCurrentDomain());
		$frontendContentContext->_set('currentDomain', $mockDomain);
		$this->assertSame($mockDomain, $frontendContentContext->getCurrentDomain());
	}
}

?>