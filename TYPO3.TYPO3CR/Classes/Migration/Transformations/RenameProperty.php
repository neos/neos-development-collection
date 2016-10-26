<?php
namespace TYPO3\TYPO3CR\Migration\Transformations;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeData;

/**
 * Rename a given property.
 */
class RenameProperty extends AbstractTransformation
{
    /**
     * Property name to change
     *
     * @var string
     */
    protected $oldPropertyName;

    /**
     * New name of property
     *
     * @var string
     */
    protected $newPropertyName;

    /**
     * Sets the name of the property to change.
     *
     * @param string $oldPropertyName
     * @return void
     */
    public function setFrom($oldPropertyName)
    {
        $this->oldPropertyName = $oldPropertyName;
    }

    /**
     * Sets the new name for the property to change.
     *
     * @param string $newPropertyName
     * @return void
     */
    public function setTo($newPropertyName)
    {
        $this->newPropertyName = $newPropertyName;
    }

    /**
     * Returns TRUE if the given node has a property with the name to work on
     * and does not yet have a property with the name to rename that property to.
     *
     * @param NodeData $node
     * @return boolean
     */
    public function isTransformable(NodeData $node)
    {
        return ($node->hasProperty($this->oldPropertyName) && !$node->hasProperty($this->newPropertyName));
    }

    /**
     * Renames the configured property to the new name.
     *
     * @param NodeData $node
     * @return void
     */
    public function execute(NodeData $node)
    {
        $node->setProperty($this->newPropertyName, $node->getProperty($this->oldPropertyName));
        $node->removeProperty($this->oldPropertyName);
    }
}
