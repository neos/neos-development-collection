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
 * Remove the property
 */
class RemoveProperty extends AbstractTransformation
{
    /**
     * @var string
     */
    protected $propertyName;

    /**
     * Sets the name of the property to be removed.
     *
     * @param string $propertyName
     * @return void
     */
    public function setProperty($propertyName)
    {
        $this->propertyName = $propertyName;
    }

    /**
     * If the given node has property this transformation should work on, this
     * returns TRUE.
     *
     * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $node
     * @return boolean
     */
    public function isTransformable(\TYPO3\TYPO3CR\Domain\Model\NodeData $node)
    {
        return $node->hasProperty($this->propertyName);
    }

    /**
     * Remove the property from the given node.
     *
     * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $node
     * @return void
     */
    public function execute(\TYPO3\TYPO3CR\Domain\Model\NodeData $node)
    {
        $node->removeProperty($this->propertyName);
    }
}
