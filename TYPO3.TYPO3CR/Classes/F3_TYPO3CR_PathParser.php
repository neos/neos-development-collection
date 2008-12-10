<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR;

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
 * @package TYPO3CR
 * @version $Id$
 */

/**
 * Path parser for relative and absolute paths defined in chapter 3.6 ("Path Syntax")
 * of the JSR-283 specification. This parser should never be called outside the CR!
 *
 * @package TYPO3CR
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class PathParser {

	const SEARCH_MODE_NODES = 1;
	const SEARCH_MODE_PROPERTIES = 2;
	const SEARCH_MODE_ITEMS = 3;

	/**
	 * Parse a path - It can be either a relative or an absolute path. We support same-name siblings as well.
	 *
	 * @param string $path Relative or absolute path according to the specification (Section 3.6)
	 * @param \F3\PHPCR\NodeInterface $currentNode current node
	 * @param integer $searchMode 1 (default) for returning only Nodes, 2 for returning only Properties, 3 for returning both
	 * @return \F3\PHPCR\NodeInterface the root node
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public static function parsePath($path, \F3\PHPCR\NodeInterface $currentNode, $searchMode = self::SEARCH_MODE_NODES) {
		if (self::isPathAbsolute($path)) {
			$currentNode = self::getRootNode($currentNode);
			$path = \F3\PHP6\Functions::substr($path, 1);
		}

		return self::parseRelativePath($path, $currentNode, $searchMode);
	}

	/**
	 * Parse a relative path.
	 *
	 * @param string $path Relative path according to the specification (Section 3.6)
	 * @param \F3\PHPCR\NodeInterface $currentNode current node
	 * @param integer $searchMode 1 (default) for returning only Nodes, 2 for returning only Properties, 3 for returning both
	 * @return \F3\PHPCR\NodeInterface the root node
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Implementation of Namespaces!
	 * @todo Add name pattern support
	 */
	protected static function parseRelativePath($path, \F3\PHPCR\NodeInterface $currentNode, $searchMode = self::SEARCH_MODE_NODES) {
		if ($path == '' && ($searchMode & self::SEARCH_MODE_NODES)) {
			return $currentNode;
		}
		list($firstElement, $remainingPath, $numberOfRemainingPathParts) = self::getFirstPathPart($path);

		$matchResult = array();
		if (preg_match('/(.*)\[(.*)\]/', $firstElement, $matchResult)) {
			if ($matchResult[2] < 1) {
				throw new \F3\PHPCR\RepositoryException('Invalid relative path supplied, index must be > 0!', 1189350810);
			}

			$name = $matchResult[1];
			$nameIndex = $matchResult[2];
		} else {
			$name = $firstElement;
			$nameIndex = 1;
		}

		if ($name == '.') {
			return self::parseRelativePath($remainingPath, $currentNode, $searchMode);
		}
		if ($name == '..') {
			return self::parseRelativePath($remainingPath, $currentNode->getParent(), $searchMode);
		}

			// Once NamePatterns are implemented, it will be a lot easier!
		$nodeIterator = $currentNode->getNodes();
		$currentNameIndex = 1;
		foreach ($nodeIterator as $currentSubNode) {
			if ($currentSubNode->getName() == $name) {
				if ($currentNameIndex == $nameIndex) {
					if ($numberOfRemainingPathParts == 0) {
						if ($searchMode & self::SEARCH_MODE_NODES) {
							return $currentSubNode;
						}
					} else {
						return self::parseRelativePath($remainingPath, $currentSubNode, $searchMode);
					}
				} else {
					$currentNameIndex++;
				}
			}
		}

			// check for properties
		if ($numberOfRemainingPathParts == 0 && ($searchMode & self::SEARCH_MODE_PROPERTIES)) {
			if ($currentNode->hasProperty($name)) {
				return $currentNode->getProperty($name);
			}
		}

		throw new \F3\PHPCR\PathNotFoundException('Node or property not found!', 1189351448);
	}

	/**
	 * Get root node by traversing the tree up
	 *
	 * @param \F3\PHPCR\NodeInterface $currentNode current node
	 * @return \F3\PHPCR\NodeInterface the root node
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected static function getRootNode(\F3\PHPCR\NodeInterface $currentNode) {
		if ($currentNode->getDepth() > 0) {
			return $currentNode->getParent();
		} else {
			return $currentNode;
		}
	}

	/**
	 * Checks if a given path is absolute or relative
	 *
	 * @param string $path Absolute or relative path to check
	 * @return boolean TRUE if path is absolute (e.g. starts with a /), FALSE otherwise
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public static function isPathAbsolute($path) {
		return ($path{0} === '/');
	}

	/**
	 * Returns the first element of the path and the remainder.
	 * Usage: list($firstElement, $remainingPath, $numberOfElementsRemaining) = \F3\TYPO3CR\PathParser::getFirstPathPart($path);
	 *
	 * @param string $path relative or absolute path
	 * @return array array[0] is first element, array[1] is the rest, and array[2] is the number of parts remaining in array[1]
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @todo optimize avoiding explode/implode: substr_count($stack, $needle) and strpos()?
	 */
	public static function getFirstPathPart($path) {
		if (self::isPathAbsolute($path)) {
			$path = \F3\PHP6\Functions::substr($path, 1);
		}
		$pathArray = explode('/', $path);
		$firstElement = array_shift($pathArray);
		$remainingPath = implode('/', $pathArray);
		return array( $firstElement, $remainingPath, count($pathArray) );
	}

	/**
	 * Returns the last element of the path and the remainder.
	 * Usage: list($lastElement, $remainingPath, $numberOfElementsRemaining) = \F3\TYPO3CR\PathParser::getLastPathPart($pathString);
	 *
	 * @param string $path relative or absolute path
	 * @return array array[0] is last element, array[1] is the first part, and array[2] is the number of parts remaining in array[1]
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @todo optimize avoiding explode/implode: substr_count($stack, $needle) and strpos()?
	 */
	public static function getLastPathPart($path) {
		$pathArray = explode('/', $path);
		$lastElement = array_pop($pathArray);
		$remainingPath = implode('/', $pathArray);
		return array( $lastElement, $remainingPath, count($pathArray) );
	}
}
?>