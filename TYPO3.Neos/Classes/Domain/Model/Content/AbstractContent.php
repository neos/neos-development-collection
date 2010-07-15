<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Model\Content;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
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
 * Domain model of a generic content element
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @entity
 * @api
 */
abstract class AbstractContent implements \F3\TYPO3\Domain\Model\Content\ContentInterface {

	/**
	 * @var \F3\FLOW3\I18n\Locale
	 */
	protected $locale;

	/**
	 * @var \F3\TYPO3\Domain\Model\Structure\ContentNode
	 * @validate NotEmpty
	 */
	protected $node;

	/**
	 * Constructs the content object
	 *
	 * @param \F3\FLOW3\I18n\Locale $locale The locale of the content
	 * @param \F3\TYPO3\Domain\Model\Structure\ContentNode $node The content node this content is bound to
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function __construct(\F3\FLOW3\I18n\Locale $locale, \F3\TYPO3\Domain\Model\Structure\ContentNode $node) {
		$this->locale = $locale;
		$this->node = $node;
		$node->setContent($this);
	}

	/**
	 * Returns the locale of the content object
	 *
	 * @return \F3\FLOW3\I18n\Locale $locale The locale of the content
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getLocale() {
		return $this->locale;
	}

	/**
	 * Returns a short string which can be used to label the content object
	 *
	 * @return string A label for the content object
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getLabel() {
		return '[' . get_class($this) . ']';
	}

	/**
	 * Returns the content node for this content object
	 *
	 * @return \F3\TYPO3\Domain\Model\Structure\ContentNode $node The node this content is bound to
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getContainingNode() {
		return $this->node;
	}

	/**
	 * Cloning of content is not allowed by default
	 *
	 * @return void
	 * @throws \F3\TYPO3\Domain\Exception\CannotCloneException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __clone() {
		throw new \F3\TYPO3\Domain\Exception\CannotCloneException('Cloning of ' . get_class($this) . ' is not allowed.', 1175793217);
	}
}
?>