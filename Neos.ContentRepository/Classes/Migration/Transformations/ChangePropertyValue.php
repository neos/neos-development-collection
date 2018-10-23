<?php
namespace Neos\ContentRepository\Migration\Transformations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeData;

/**
 * Change the value of a given property.
 *
 * This can apply two transformations:
 *
 * If newValue is set, the value will be set to this, with any occurrences of the currentValuePlaceholder replaced with
 * the current value of the property.
 *
 * If search and replace are given, that replacement will be done on the value (after applying the newValue if set).
 */
class ChangePropertyValue extends AbstractTransformation
{
    /**
     * @var string
     */
    protected $propertyName;

    /**
     * @var string
     */
    protected $newValue = '{current}';

    /**
     * @var string
     */
    protected $search = '';

    /**
     * @var string
     */
    protected $replace = '';

    /**
     * Placeholder for the current property value to be inserted in newValue.
     *
     * @var string
     */
    protected $currentValuePlaceholder = '{current}';

    /**
     * Sets the name of the property to change.
     *
     * @param string $propertyName
     * @return void
     */
    public function setProperty($propertyName)
    {
        $this->propertyName = $propertyName;
    }

    /**
     * New property value to be set.
     *
     * The value of the option "currentValuePlaceholder" (defaults to "{current}") will be
     * used to include the current property value into the new value.
     *
     * @param string $newValue
     * @return void
     */
    public function setNewValue($newValue)
    {
        $this->newValue = $newValue;
    }

    /**
     * Search string to replace in current property value.
     *
     * @param string $search
     * @return void
     */
    public function setSearch($search)
    {
        $this->search = $search;
    }

    /**
     * Replacement for the search string
     *
     * @param string $replace
     * @return void
     */
    public function setReplace($replace)
    {
        $this->replace = $replace;
    }

    /**
     * The value of this option (defaults to "{current}") will be used to include the
     * current property value into the new value.
     *
     * @param string $currentValuePlaceholder
     * @return void
     */
    public function setCurrentValuePlaceholder($currentValuePlaceholder)
    {
        $this->currentValuePlaceholder = $currentValuePlaceholder;
    }

    /**
     * If the given node has the property this transformation should work on, this
     * returns true.
     *
     * @param NodeData $node
     * @return boolean
     */
    public function isTransformable(NodeData $node)
    {
        return ($node->hasProperty($this->propertyName));
    }

    /**
     * Change the property on the given node.
     *
     * @param NodeData $node
     * @return void
     */
    public function execute(NodeData $node)
    {
        $currentPropertyValue = $node->getProperty($this->propertyName);
        $newValueWithReplacedCurrentValue = str_replace($this->currentValuePlaceholder, $currentPropertyValue, $this->newValue);
        $newValueWithReplacedSearch = str_replace($this->search, $this->replace, $newValueWithReplacedCurrentValue);
        $node->setProperty($this->propertyName, $newValueWithReplacedSearch);
    }
}
