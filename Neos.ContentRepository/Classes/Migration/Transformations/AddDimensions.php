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

use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeDimension;

/**
 * Add dimensions on a node. This adds to the existing dimensions, if you need to
 * overwrite existing dimensions, @see SetDimensions
 */
class AddDimensions extends AbstractTransformation
{
    /**
     * @Flow\Inject
     * @var Dimension\ContentDimensionSourceInterface
     */
    protected $contentDimensionSource;

    /**
     * If you omit a configured dimension this transformation will add the default value for that dimension.
     *
     * @var array
     */
    protected $dimensionValues = array();

    /**
     * Adds the default dimension values for all dimensions that were not given.
     *
     * @var boolean
     */
    protected $addDefaultDimensionValues = true;

    /**
     * @param array $dimensionValues
     */
    public function setDimensionValues($dimensionValues)
    {
        $this->dimensionValues = $dimensionValues;
    }

    /**
     * @param boolean $addDefaultDimensionValues
     */
    public function setAddDefaultDimensionValues($addDefaultDimensionValues)
    {
        $this->addDefaultDimensionValues = $addDefaultDimensionValues;
    }

    /**
     * Add dimensions to the node.
     *
     * @param NodeData $node
     * @return void
     */
    public function execute(NodeData $node)
    {
        $dimensionValuesToBeAdded = $node->getDimensionValues();

        foreach ($this->dimensionValues as $dimensionName => $dimensionValues) {
            if (!isset($dimensionValuesToBeAdded[$dimensionName])) {
                if (is_array($dimensionValues)) {
                    $dimensionValuesToBeAdded[$dimensionName] = $dimensionValues;
                } else {
                    $dimensionValuesToBeAdded[$dimensionName] = array($dimensionValues);
                }
            }
        }

        if ($this->addDefaultDimensionValues === true) {
            $configuredDimensions = $this->contentDimensionSource->getContentDimensionsOrderedByPriority();
            foreach ($configuredDimensions as $configuredDimension) {
                if (!isset($dimensionValuesToBeAdded[(string)$configuredDimension->getIdentifier()])) {
                    $dimensionValuesToBeAdded[(string)$configuredDimension->getIdentifier()] = [(string)$configuredDimension->getDefaultValue()];
                }
            }
        }

        $dimensionsToBeSet = array();
        foreach ($dimensionValuesToBeAdded as $dimensionName => $dimensionValues) {
            foreach ($dimensionValues as $dimensionValue) {
                $dimensionsToBeSet[] = new NodeDimension($node, $dimensionName, $dimensionValue);
            }
        }

        $node->setDimensions($dimensionsToBeSet);
    }
}
