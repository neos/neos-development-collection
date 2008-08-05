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

/**
 * @package TYPO3
 * @subpackage Frontend
 * @version $Id:F3_TYPO3_Controller_Page.php 262 2007-07-13 10:51:44Z robert $
 */

/**
 * TYPO3's frontend page controller
 *
 * @package TYPO3
 * @subpackage Frontend
 * @version $Id:F3_TYPO3_Controller_Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3_Frontend_Controller_Default extends F3_FLOW3_MVC_Controller_ActionController {

	/**
	 * @var array
	 */
	protected $supportedRequestTypes = array('F3_FLOW3_MVC_Web_Request');

	/**
	 * Initalizes the page controller
	 *
	 * @param F3_TypoScript_Parser
	 */
	public function injectTypoScriptParser(F3_TypoScript_ParserInterface $typoScriptParser) {
		$this->typoScriptParser = $typoScriptParser;
		$this->typoScriptParser->setDefaultNamespace('F3_TYPO3_TypoScript');
	}

	/**
	 * Processes a web- request and returns the rendered page as a response
	 *
	 * @param F3_FLOW3_MVC_Web_Request $request: The request to process
	 * @param F3_FLOW3_MVC_Response $response: The response
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function defaultAction() {
		return 'Frontend';
	}
}
?>