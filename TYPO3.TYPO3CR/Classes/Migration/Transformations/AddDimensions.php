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
use TYPO3\TYPO3CR\Domain\Model\NodeDimension;
use TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository;

/**
 * Add dimensions on a node. This adds to the existing dimensions, if you need to
 * overwrite existing dimensions, @see SetDimensions
 */
class AddDimensions extends AbstractTransformation
{
    /**
     * @Flow\Inject
     * @var ContentDimensionRepository
     */
    protected $contentDimensionRepository;

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
            $configuredDimensions = $this->contentDimensionRepository->findAll();
            foreach ($configuredDimensions as $configuredDimension) {
                if (!isset($dimensionValuesToBeAdded[$configuredDimension->getIdentifier()])) {
                    $dimensionValuesToBeAdded[$configuredDimension->getIdentifier()] = array($configuredDimension->getDefault());
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
