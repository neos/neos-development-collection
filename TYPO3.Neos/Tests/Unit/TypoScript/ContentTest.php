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
 * Testcase for the Content TypoScript object
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ContentTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function offsetGetInitializesContentOnFirstCall() {
		$content = $this->getAccessibleMock('F3\TYPO3\TypoScript\Content', array('initializeSections'));
		$content->expects($this->once())->method('initializeSections');

		$content->offsetGet('foo');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function offsetGetReturnsNullForNonExistantOffset() {
		$content = $this->getAccessibleMock('F3\TYPO3\TypoScript\Content', array('initializeSections'));

		$this->assertNull($content->offsetGet('foo'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function offsetExistsChecksIfSectionExists() {
		$mockTypoScriptObject = $this->getMock('F3\TypoScript\ContentObjectInterface');

		$content = $this->getAccessibleMock('F3\TYPO3\TypoScript\Content', array('initializeSections'));
		$content['foo'] = $mockTypoScriptObject;

		$this->assertTrue($content->offsetExists('foo'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function offsetSetInitializesContentOnFirstCall() {
		$content = $this->getAccessibleMock('F3\TYPO3\TypoScript\Content', array('initializeSections'));
		$content->expects($this->once())->method('initializeSections');

		$content->offsetSet('foo', $this->getMock('F3\TypoScript\ContentObjectInterface'));
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function offsetSetThrowsExceptionIfInvalidValueIsGiven() {
		$content = $this->getAccessibleMock('F3\TYPO3\TypoScript\Content', array('initializeSections'));
		$content->expects($this->once())->method('initializeSections');

		$content->offsetSet('foo', 'bar');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function usingArrayAccessASetValueCanBeRetrievedAgain() {
		$content = $this->getAccessibleMock('F3\TYPO3\TypoScript\Content', array('initializeSections'));
		$value = $this->getMock('F3\TypoScript\ContentObjectInterface');

		$content['foo'] = $value;
		$this->assertSame($value, $content['foo']);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function offsetUnsetInitializesContentOnFirstCall() {
		$content = $this->getAccessibleMock('F3\TYPO3\TypoScript\Content', array('initializeSections'));
		$content->expects($this->once())->method('initializeSections');

		$content->offsetUnset('foo');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function offsetUnsetWorks() {
		$content = $this->getAccessibleMock('F3\TYPO3\TypoScript\Content', array('initializeSections'));
		$content['foo'] = $this->getMock('F3\TypoScript\ContentObjectInterface');

		$content->offsetUnset('foo');
		$this->assertNull($content['foo']);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeSectionsIteratesOverUsedSectionsOfPageNodeAndBuildsTypoScriptObjectsForFoundContent() {
		$mockContentNode1 = $this->getMock('F3\TYPO3CR\Domain\Model\Node', array(), array(), '', FALSE);
		$mockContentNode2 = $this->getMock('F3\TYPO3CR\Domain\Model\Node', array(), array(), '', FALSE);
		$mockContentNode2->expects($this->once())->method('getContentType')->will($this->returnValue('TYPO3:Page'));

		$mockSectionNode = $this->getMock('F3\TYPO3CR\Domain\Model\Node', array(), array(), '', FALSE);
		$mockSectionNode->expects($this->once())->method('getName')->will($this->returnValue('default'));
		$mockSectionNode->expects($this->once())->method('getChildNodes')->will($this->returnValue(array($mockContentNode1, $mockContentNode2)));

		$mockCurrentNode = $this->getMock('F3\TYPO3CR\Domain\Model\Node', array(), array(), '', FALSE);
		$mockCurrentNode->expects($this->once())->method('getChildNodes')->with('TYPO3:Section')->will($this->returnValue(array($mockSectionNode)));

		$mockContentContext = $this->getMock('F3\TYPO3\Domain\Service\ContentContext', array(), array('live'));
		$mockContentContext->expects($this->once())->method('getCurrentNode')->will($this->returnValue($mockCurrentNode));

		$mockRenderingContext = $this->getMock('F3\TypoScript\RenderingContext');
		$mockRenderingContext->expects($this->any())->method('getContentContext')->will($this->returnValue($mockContentContext));

		$mockTypoScriptTextObject = $this->getMock('F3\TYPO3\TypoScript\Text');
		$mockContentArray = $this->getMock('F3\TYPO3\TypoScript\ContentArray', array('dummy'));
		$mockTypoScriptObjectFactory = $this->getMock('F3\TypoScript\ObjectFactory');
		$mockTypoScriptObjectFactory->expects($this->once())->method('createByName')->with('ContentArray')->will($this->returnValue($mockContentArray));
		$mockTypoScriptObjectFactory->expects($this->once())->method('createByNode')->with($mockContentNode1)->will($this->returnValue($mockTypoScriptTextObject));

		$content = $this->getAccessibleMock('F3\TYPO3\TypoScript\Content', array('dummy'));
		$content->_set('typoScriptObjectFactory', $mockTypoScriptObjectFactory);
		$content->_set('renderingContext', $mockRenderingContext);

		$content->_call('initializeSections');

		$this->assertSame($mockContentArray, $content['default']);
		$this->assertSame($mockTypoScriptTextObject, $content['default'][0]);
	}
}
?>