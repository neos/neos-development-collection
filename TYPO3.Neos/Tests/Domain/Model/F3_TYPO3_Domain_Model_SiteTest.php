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
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$site = new \F3\TYPO3\Domain\Model\Site($mockObjectFactory);
		$site->setName('My cool website');
		$this->assertEquals('My cool website', $site->getName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aUniqueIDIsCreatedAutomaticallyWhileConstructingTheSiteObject() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$site1 = new \F3\TYPO3\Domain\Model\Site($mockObjectFactory);
		$site2 = new \F3\TYPO3\Domain\Model\Site($mockObjectFactory);

		$this->assertEquals(36, strlen($site1->getId()));
		$this->assertEquals(36, strlen($site2->getId()));
		$this->assertNotEquals($site1->getId(), $site2->getId());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aRootStructureNodeIsCreatedAutomaticallyWhileConstructingTheSiteObject() {
		$mockRootStructureNode = $this->getMock('F3\TYPO3\Domain\Model\StructureNode');
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')->will($this->returnValue($mockRootStructureNode));

		$site = new \F3\TYPO3\Domain\Model\Site($mockObjectFactory);
		$this->assertSame($mockRootStructureNode, $site->getRootStructureNode());
	}
}

?>