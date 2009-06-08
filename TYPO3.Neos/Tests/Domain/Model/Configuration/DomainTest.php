<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Model\Configuration;

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
 * Testcase for the "Domain" domain model
 *
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DomainTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setHostPatternAllowsForSettingTheHostPatternOfTheDomain() {
		$domain = new \F3\TYPO3\Domain\Model\Configuration\Domain;
		$domain->setHostPattern('typo3.com');
		$this->assertSame('typo3.com', $domain->getHostPattern());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setSiteEntryPointSetsTheEntryPointForTheDomain() {
		$mockStructureNode = $this->getMock('F3\TYPO3\Domain\Model\StructureNode', array(), array(), '', FALSE);

		$domain = new \F3\TYPO3\Domain\Model\Configuration\Domain;
		$domain->setSiteEntryPoint($mockStructureNode);
		$this->assertSame($mockStructureNode, $domain->getSiteEntryPoint());
	}

}

?>