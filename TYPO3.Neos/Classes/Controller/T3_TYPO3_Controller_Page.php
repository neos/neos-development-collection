<?php
declare(encoding = 'utf-8');

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
 * A generic controller for pages.
 * 
 * Note: This is not a "Page Controller" as in the Patterns of Enterprise 
 *       Application Architecture (PoEAA).
 * 
 * @package		CMS
 * @version 	$Id:T3_TYPO3_Controller_Page.php 262 2007-07-13 10:51:44Z robert $
 * @copyright	Copyright belongs to the respective authorst
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_TYPO3_Controller_Page extends T3_FLOW3_MVC_Controller_RequestHandlingController {

	/**
	 * @var T3_TypoScript_ParserInterface
	 */
	protected $typoScriptParser;
	
	/**
	 * Initalizes the page controller
	 *
	 * @param T3_TypoScript_Parser
	 */
	public function initializeComponent() {
		$this->typoScriptParser = $this->componentManager->getComponent('T3_TypoScript_ParserInterface');
		$this->typoScriptParser->setDefaultNamespace('T3_TYPO3_TypoScript');		
	}
	
	/**
	 * Processes a web- request and returns the rendered page as a response
	 *
	 * @param  T3_FLOW3_MVC_Web_Request		$request: The request to process
	 * @param  T3_FLOW3_MVC_Response		$response: The response
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processWebRequest(T3_FLOW3_MVC_Web_Request $request, T3_FLOW3_MVC_Web_Response $response) {		
		$typoScriptObjectTree = $this->typoScriptParser->parse(file_get_contents(dirname(__FILE__) . '/../../Tests/Fixtures/PreliminaryPageConfiguration.ts'));		
		$viewName = $request->hasArgument('view') ? $request->getArgument('view') : 'T3_TYPO3_View_Page';
		if (!$this->componentManager->isComponentRegistered($viewName)) {
			$response->setContent('Error: A view with the name "' . $viewName . '" is not available.');
			return $response;
		}
	
		$view = $this->componentManager->getComponent($viewName);
		if (!$view instanceof T3_FLOW3_MVC_View_Abstract) {
			$response->setContent('Error: The component with the name "' . $viewName . '" is not a view.');
			return;			
		}
		
		$response->setContent($this->newXHTML($view->render($typoScriptObjectTree)));
	}

}

?>