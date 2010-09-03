<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Version;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(__DIR__ . '/../Fixtures/MockStorageBackend.php');

/**
 * Tests for the Version implementation.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class VersionManagerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @expectedException \F3\PHPCR\UnsupportedRepositoryOperationException
	 */
	public function checkinThrowsExceptionOnUnversionableNode() {
		$mockNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNode->expects($this->any())->method('isNodeType')->will($this->returnValue(FALSE));
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->once())->method('getNode')->with('/UnversionableNode')->will($this->returnValue($mockNode));
		$versionManager = new \F3\TYPO3CR\Version\VersionManager($mockSession);
		$versionManager->checkin('/UnversionableNode');
	}

	/**
	 * @test
	 * @expectedException \F3\PHPCR\InvalidItemStateException
	 */
	public function checkinThrowsExceptionOnModifiedNode() {
		$mockNode = $this->getMock('F3\PHPCR\NodeInterface');
		$mockNode->expects($this->any())->method('isModified')->will($this->returnValue(TRUE));
		$mockSession = $this->getMock('F3\PHPCR\SessionInterface');
		$mockSession->expects($this->once())->method('getNode')->with('/ModifiedNode')->will($this->returnValue($mockNode));
		$versionManager = new \F3\TYPO3CR\Version\VersionManager($mockSession);
		$versionManager->checkin('/ModifiedNode');
	}

}
?>
