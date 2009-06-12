<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Model\Content;

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
 * @version $Id$
 */

/**
 * Testcase for the Abstract Content domain model
 *
 * @package TYPO3
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AbstractContentTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theLocaleOfAContentElementMustBePassedToTheConstructor() {
		$mockLocale = $this->getMock('F3\FLOW3\Locale\Locale', array(), array(), '', FALSE);
		$content = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Model\Content\AbstractContent'), array('dummy'), array($mockLocale));
		$this->assertSame($mockLocale, $content->getLocale());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLabelReturnsTheClassNameEnclosedInSquareBrackets() {
		$content = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Model\Content\AbstractContent'), array('dummy'), array(), '', FALSE);
		$this->assertSame('[' . get_class($content) . ']', $content->getLabel());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setStructureNodeAlsoAddsTheContentObjectToTheStructureNodeByCallingAddContent() {
		$mockStructureNode = $this->getMock('F3\TYPO3\Domain\Model\StructureNode', array(), array(), '', FALSE);

		$content = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Model\Content\AbstractContent'), array('dummy'), array(), '', FALSE);
		$mockStructureNode->expects($this->once())->method('setContent');

		$content->setStructureNode($mockStructureNode);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setStructureNodeRemovesTheContentFromAnyPreviousStructureNode() {
		$mockNewStructureNode = $this->getMock('F3\TYPO3\Domain\Model\StructureNode', array(), array(), '', FALSE);
		$mockOldStructureNode = $this->getMock('F3\TYPO3\Domain\Model\StructureNode', array(), array(), '', FALSE);

		$content = $this->getMock($this->buildAccessibleProxy('F3\TYPO3\Domain\Model\Content\AbstractContent'), array('dummy'), array(), '', FALSE);
		$mockOldStructureNode->expects($this->once())->method('removeContent');
		$mockNewStructureNode->expects($this->once())->method('setContent');

		$content->_set('structureNode', $mockOldStructureNode);
		$content->setStructureNode($mockNewStructureNode);
	}
}


?>