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
 * Common class for TypoScript Content Objects with Array capabilities
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class AbstractContentArrayObject extends \F3\TypoScript\AbstractContentObject implements \ArrayAccess {

	/**
	 * @var array An array which contains further content objects which can be set and retrieved through numeric indexes
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
		if (!is_integer($offset)) throw new \InvalidArgumentException('Invalid offset while setting the value of an element of the content array. The offset (index) must be of type integer, ' . gettype($offset) . ' given.', 1181064753);
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
		if (!is_integer($offset)) throw new \InvalidArgumentException('Invalid offset while unsetting the value of an element of the content array. The offset (index) must be of type integer, ' . gettype($offset) . ' given.', 1181064754);
		unset($this->contentArray[$offset]);
	}

	/**
	 * Traverses the content array and renders the content of all TypoScript content
	 * object it finds. The result is returned as a whole, merged in the order of the
	 * array offsets.
	 *
	 * @param \F3\TypoScript\RenderingContext $renderingContext
	 * @return string The assembled content of all Content Objects in the internal content array.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render(\F3\TypoScript\RenderingContext $renderingContext) {
		ksort($this->contentArray);
		$content = '';
		foreach ($this->contentArray as $contentItem) {
			if ($contentItem instanceof \F3\TypoScript\ContentObjectInterface) {
				$content .= $contentItem->render($renderingContext);
			}
		}
		return $content;
	}
}
?>