<?php
namespace TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Contract for a TypoScript parser
 *
 * @author Robert Lemke <robert@typo3.org>
 * @api
 */
interface ParserInterface {

	/**
	 * Parses the given TypoScript source code and returns an object tree
	 * as the result.
	 *
	 * @param string $sourceCode The TypoScript source code to parse
	 * @return \TYPO3\TypoScript\ObjectTree A TypoScript object tree, generated from the source code
	 * @api
	 */
	public function parse($sourceCode);

	/**
	 * Sets the default namespace to the given object name prefix
	 *
	 * @param string $objectNamePrefix The object name to prepend as the default namespace, without trailing "
	 * @return void
	 * @api
	 */
	public function setDefaultNamespace($objectNamePrefix);

}
?>