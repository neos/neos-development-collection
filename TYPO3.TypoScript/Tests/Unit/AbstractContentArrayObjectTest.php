<?php
namespace TYPO3\TypoScript\Tests\Unit;

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
 * Testcase for the AbstractContentArrayObject
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AbstractContentArrayObjectTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\TypoScript\AbstractContentArrayObject
	 */
	protected $abstractContentArrayObject;

	/**
	 * @var \TYPO3\TypoScript\RenderingContext
	 */
	protected $mockRenderingContext;

	/**
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setUp() {
		$this->abstractContentArrayObject = $this->getMock('TYPO3\TypoScript\AbstractContentArrayObject', array('dummy'));
		$this->mockRenderingContext = $this->getMock('TYPO3\TypoScript\RenderingContext');
		$this->abstractContentArrayObject->setRenderingContext($this->mockRenderingContext);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsAnEmptyStringIfContentArrayIsEmpty() {
		$this->assertEmpty($this->abstractContentArrayObject->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderIteratesThroughContentArrayAndRendersTheirOutput() {
		$mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$mockNode->expects($this->any())->method('isAccessible')->will($this->returnValue(TRUE));

		$mockContentItem1 = $this->getMock('TYPO3\TypoScript\ContentObjectInterface');
		$mockContentItem1->expects($this->once())->method('render')->will($this->returnValue('content1'));
		$mockContentItem1->expects($this->once())->method('getNode')->will($this->returnValue($mockNode));

		$mockContentItem2 = $this->getMock('TYPO3\TypoScript\ContentObjectInterface');
		$mockContentItem2->expects($this->once())->method('render')->will($this->returnValue('content2'));
		$mockContentItem2->expects($this->once())->method('getNode')->will($this->returnValue($mockNode));

		$this->abstractContentArrayObject[0] = $mockContentItem1;
		$this->abstractContentArrayObject[1] = $mockContentItem2;

		$expectedResult = 'content1content2';
		$actualResult = $this->abstractContentArrayObject->render();
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderSkipsInaccessibleContentItems() {
		$mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$mockNode->expects($this->once())->method('isAccessible')->will($this->returnValue(TRUE));
		$mockProtectedNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
		$mockProtectedNode->expects($this->once())->method('isAccessible')->will($this->returnValue(FALSE));

		$mockContentItem1 = $this->getMock('TYPO3\TypoScript\ContentObjectInterface');
		$mockContentItem1->expects($this->never())->method('render');
		$mockContentItem1->expects($this->once())->method('getNode')->will($this->returnValue($mockProtectedNode));

		$mockContentItem2 = $this->getMock('TYPO3\TypoScript\ContentObjectInterface');
		$mockContentItem2->expects($this->once())->method('render')->will($this->returnValue('content2'));
		$mockContentItem2->expects($this->once())->method('getNode')->will($this->returnValue($mockNode));

		$this->abstractContentArrayObject[0] = $mockContentItem1;
		$this->abstractContentArrayObject[1] = $mockContentItem2;

		$expectedResult = 'content2';
		$actualResult = $this->abstractContentArrayObject->render();
		$this->assertSame($expectedResult, $actualResult);
	}
}

?>