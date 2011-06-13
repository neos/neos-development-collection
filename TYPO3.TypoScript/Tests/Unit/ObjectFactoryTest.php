<?php
namespace F3\TypoScript\Tests\Unit;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
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
 * Testcase for the TypoScript Object Factory
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ObjectFactoryTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @dataProvider unsupportedContentTypes
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createByNodeCreatesANodeTypoScriptObjectIfNoSpecializedTypoScriptObjectExistsForTheContentType($contentType) {
		$node = $this->getMock('F3\TYPO3CR\Domain\Model\NodeInterface', array(), array(), '', FALSE);
		$node->expects($this->once())->method('getContentType')->will($this->returnValue($contentType));

		$expectedTypoScriptObject = $this->getMock('F3\TypoScript\ObjectInterface');
		$expectedTypoScriptObject->expects($this->once())->method('setNode')->with($node);

		$objectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$objectManager->expects($this->once())->method('create')->with('F3\TYPO3\TypoScript\Node')->will($this->returnValue($expectedTypoScriptObject));
		$objectManager->expects($this->any())->method('isRegistered')->will($this->returnValue(FALSE));

		$objectFactory = $this->getAccessibleMock('F3\TypoScript\ObjectFactory', array('dummy'));
		$objectFactory->_set('objectManager', $objectManager);

		$actualTypoScriptObject = $objectFactory->createByNode($node);

		$this->assertSame($expectedTypoScriptObject, $actualTypoScriptObject);
	}

	/**
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function unsupportedContentTypes() {
		return array(
			array('unstructured'),
			array('TYPO3:Googolplex'),
			array('urks'),
			array('Drupal:Node'),
			array('-')
		);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createByNodeCreatesASpecificTypoScriptObjectIfOneExistsForTheContentType() {
		$node = $this->getMock('F3\TYPO3CR\Domain\Model\NodeInterface', array(), array(), '', FALSE);
		$node->expects($this->once())->method('getContentType')->will($this->returnValue('TYPO3:ContensUniversalis'));

		$expectedTypoScriptObject = $this->getMock('F3\TypoScript\ObjectInterface');
		$expectedTypoScriptObject->expects($this->once())->method('setNode')->with($node);

		$objectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$objectManager->expects($this->once())->method('create')->with('F3\TYPO3\TypoScript\ContensUniversalis')->will($this->returnValue($expectedTypoScriptObject));
		$objectManager->expects($this->any())->method('isRegistered')->will($this->returnValue(TRUE));

		$objectFactory = $this->getAccessibleMock('F3\TypoScript\ObjectFactory', array('dummy'));
		$objectFactory->_set('objectManager', $objectManager);

		$actualTypoScriptObject = $objectFactory->createByNode($node);

		$this->assertSame($expectedTypoScriptObject, $actualTypoScriptObject);
	}
}

?>