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
class SectionsTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function offsetGetInitializesSectionsOnFirstCall() {
		$sections = $this->getAccessibleMock('F3\TYPO3\TypoScript\Sections', array('initializeSections'));
		$sections->expects($this->once())->method('initializeSections');

		$sections->offsetGet('foo');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function offsetGetReturnsNullForNonExistantOffset() {
		$sections = $this->getAccessibleMock('F3\TYPO3\TypoScript\Sections', array('initializeSections'));

		$this->assertNull($sections->offsetGet('foo'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function offsetExistsChecksChildNodesOfModelsStructureNode() {
		$mockNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\NodeInterface');
		$mockNode->expects($this->once())->method('hasChildNodes')->with('foo')->will($this->returnValue(TRUE));
		$mockModel = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface');
		$mockModel->expects($this->once())->method('getNode')->will($this->returnValue($mockNode));
		$sections = $this->getAccessibleMock('F3\TYPO3\TypoScript\Sections', array('dummy'));
		$sections->_set('model', $mockModel);

		$this->assertTrue($sections->offsetExists('foo'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function offsetSetInitializesSectionsOnFirstCall() {
		$sections = $this->getAccessibleMock('F3\TYPO3\TypoScript\Sections', array('initializeSections'));
		$sections->expects($this->once())->method('initializeSections');

		$sections->offsetSet('foo', $this->getMock('F3\TypoScript\ContentObjectInterface'));
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function offsetSetThrowsExceptionIfInvalidValueIsGiven() {
		$sections = $this->getAccessibleMock('F3\TYPO3\TypoScript\Sections', array('initializeSections'));
		$sections->expects($this->once())->method('initializeSections');

		$sections->offsetSet('foo', 'bar');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function usingArrayAccessASetValueCanBeRetrievedAgain() {
		$sections = $this->getAccessibleMock('F3\TYPO3\TypoScript\Sections', array('initializeSections'));
		$value = $this->getMock('F3\TypoScript\ContentObjectInterface');

		$sections['foo'] = $value;
		$this->assertSame($value, $sections['foo']);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function offsetUnsetInitializesSectionsOnFirstCall() {
		$sections = $this->getAccessibleMock('F3\TYPO3\TypoScript\Sections', array('initializeSections'));
		$sections->expects($this->once())->method('initializeSections');

		$sections->offsetUnset('foo');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function offsetUnsetWorks() {
		$sections = $this->getAccessibleMock('F3\TYPO3\TypoScript\Sections', array('initializeSections'));
		$sections['foo'] = $this->getMock('F3\TypoScript\ContentObjectInterface');

		$sections->offsetUnset('foo');
		$this->assertNull($sections['foo']);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeSectionsIteratesOverUsedSectionsOfPageNodeAndBuildsTypoScriptObjectsForFoundContent() {
		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext');
		$mockRenderingContext = $this->getMock('F3\TypoScript\RenderingContext');
		$mockRenderingContext->expects($this->any())->method('getContentContext')->will($this->returnValue($mockContentContext));

		$mockPageContent = $this->getMock('F3\TYPO3\Domain\Model\Content\Page', array(), array(), '', FALSE);
		$mockTextContent = $this->getMock('F3\TYPO3\Domain\Model\Content\Text', array(), array(), '', FALSE);

		$mockTextNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$mockTextNode->expects($this->once())->method('getContent')->with($mockContentContext)->will($this->returnValue($mockTextContent));

		$mockPageNode = $this->getMock('F3\TYPO3\Domain\Model\Structure\ContentNode');
		$mockPageNode->expects($this->once())->method('getUsedSectionNames')->will($this->returnValue(array('default')));
		$mockPageNode->expects($this->once())->method('getChildNodes')->with($mockContentContext, 'default')->will($this->returnValue(array($mockPageNode, $mockTextNode)));
		$mockPageNode->expects($this->once())->method('getContent')->with($mockContentContext)->will($this->returnValue($mockPageContent));

		$mockTypoScriptTextObject = $this->getMock('F3\TYPO3\TypoScript\Text');
		$mockContentArray = $this->getMock('F3\TYPO3\TypoScript\ContentArray', array('setModel'));
		$mockTypoScriptObjectFactory = $this->getMock('F3\TypoScript\ObjectFactory');
		$mockTypoScriptObjectFactory->expects($this->once())->method('createByName')->with('ContentArray')->will($this->returnValue($mockContentArray));
		$mockTypoScriptObjectFactory->expects($this->once())->method('createByDomainModel')->with($mockTextContent)->will($this->returnValue($mockTypoScriptTextObject));

		$mockModel = $this->getMock('F3\TYPO3\Domain\Model\Content\ContentInterface');
		$mockModel->expects($this->once())->method('getNode')->will($this->returnValue($mockPageNode));

		$sections = $this->getAccessibleMock('F3\TYPO3\TypoScript\Sections', array('dummy'));
		$sections->_set('typoScriptObjectFactory', $mockTypoScriptObjectFactory);
		$sections->_set('renderingContext', $mockRenderingContext);
		$sections->_set('model', $mockModel);

		$sections->_call('initializeSections');

		$this->assertSame($mockContentArray, $sections['default']);
		$this->assertSame($mockTypoScriptTextObject, $sections['default'][0]);
	}
}
?>