<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Domain\Model;

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
 * A Content Type
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @entity
 * @scope prototype
 */
class ContentType {

	/**
	 * Name of this content type. Example: "TYPO3CR:Folder"
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Content types this content type directly inherits from
	 *
	 * @var array<\F3\TYPO3CR\Domain\Model\ContentType>
	 */
	protected $declaredSuperTypes = array();

	/**
	 * Constructs this content type
	 *
	 * @param string $name Name of the content type
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($name) {
		$this->name = $name;
	}

	/**
	 * Returns the name of this content type
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Declares the super types this content type inherits from.
	 *
	 * @param array<\F3\TYPO3CR\Domain\Model\ContentType> $types
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setDeclaredSuperTypes(array $types) {
		$this->declaredSuperTypes = $types;
	}

	/**
	 * Returns the direct, explicitly declared super types
	 * of this content type.
	 *
	 * @return array<\F3\TYPO3CR\Domain\Model\ContentType>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDeclaredSuperTypes() {
		return $this->declaredSuperTypes;
	}

	/**
	 * If this content type or any of the direct or indirect super types
	 * has the given name.
	 *
	 * @param string $contentTypeName
	 * @return boolean TRUE if this content type is of the given kind, otherwise FALSE
	 */
	public function isOfType($contentTypeName) {
		if ($contentTypeName === $this->name) {
			return TRUE;
		}
		foreach ($this->superTypes as $superType) {
			if ($superType->isOfType($contentTypeName) === TRUE) {
				return TRUE;
			}
		}
		return FALSE;
	}
}

?>