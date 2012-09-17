<?php
namespace TYPO3\TYPO3CR\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A Content Object Proxy object to connect domain models to nodes
 *
 * @FLOW3\Entity
 * @FLOW3\Scope("prototype")
 */
class ContentObjectProxy {

	/**
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 * @FLOW3\Inject
	 */
	protected $persistenceManager;

	/**
	 * Type of the target model
	 *
	 * @var string
	 */
	protected $targetType;

	/**
	 * Technical identifier of the target object
	 *
	 * @var string
	 */
	protected $targetId;

	/**
	 * @var object
	 * @FLOW3\Transient
	 */
	protected $contentObject = NULL;

	/**
	 * Constructs this content type
	 *
	 * @param object $contentObject The content object that should be represented by this proxy
	 */
	public function __construct($contentObject) {
		$this->contentObject = $contentObject;
	}

	/**
	 * Fetches the identifier from the set content object. If that
	 * is not using automatically introduced UUIDs by FLOW3 it tries
	 * to call persistAll() and fetch the identifier again. If it still
	 * fails, an exception is thrown.
	 *
	 * @return void
	 */
	public function initializeObject() {
		if ($this->contentObject !== NULL) {
			$this->targetType = get_class($this->contentObject);
			$this->targetId = $this->persistenceManager->getIdentifierByObject($this->contentObject);
			if ($this->targetId === NULL) {
				$this->persistenceManager->persistAll();
				$this->targetId = $this->persistenceManager->getIdentifierByObject($this->contentObject);
				if ($this->targetId === NULL) {
					throw new \TYPO3\FLOW3\Persistence\Exception\IllegalObjectTypeException('You cannot add an object without an identifier to a ContentObjectProxy. Probably you didn\'t add a valid entity?', 1303859434);
				}
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
			$this->contentObject = $this->persistenceManager->getObjectByIdentifier($this->targetId, $this->targetType);
		}
		return $this->contentObject;
	}
}

?>