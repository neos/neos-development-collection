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
 * A Content Object Proxy object to connect domain models to nodes
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @entity
 * @scope prototype
 */
class ContentObjectProxy {

	/**
	 * @var \F3\FLOW3\Persistence\PersistenceManagerInterface
	 * @inject
	 */
	protected $persistenceManager;

	/**
	 * This ID is only for the ORM.
	 *
	 * @var integer
	 * @Id
	 * @GeneratedValue
	*/
	protected $id;

	/**
	 * Type of the target model
	 *
	 * @var string
	 */
	protected $targetType;

	/**
	 * Artificial Id of the target object
	 *
	 * @var integer
	 */
	protected $targetId;

	/**
	 * @var object
	 * @transient
	 */
	protected $contentObject = NULL;

	/**
	 * Constructs this content type
	 *
	 * @param object $contentObject The content object that should be represented by this proxy
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct($contentObject) {
		$this->contentObject = $contentObject;
	}

	/**
	 * @return void
	 */
	public function initializeObject() {
		if ($this->contentObject !== NULL) {
			$this->targetType = get_class($this->contentObject);
			$this->targetId = $this->persistenceManager->getIdentifierByObject($this->contentObject);
			if ($this->targetId === NULL) {
					// FIXME The ID is not present at the moment... doctrine fix needed... workaround!
				$this->persistenceManager->persistAll();
				$this->targetId = $this->persistenceManager->getIdentifierByObject($this->contentObject);
			}
		}
	}

	/**
	 * @param integer $targetId
	 * @return void
	 */
	public function setTargetId($targetId) {
		$this->targetId = $targetId;
	}

	/**
	 * @return integer
	 */
	public function getTargetId() {
		return $this->targetId;
	}

	/**
	 * @param string $targetType
	 * @return void
	 */
	public function setTargetType($targetType) {
		$this->targetType = $targetType;
	}

	/**
	 * @return string
	 */
	public function getTargetType() {
		return $this->targetType;
	}

	/**
	 * @return object
	 */
	public function getObject() {
		if ($this->contentObject === NULL) {
			$this->contentObject = $this->persistenceManager->findByIdentifier($this->targetId, $this->targetType);
		}
		return $this->contentObject;
	}
}

?>