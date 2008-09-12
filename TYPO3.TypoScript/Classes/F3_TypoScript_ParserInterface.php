<?php
declare(ENCODING = 'utf-8');
namespace F3::TypoScript;

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
 * Contract for a TypoScript parser
 * 
 * @package		TypoScript
 * @version 	$Id$
 * @author Robert Lemke <robert@typo3.org>
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface ParserInterface {
		
	/**
	 * Parses the given TypoScript source code and returns an object tree
	 * as the result.
	 *
	 * @param  string								$sourceCode: The TypoScript source code to parse
	 * @return F3::TypoScript::ObjectTree			A TypoScript object tree, generated from the source code
	 */
	public function parse($sourceCode);
		
	/**
	 * Sets the default namespace to the given component name prefix
	 * 
	 * @param  string								$componentNamePrefix: The component name to prepend as the default namespace, without trailing "
	 * @return void
	 */
	public function setDefaultNamespace($componentNamePrefix);

}
?>