<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3::TypoScript;

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
 * A TypoScript Text object
 * 
 * @package		CMS
 * @version 	$Id$
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 *
 * @scope prototype
 */
class Text extends F3::TypoScript::AbstractContentObject {

	/**
	 * @var string Content of this Text TypoScript object
	 */
	protected $value = '';
	
	/**
	 * Sets the Content
	 *
	 * @param  string			$value: Text value of this Text object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setValue($value) {
		$this->value = (string)$value;
	}
	
	/**
	 * Returns the Content of this Text object
	 *
	 * @return string			The text value of this Text object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Returns the rendered content of this content object
	 * 
	 * @return string				The rendered content as a string - usually (X)HTML, XML or just plaing text
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRenderedContent() {
		return $this->getProcessedProperty('value');
	}	
}
?>