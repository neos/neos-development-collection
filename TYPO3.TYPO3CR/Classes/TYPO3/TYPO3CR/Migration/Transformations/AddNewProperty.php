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

/**
 * Add the new property and its value
 */
class AddNewProperty extends AbstractTransformation
{
    /**
     * @var string
     */
    protected $newPropertyName;

    /**
     * @var string
     */
    protected $value;

    /**
     * Sets the name of the new property to be added.
     *
     * @param string $newPropertyName
     * @return void
     */
    public function setNewPropertyName($newPropertyName)
    {
        $this->newPropertyName = $newPropertyName;
    }

    /**
     * Property value to be set.
     *
     * @param string $value
     * @return void
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * If the given node has no property this transformation should work on, this
     * returns TRUE.
     *
     * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $node
     * @return boolean
     */
    public function isTransformable(\TYPO3\TYPO3CR\Domain\Model\NodeData $node)
    {
        return !$node->hasProperty($this->newPropertyName);
    }

    /**
     * Add the new property with the given value on the given node.
     *
     * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $node
     * @return void
     */
    public function execute(\TYPO3\TYPO3CR\Domain\Model\NodeData $node)
    {
        $node->setProperty($this->newPropertyName, $this->value);
    }
}
