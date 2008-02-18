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
 * Path parser for relative and absolute paths defined in chapter 3.6 ("Path Syntax") 
 * of the JSR-283 specification. This parser should never be called outside the CR!
 *
 * @package		TYPO3CR
 * @version		$Id: T3_TYPO3CR_Workspace.php 328 2007-09-04 13:44:34Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

class T3_TYPO3CR_PathParser implements T3_TYPO3CR_PathParserInterface {

	/**
	 * Parse a path - It can be either a relative or an absolute path. We support same-name siblings as well. 
	 * 
	 * @param string $path Relative or absolute path according to the specification (Section 3.6)
	 * @param T3_phpCR_NodeInterface $currentNode current node
	 * @param integer $searchMode 1 (default) for returning only Nodes, 2 for returning only Properties, 3 for returning both
	 * @return T3_phpCR_NodeInterface the root node
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function parsePath($path, T3_phpCR_NodeInterface $currentNode, $searchMode = self::SEARCH_MODE_NODES) {
		if ($this->isPathAbsolute($path)) {
			$currentNode = $this->getRootNode($currentNode);
			$path = T3_PHP6_Functions::substr($path, 1);
		}

		return $this->parseRelativePath($path, $currentNode, $searchMode);
	}

	/**
	 * Parse a relative path.
	 * 
	 * @param string $path Relative path according to the specification (Section 3.6)
	 * @param T3_phpCR_NodeInterface $currentNode current node
	 * @param integer $searchMode 1 (default) for returning only Nodes, 2 for returning only Properties, 3 for returning both
	 * @return T3_phpCR_NodeInterface the root node
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Implementation of Namespaces!
	 * @todo Add name pattern support
	 */
	protected function parseRelativePath($path, T3_phpCR_NodeInterface $currentNode, $searchMode = self::SEARCH_MODE_NODES) {
		if ($path == '' && ($searchMode & self::SEARCH_MODE_NODES)) {
			return $currentNode;
		}
		list($firstElement, $remainingPath, $numberOfRemainingPathParts) = $this->getFirstPathPart($path);

		if (preg_match('/(.*)\[(.*)\]/', $firstElement, $matchResult)) {
			if ($matchResult[2] < 1) {
				throw new T3_phpCR_RepositoryException('Invalid relative path supplied, index must be > 0!', 1189350810);
			}

			$name = $matchResult[1];
			$nameIndex = $matchResult[2];
		} else {
			$name = $firstElement;
			$nameIndex = 1;
		}

		if ($name == '.') {
			return $this->parseRelativePath($remainingPath, $currentNode, $searchMode);
		}
		if ($name == '..') {
			return $this->parseRelativePath($remainingPath, $currentNode->getParent(), $searchMode);
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
						return $this->parseRelativePath($remainingPath, $currentSubNode, $searchMode);
					}
				} else {
					$currentNameIndex++;
				}
			}
		}

			// check for properties
		if ($numberOfRemainingPathParts == 0 && ($searchMode & self::SEARCH_MODE_PROPERTIES)) {
			$propertyIterator = $currentNode->getProperties();
			foreach ($propertyIterator as $currentProperty) {
				if ($currentProperty->getName() == $name) {
					return $currentProperty;
				}
			}
		}

		throw new T3_phpCR_PathNotFoundException('Node or property not found!', 1189351448);
	}

	/**
	 * Get root node by traversing the tree up
	 * 
	 * @param T3_phpCR_NodeInterface $currentNode current node
	 * @return T3_phpCR_NodeInterface the root node
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	protected function getRootNode(T3_phpCR_NodeInterface $currentNode) {
		try {
			return $currentNode->getParent();
		} catch (T3_phpCR_ItemNotFoundException $e) {
			return $currentNode;
		}
	}

	/**
	 * Checks if a given path is absolute or relative
	 * 
	 * @param string $path Absolute or relative path to check
	 * @return boolean TRUE if path is absolute (e.g. starts with a /), false otherwise
	 * @author Sebastian Kurfuerst <sebastian@typo3.org> 
	 */
	public function isPathAbsolute($path) {
		return ($path{0} == '/');
	}

	/**
	 * Returns the first element of the path and the remainder.
	 * Usage: list($firstElement, $remainingPath, $numberOfElementsRemaining) = T3_TYPO3CR_PathParser::getFirstPathPart($path);
	 * 
	 * @param string $path relative or absolute path
	 * @return array array[0] is first element, array[1] is the rest, and array[2] is the number of parts remaining in array[1]
	 * @author Sebastian Kurfuerst <sebastian@typo3.org> 
	 * @todo optimize avoiding explode/implode: substr_count($stack, $needle) and strpos()?
	 */
	public function getFirstPathPart($path) {
		if (self::isPathAbsolute($path)) { 
			$path = T3_PHP6_Functions::substr($path, 1); 
		}
		$pathArray = explode('/', $path);
		$firstElement = array_shift($pathArray);
		$remainingPath = implode('/', $pathArray);
		return array( $firstElement, $remainingPath, count($pathArray) );
	}

	/**
	 * Returns the last element of the path and the remainder.
	 * Usage: list($lastElement, $remainingPath, $numberOfElementsRemaining) = T3_TYPO3CR_PathParser::getLastPathPart($pathString);
	 * 
	 * @param string $path relative or absolute path
	 * @return array array[0] is last element, array[1] is the first part, and array[2] is the number of parts remaining in array[1]
	 * @author Sebastian Kurfuerst <sebastian@typo3.org> 
	 * @todo optimize avoiding explode/implode: substr_count($stack, $needle) and strpos()?
	 */
	public function getLastPathPart($path) {
		$pathArray = explode('/', $path);
		$lastElement = array_pop($pathArray);
		$remainingPath = implode('/', $pathArray);
		return array( $lastElement, $remainingPath, count($pathArray) );
	}
}
?>