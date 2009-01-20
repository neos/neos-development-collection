<?php
declare(ENCODING = 'utf-8');
namespace F3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
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
 * @package TypoScript
 * @version $Id$
 */

/**
 * Contract for a TypoScript parser
 * 
 * @package TypoScript
 * @version $Id$
 * @author Robert Lemke <robert@typo3.org>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
interface ParserInterface {
		
	/**
	 * Parses the given TypoScript source code and returns an object tree
	 * as the result.
	 *
	 * @param  string								$sourceCode: The TypoScript source code to parse
	 * @return \F3\TypoScript\ObjectTree			A TypoScript object tree, generated from the source code
	 */
	public function parse($sourceCode);
		
	/**
	 * Sets the default namespace to the given object name prefix
	 * 
	 * @param  string								$objectNamePrefix: The object name to prepend as the default namespace, without trailing "
	 * @return void
	 */
	public function setDefaultNamespace($objectNamePrefix);

}
?>