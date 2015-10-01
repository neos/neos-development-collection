<?php
namespace TYPO3\TYPO3CR\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A Content Object Proxy object to connect domain models to nodes
 *
 * This class is never used directly in userland but is instantiated automatically
 * through setContentObject() in AbstractNodeData.
 *
 * @Flow\Entity
 */
class ContentObjectProxy
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
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
     * @Flow\Transient
     */
    protected $contentObject = null;

    /**
     * Constructs this content type
     *
     * @param object $contentObject The content object that should be represented by this proxy
     */
    public function __construct($contentObject)
    {
        $this->contentObject = $contentObject;
    }

    /**
     * Fetches the identifier from the set content object. If that
     * is not using automatically introduced UUIDs by Flow it tries
     * to call persistAll() and fetch the identifier again. If it still
     * fails, an exception is thrown.
     *
     * @return void
     * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    protected function initializeObject()
    {
        if ($this->contentObject !== null) {
            $this->targetType = get_class($this->contentObject);
            $this->targetId = $this->persistenceManager->getIdentifierByObject($this->contentObject);
            if ($this->targetId === null) {
                $this->persistenceManager->persistAll();
                $this->targetId = $this->persistenceManager->getIdentifierByObject($this->contentObject);
                if ($this->targetId === null) {
                    throw new \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException('You cannot add an object without an identifier to a ContentObjectProxy. Probably you didn\'t add a valid entity?', 1303859434);
                }
            }
        }
    }

    /**
     * Returns the real object this proxy stands for
     *
     * @return object The "content object" as it was originally passed to the constructor
     */
    public function getObject()
    {
        if ($this->contentObject === null) {
            $this->contentObject = $this->persistenceManager->getObjectByIdentifier($this->targetId, $this->targetType);
        }
        return $this->contentObject;
    }
}
