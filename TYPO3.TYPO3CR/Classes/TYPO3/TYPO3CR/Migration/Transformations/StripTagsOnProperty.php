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
 * Strip all tags on a given property
 */
class StripTagsOnProperty extends AbstractTransformation
{
    /**
     * Property name to change
     *
     * @var string
     */
    protected $propertyName;

    /**
     * Sets the name of the property to work on.
     *
     * @param string $propertyName
     * @return void
     */
    public function setProperty($propertyName)
    {
        $this->propertyName = $propertyName;
    }

    /**
     * Returns TRUE if the given node has the property to work on.
     *
     * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $node
     * @return boolean
     */
    public function isTransformable(\TYPO3\TYPO3CR\Domain\Model\NodeData $node)
    {
        return ($node->hasProperty($this->propertyName));
    }

    /**
     * Strips tags on the value of the property to work on.
     *
     * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $node
     * @return void
     */
    public function execute(\TYPO3\TYPO3CR\Domain\Model\NodeData $node)
    {
        $node->setProperty($this->propertyName, strip_tags($node->getProperty($this->propertyName)));
    }
}
