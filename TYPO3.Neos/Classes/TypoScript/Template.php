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
 * A TypoScript Template object
 * 
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class Template extends \F3\TypoScript\AbstractContentArrayObject {

	/**
	 * This property must be loaded with the template source code, otherwise this content object will return an empty string.
	 *
	 * @var string
	 */
	protected $templateCode = '';

	public function __construct(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->templateCode = $objectFactory->create('F3\TYPO3\TypoScript\ContentArray');
		$this->templateCode[10] = $objectFactory->create('F3\TYPO3\TypoScript\Text');
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