<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Model;

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
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 */

/**
 * Testcase for the "Site" domain model
 *
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SiteTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aNameCanBeSetAndRetrievedFromTheSite() {
		$site = new \F3\TYPO3\Domain\Model\Site();
		$site->setName('My cool website');
		$this->assertSame('My cool website', $site->getName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aSiteCanHaveAnyNumberOfDomains() {
		$domain1 = $this->getMock('F3\TYPO3\Domain\Model\Configuration\Domain', array(), array(), '' ,FALSE);
		$domain2 = $this->getMock('F3\TYPO3\Domain\Model\Configuration\Domain', array(), array(), '' ,FALSE);

		$site = new \F3\TYPO3\Domain\Model\Site();
		$site->addDomain($domain1);
		$site->addDomain($domain2);

		$domains = $site->getDomains();
		$this->assertTrue($domains->contains($domain1));
		$this->assertTrue($domains->contains($domain2));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeDomainRemovesADomainDefinitionFromTheSite() {
		$domain = $this->getMock('F3\TYPO3\Domain\Model\Configuration\Domain', array(), array(), '' ,FALSE);

		$site = new \F3\TYPO3\Domain\Model\Site();
		$site->addDomain($domain);

		$domains = $site->getDomains();
		$this->assertTrue($domains->contains($domain));

		$site->removeDomain($domain);

		$domains = $site->getDomains();
		$this->assertFalse($domains->contains($domain));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDomainsClonesTheObjectHashBeforeReturningIt() {
		$domain = $this->getMock('F3\TYPO3\Domain\Model\Configuration\Domain', array(), array(), '' ,FALSE);

		$site = new \F3\TYPO3\Domain\Model\Site();
		$site->addDomain($domain);

		$domains = $site->getDomains();
		$domains->detach($domain);

		$domains = $site->getDomains();
		$this->assertTrue($domains->contains($domain));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setSiteRootDefinesTheStructureNodeWhichActsAsTheRootOfTheSite() {
		$mockStructureNode = $this->getMock('F3\TYPO3\Domain\Model\StructureNode', array(), array(), '', FALSE);

		$site = new \F3\TYPO3\Domain\Model\Site();
		$site->setSiteRoot($mockStructureNode);
		$this->assertSame($mockStructureNode, $site->getSiteRoot());
	}
}

?>