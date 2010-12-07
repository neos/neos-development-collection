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
 * A factory for TypoScript Objects
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ObjectFactory {

	/**
	 * @inject
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Creates a new TypoScript object which is supposed to render the given node.
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node The node
	 * @return mixed Either the TypoScript Object or FALSE if no object could be created for the given node
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createByNode(\F3\TYPO3CR\Domain\Model\Node $node) {
		$contentType = $node->getContentType();
		if (strpos($contentType, ':') !== FALSE) {
			list($packageKey, $typoScriptObjectName) = explode(':', $contentType);
			$possibleTypoScriptObjectName = 'F3\\'. $packageKey . '\\TypoScript\\' . $typoScriptObjectName;
			if ($this->objectManager->isRegistered($possibleTypoScriptObjectName) === TRUE) {
				$typoScriptObject = $this->objectManager->create($possibleTypoScriptObjectName);
			}
		}
		if (!isset($typoScriptObject)) {
			$typoScriptObject = $this->objectManager->create('F3\TYPO3\TypoScript\Node');
		}
		$typoScriptObject->setNode($node);
		return $typoScriptObject;
	}

	/**
	 * Creates a new TypoScript object by the specified name.
	 *
	 * @param string $typoScriptObjectName Short object name
	 * @return \F3\TypoScript\ObjectInterface The TypoScript Object
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Needs some real implementation
	 */
	public function createByName($typoScriptObjectName) {
		return $this->objectManager->create('F3\TYPO3\TypoScript\\' . $typoScriptObjectName);
	}
}
?>