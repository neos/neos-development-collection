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
	 * This ID is only for the ORM.
	 *
	 * @var integer
	 * @Id
	 * @GeneratedValue
	*/
	protected $id;

	/**
	 * Name of this content type. Example: "TYPO3CR:Folder"
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Content types this content type directly inherits from
	 *
	 * @var \Doctrine\Common\Collections\ArrayCollection<\F3\TYPO3CR\Domain\Model\ContentType>
	 * @ManyToMany
	 * @JoinTable(name="contentTypesDeclaredSuperTypes",
	 *      joinColumns={@JoinColumn(name="declaredSuperTypeId", referencedColumnName="id")}
	 *      )
	 */
	protected $declaredSuperTypes;

	/**
	 * Constructs this content type
	 *
	 * @param string $name Name of the content type
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($name) {
		$this->name = $name;
		$this->declaredSuperTypes = new \Doctrine\Common\Collections\ArrayCollection();
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
	 * @param \Doctrine\Common\Collections\ArrayCollection<\F3\TYPO3CR\Domain\Model\ContentType> $types
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setDeclaredSuperTypes(\Doctrine\Common\Collections\ArrayCollection $types) {
		foreach ($types as $type) {
			if (!$type instanceof \F3\TYPO3CR\Domain\Model\ContentType) {
				throw new \InvalidArgumentException('$types must be an array of ContentType objects', 1291300950);
			}
		}
		$this->declaredSuperTypes = $types;
	}

	/**
	 * Returns the direct, explicitly declared super types
	 * of this content type.
	 *
	 * @return \Doctrine\Common\Collections\ArrayCollection<\F3\TYPO3CR\Domain\Model\ContentType>
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
		foreach ($this->declaredSuperTypes as $superType) {
			if ($superType->isOfType($contentTypeName) === TRUE) {
				return TRUE;
			}
		}
		return FALSE;
	}
}

?>