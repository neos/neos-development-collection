<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Model\Structure;

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
 * A Content Node
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @entity
 */
class ContentNode extends \F3\TYPO3\Domain\Model\Structure\AbstractNode {

	/**
	 * @var string
	 */
	protected $contentType;

	/**
	 * @var array
	 */
	protected $contents = array();

	/**
	 * Attaches the given content object to this structure node.
	 *
	 * A structure node can refer to multiple content objects, but it can only relate to one content object
	 * per locale. The locale information is retrieved directly from the content object on calling this method.
	 * Any existing content with the same locale will be silently overwritten on calling this function.
	 * Note that the criteria for "same locale" is the language and region. Script and variant are not taken
	 * into account.
	 *
	 * Content added to a structure node must always match the type (ie. class name) of previously set content
	 * objects. The first content object added with this method sets the content type for the structure node.
	 *
	 * @param \F3\TYPO3\Domain\Model\Content\ContentInterface $content The content to attach to this structure node
	 * @return void
	 * @throws \F3\TYPO3\Domain\Exception\InvalidContentType if the content does not matche the type of previously added content.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContent(\F3\TYPO3\Domain\Model\Content\ContentInterface $content) {
		if ($this->contentType !== NULL && get_class($content) !== $this->contentType) {
			throw new \F3\TYPO3\Domain\Exception\InvalidContentType('The given content was of type "' . get_class($content) . '" but the structure node already contains content of type "' . $this->contentType . '". Content types must not be mixed.', 1244713160);
		}
		$locale = $content->getLocale();
		$this->contents[$locale->getLanguage()][$locale->getRegion()] = $content;
		$this->contentType = get_class($content);
	}

	/**
	 * Returns the content specified by $locale.
	 *
	 * This function tries to return a content object which strictly matches the locale of the given
	 * context. If no such content exists it will try to find a content object which is suggested by
	 * the context's fallback strategy.
	 *
	 * @param \F3\TYPO3\Domain\Service\ContentContext $contentContext Context the content should match
	 * @return \F3\TYPO3\Domain\Model\Content\ContentInterface The content object or NULL if none matched the given context
	 * @author Robert Lemke <rober@typo3.org>
	 */
	public function getContent(\F3\TYPO3\Domain\Service\ContentContext $contentContext) {
		$locale = $contentContext->getLocale();
		$language = $locale->getLanguage();
		$region = $locale->getRegion();

		if (isset($this->contents[$language]) && isset($this->contents[$language][$region])) {
			return $this->contents[$language][$region];
		}
		return NULL;
	}

	/**
	 * @param \F3\TYPO3\Domain\Model\Content\ContentInterface $content The content to attach to this structure node
	 * @return void
	 * @throws \F3\TYPO3\Domain\Exception\NoSuchContent if the specified content is not currently attached to this structure node
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeContent(\F3\TYPO3\Domain\Model\Content\ContentInterface $content) {
		$locale = $content->getLocale();
		$language = $locale->getLanguage();
		$region = $locale->getRegion();

		if (!isset($this->contents[$language]) || !isset($this->contents[$language][$region])) {
			throw new \F3\TYPO3\Domain\Exception\NoSuchContent('The specified content with locale ' . $language . '-' . $region . ' is not attached to this structure node.', 1244802597);
		}
		unset($this->contents[$language][$region]);
		if ($this->contents[$language] === array()) {
			unset($this->contents[$language]);
		}
		if ($this->contents === array()) {
			$this->contentType = NULL;
		}
	}

	/**
	 * Returns the type (class name) of the content attached to this structure node.
	 *
	 * @return string The content type (class name) or NULL if no content is attached to this node yet
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentType() {
		return $this->contentType;
	}
}

?>