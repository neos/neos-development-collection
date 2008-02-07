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
 * A TypoScript Template object
 * 
 * @package		CMS
 * @version 	$Id$
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 *
 * @scope prototype
 */
class T3_TYPO3_TypoScript_Template extends T3_TypoScript_AbstractContentArrayObject {

	/**
	 * This property must be loaded with the template source code, otherwise this content object will return an empty string.
	 *
	 * @var string
	 */
	protected $templateCode = '';

	public function __construct(T3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->templateCode = $componentManager->getComponent('T3_TYPO3_TypoScript_ContentArray');
		$this->templateCode[10] = $componentManager->getComponent('T3_TYPO3_TypoScript_Text');
		$this->templateCode[10]->setValue('test');
	}
	
	/**
	 * Sets the template code
	 *
	 * @param  mixed				$templateCode: The template source code. May be an object with __toString support as it will be casted to a string.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setTemplateCode($templateCode) {
		#$this->templateCode = (string)$templatecode;		
	}
	
	/**
	 * Returns the template code
	 *
	 * @return string				The template source code, markers stay untouched.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTemplateCode() {
		return $this->templateCode;
	}
	
	/**
	 * Returns the rendered content of this content object
	 * 
	 * @return string				The rendered content as a string - usually (X)HTML, XML or just plaing text
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRenderedContent() {
		return $this->templateCode;
	}	
}
?>