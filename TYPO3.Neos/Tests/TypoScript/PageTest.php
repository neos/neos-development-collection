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
 * @package TYPO3
 * @subpackage TypoScript
 * @version $Id$
 */

/**
 * Testcase for the TypoScript Page object
 *
 * @package TYPO3
 * @subpackage TypoScript
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PageTest extends \F3\Testing\BaseTestCase {

	/**
	 * Checks if a Page object renders a simple content without any processors involved.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function pageObjectRendersSimpleContentCorrectly() {
		$page = $this->objectFactory->create('F3\TYPO3\TypoScript\Page');
		$expectedContent = '<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.1 Transitional//EN">
<html>
<head>

</head>
<!--
	This website is brought to you by TYPO3 - inspiring people to share.
	TYPO3 is a free open source Content Management Framework licensed under GNU/GPL.
	Information and contribution at http://www.typo3.com and http://www.typo3.org
-->
<body style="background-color: white;">

</body>
';
		$this->assertEquals($expectedContent, $page->getRenderedContent(), 'The Page object did not return the expected content during the basic check.');
	}

	/**
	 * Checks if setBody throws an exception on an invalid body tag.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function settingInvalidBodyTagThrowsException() {
		try {
			$page = $this->objectFactory->create('F3\TYPO3\TypoScript\Page');
			$page->setBodyTag('<lotty style="">');
			$this->fail('setBodyTag accepted an invalid body tag without throwing an exception.');
		} catch (\F3\TypoScript\Exception $exception) {

		}
	}
}
?>