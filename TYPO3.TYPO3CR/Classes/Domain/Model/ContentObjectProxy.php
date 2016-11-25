<?php
namespace Neos\ContentRepository\Domain\Model;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\PersistenceManagerInterface;

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
     * @var PersistenceManagerInterface
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
     * @throws IllegalObjectTypeException
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
                    throw new IllegalObjectTypeException('You cannot add an object without an identifier to a ContentObjectProxy. Probably you didn\'t add a valid entity?', 1303859434);
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
