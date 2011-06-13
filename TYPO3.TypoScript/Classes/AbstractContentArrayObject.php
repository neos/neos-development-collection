<?php
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
 * Common class for TypoScript Content Objects with Array capabilities
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class AbstractContentArrayObject extends \F3\TypoScript\AbstractContentObject implements \ArrayAccess, \Countable {

	/**
	 * An array which contains further content objects which can be set and retrieved through numeric indexes
	 * @var array
	 */
	protected $contentArray = array();

	/**
	 * Checks if an offset in the contentArray exists.
	 *
	 * @param mixed $offset The offset to check
	 * @return boolean TRUE if the offset exists, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function offsetExists($offset) {
		return isset($this->contentArray[$offset]);
	}

	/**
	 * Returns the value of the contentArray with the given offset. If a value with the
	 * given offset does not exist yet, it will be created and set to an empty array!
	 * This is neccessary to make life easier for the TypoScript parser.
	 *
	 * Note: Always use isset() to check if a value is set or not.
	 *
	 * @param mixed $offset The offset (index) of the value to return
	 * @return mixed The value of the content object array with the specified offset
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function offsetGet($offset) {
		if (!isset($this->contentArray[$offset])) {
			$this->contentArray[$offset] = array();
		}
		return $this->contentArray[$offset];
	}

	/**
	 * Sets the value of the contentArray with the given offset
	 *
	 * @param integer $offset The offset (index) of the value to set.
	 * @param mixed $value The value
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \InvalidArgumentException
	 */
	public function offsetSet($offset, $value) {
		$this->contentArray[$offset] = $value;
	}

	/**
	 * Unsets the value of the contentArray with the given offset
	 *
	 * @param integer $offset The offset (index) of the value to unset.
	 * @param mixed $value The value
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \InvalidArgumentException
	 */
	public function offsetUnset($offset) {
		unset($this->contentArray[$offset]);
	}

	/**
	 * Returns the number of elements in this content array
	 *
	 * @return integer
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function count() {
		return count($this->contentArray);
	}

	/**
	 * Traverses the content array and renders the content of all TypoScript content
	 * object it finds. The result is returned as a whole, merged in the order of the
	 * array offsets.
	 *
	 * @return string The assembled content of all Content Objects in the internal content array.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render() {
		$content = '';
		foreach ($this->contentArray as $contentItem) {
			if ($contentItem instanceof \F3\TypoScript\ContentObjectInterface) {
				$contentItem->setRenderingContext($this->renderingContext);
				$content .= $contentItem->render();
			}
		}
		return $content;
	}
}
?>