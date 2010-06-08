<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\TypoScript;

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
 * Testcase for the TypoScript standard processors
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PageTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getSectionsInitializesSectionsOnFirstCall() {
		$mockSections = $this->getMock('F3\TYPO3\TypoScript\Sections', array('dummy'));
		$mockTypoScriptObjectFactory = $this->getMock('F3\TypoScript\ObjectFactory');
		$mockTypoScriptObjectFactory->expects($this->once())->method('createByName')->with('Sections')->will($this->returnValue($mockSections));

		$page = $this->getAccessibleMock('F3\TYPO3\TypoScript\Page', array('dummy'));
		$page->_set('typoScriptObjectFactory', $mockTypoScriptObjectFactory);
		$page->_set('model', $this->getMock('F3\TYPO3\Domain\Model\Content\CompositeContentInterface'));

		$this->assertSame($mockSections, $page->getSections());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getSectionsSetsTheModelOnEachCall() {
		$mockSections = $this->getMock('F3\TYPO3\TypoScript\Sections', array('setModel'));
		$mockSections->expects($this->exactly(2))->method('setModel');
		$mockTypoScriptObjectFactory = $this->getMock('F3\TypoScript\ObjectFactory');
		$mockTypoScriptObjectFactory->expects($this->once())->method('createByName')->with('Sections')->will($this->returnValue($mockSections));

		$page = $this->getAccessibleMock('F3\TYPO3\TypoScript\Page', array('dummy'));
		$page->_set('typoScriptObjectFactory', $mockTypoScriptObjectFactory);
		$page->_set('model', $this->getMock('F3\TYPO3\Domain\Model\Content\CompositeContentInterface'));

		$page->getSections();
		$page->getSections();
	}

}
?>