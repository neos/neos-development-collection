<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3::Domain::Model;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
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
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class SiteTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aNameCanBeSetAndRetrievedFromTheSite() {
		$mockComponentFactory = $this->getMock('F3::FLOW3::Component::FactoryInterface');
		$site = new F3::TYPO3::Domain::Model::Site($mockComponentFactory);
		$site->setName('My cool website');
		$this->assertEquals('My cool website', $site->getName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aUniqueIDIsCreatedAutomaticallyWhileConstructingTheSiteObject() {
		$mockComponentFactory = $this->getMock('F3::FLOW3::Component::FactoryInterface');
		$site1 = new F3::TYPO3::Domain::Model::Site($mockComponentFactory);
		$site2 = new F3::TYPO3::Domain::Model::Site($mockComponentFactory);

		$this->assertEquals(36, strlen($site1->getId()));
		$this->assertEquals(36, strlen($site2->getId()));
		$this->assertNotEquals($site1->getId(), $site2->getId());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aRootStructureNodeIsCreatedAutomaticallyWhileConstructingTheSiteObject() {
		$mockRootStructureNode = $this->getMock('F3::TYPO3::Domain::Model::StructureNode');
		$mockComponentFactory = $this->getMock('F3::FLOW3::Component::FactoryInterface');
		$mockComponentFactory->expects($this->once())->method('create')->will($this->returnValue($mockRootStructureNode));

		$site = new F3::TYPO3::Domain::Model::Site($mockComponentFactory);
		$this->assertSame($mockRootStructureNode, $site->getRootStructureNode());
	}
}

?>