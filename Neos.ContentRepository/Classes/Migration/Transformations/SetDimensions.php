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

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeDimension;
use Neos\ContentRepository\Domain\Repository\ContentDimensionRepository;

/**
 * Set dimensions on a node. This always overwrites existing dimensions, if you need to
 * add to existing dimensions, @see AddDimensions
 */
class SetDimensions extends AbstractTransformation
{
    /**
     * @Flow\Inject
     * @var ContentDimensionRepository
     */
    protected $contentDimensionRepository;

    /**
     * If you omit a configured dimension this transformation will set the default value for that dimension.
     *
     * @var array
     */
    protected $dimensionValues = [];

    /**
     * Sets the default dimension values for all dimensions that were not given.
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
     * Change the property on the given node.
     *
     * @param NodeData $node
     * @return void
     */
    public function execute(NodeData $node)
    {
        $dimensions = [];
        foreach ($this->dimensionValues as $dimensionName => $dimensionConfiguration) {
            foreach ($dimensionConfiguration as $dimensionValues) {
                if (is_array($dimensionValues)) {
                    foreach ($dimensionValues as $dimensionValue) {
                        $dimensions[] = new NodeDimension($node, $dimensionName, $dimensionValue);
                    }
                } else {
                    $dimensions[] = new NodeDimension($node, $dimensionName, $dimensionValues);
                }
            }
        }

        if ($this->addDefaultDimensionValues === true) {
            $configuredDimensions = $this->contentDimensionRepository->findAll();
            foreach ($configuredDimensions as $configuredDimension) {
                if (!isset($this->dimensionValues[$configuredDimension->getIdentifier()])) {
                    $dimensions[] = new NodeDimension($node, $configuredDimension->getIdentifier(), $configuredDimension->getDefault());
                }
            }
        }

        $node->setDimensions($dimensions);
    }
}
