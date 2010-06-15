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
 * Testcase for the Menu TypoScript Object
 *
 * @version $Id: FileTest.php 4469 2010-06-08 16:09:45Z k-fish $
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class MenuTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getItemsBuildsTheItemsArrayIfItHasNotBeenBuiltAlready() {
		$mockItems = array('foo' => 'bar');
		
		$menu = $this->getMock('F3\TYPO3\TypoScript\Menu', array('buildItems'));
		$menu->expects($this->once())->method('buildItems')->will($this->returnValue($mockItems));
		
		$this->assertSame($mockItems, $menu->getItems());
		$this->assertSame($mockItems, $menu->getItems());
	}


}
?>