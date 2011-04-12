<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Domain\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Manager for content types
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 */
class ContentTypeManager {

	/**
	 * @inject
	 * @var \F3\TYPO3CR\Domain\Repository\ContentTypeRepository
	 */
	protected $contentTypeRepository;

	/**
	 * @inject
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Content types, indexed by name
	 *
	 * @var array
	 */
	protected $cachedContentTypes = array();

	/**
	 * Returns the speciifed content type
	 *
	 * @return \F3\TYPO3CR\Domain\Model\ContentType or NULL
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentType($name) {
		if ($this->cachedContentTypes === array()) {
			$this->loadContentTypes();
		}
		return isset($this->cachedContentTypes[$name]) ? $this->cachedContentTypes[$name] : NULL;
	}

	/**
	 * Checks if the specified content type exists
	 *
	 * @param string $name Name of the content type
	 * @return boolean TRUE if it exists, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasContentType($name) {
		if ($this->cachedContentTypes === array()) {
			$this->loadContentTypes();
		}
		return isset($this->cachedContentTypes[$name]);
	}

	/**
	 * Creates a new content type
	 *
	 * @param string $contentTypeName Unique ame of the new content type. Example: "TYPO3:Page"
	 * @return \F3\TYPO3CR\Domain\Model\ContentType The new content type
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createContentType($contentTypeName) {
		if ($this->getContentType($contentTypeName) !== NULL) {
			throw new \F3\TYPO3CR\Exception('The content type ' . $contentTypeName . ' already exists.', 1285519455);
		}
		$contentType = $this->objectManager->create('F3\TYPO3CR\Domain\Model\ContentType', $contentTypeName);
		$this->contentTypeRepository->add($contentType);
		$this->cachedContentTypes[$contentTypeName] = $contentType;

		return $contentType;
	}

	/**
	 * Loads all content types into memory.
	 *
	 * @return void
	 * @author Robert Lemke	<robert@typo3.org>
	 */
	protected function loadContentTypes() {
		foreach ($this->contentTypeRepository->findAll()->toArray() as $contentType) {
			$this->cachedContentTypes[$contentType->getName()] = $contentType;
		}
	}

}
?>