<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

require_once 'PHPUnit/Framework.php';

/**
 * Testcase for the TypoScript Page object
 * 
 * @package		CMS
 * @version 	$Id:F3_FLOW3_Component_ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3_TypoScript_PageTest extends F3_Testing_BaseTestCase {
	
	/**
	 * Checks if a Page object renders a simple content without any processors involved.
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function pageObjectRendersSimpleContentCorrectly() {
		$page = $this->componentManager->getComponent('F3_TYPO3_TypoScript_Page');
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function settingInvalidBodyTagThrowsException() {
		try {
			$page = $this->componentManager->getComponent('F3_TYPO3_TypoScript_Page');
			$page->setBodyTag('<lotty style="">');
			$this->fail('setBodyTag accepted an invalid body tag without throwing an exception.');
		} catch (F3_TypoScript_Exception $exception) {
			
		}
	}
}
?>