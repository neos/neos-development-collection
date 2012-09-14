<?php
namespace TYPO3\TypoScript\Core;

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
 * @api
 */
interface ParserInterface {

	/**
	 * Parses the given TypoScript source code and returns an object tree
	 * as the result.
	 *
	 * @param string $sourceCode The TypoScript source code to parse
	 * @param string $contextPathAndFilename An optional path and filename to use as a prefix for inclusion of further TypoScript files
	 * @param array $objectTreeUntilNow Used internally for keeping track of the built object tree
	 * @return array A TypoScript object tree, generated from the source code
	 * @throws \TYPO3\TypoScript\Exception
	 * @api
	 */
	public function parse($sourceCode, $contextPathAndFilename = NULL, array $objectTreeUntilNow = array());

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