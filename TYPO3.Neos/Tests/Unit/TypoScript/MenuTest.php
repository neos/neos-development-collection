<?php
namespace TYPO3\TYPO3\Tests\Unit\TypoScript;

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
 * Testcase for the Menu TypoScript Object
 *
 */
class MenuTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getItemsBuildsTheItemsArrayIfItHasNotBeenBuiltAlready() {
		$mockItems = array('foo' => 'bar');

		$mockContentContext = $this->getMock('TYPO3\TYPO3\Domain\Service\ContentContext', array(), array('live'));

		$mockRenderingContext = $this->getMock('TYPO3\TypoScript\RenderingContext');
		$mockRenderingContext->expects($this->once())->method('getContentContext')->will($this->returnValue($mockContentContext));

		$menu = $this->getMock('TYPO3\TYPO3\TypoScript\Menu', array('buildItems'));
		$menu->setRenderingContext($mockRenderingContext);

		$menu->expects($this->once())->method('buildItems')->will($this->returnValue($mockItems));

		$this->assertSame($mockItems, $menu->getItems());
		$this->assertSame($mockItems, $menu->getItems());
	}

	/**
	 *
	 * test
	 * @dataProvider buildItemData
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Either finish test implementation or replace by system test
	 */
	public function buildItemsCanBuildDifferentKindsOfMenus($entryLevel, $lastLevel, \TYPO3\TYPO3\Domain\Service\ContentContext $contentContext, array $expectedItems) {
		$menu = $this->getAccessibleMock('TYPO3\TYPO3\TypoScript\Menu', array('dummy'));
		$menu->setEntryLevel($entryLevel);
		$menu->setLastLevel($lastLevel);

		$actualItems = $menu->_call('buildItems', $contentContext);
		$this->assertSame($expectedItems, $actualItems);
	}


	/**
	 *
	 */
	public function buildItemData() {
		$currentSite = $this->getMock('TYPO3\TYPO3\Domain\Model\Site', array(), array(), '', FALSE);

		$contentContext = $this->getMock('TYPO3\TYPO3\Domain\Service\ContentContext');
		$contentContext->expects($this->any())->method('getCurrentSite')->will($this->returnValue($currentSite));

		return array(
			array(
				1, 1, $contentContext, array()
			)
		);
	}
}
?>