<?php
namespace TYPO3\TYPO3CR\Migration\Filters;

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
use TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository;

/**
 * Filter nodes by their dimensions.
 */
class DimensionValues implements FilterInterface
{
    /**
     * @Flow\Inject
     * @var ContentDimensionRepository
     */
    protected $contentDimensionRepository;

    /**
     * The array of dimension values to filter for.
     *
     * @var array
     */
    protected $dimensionValues = array();

    /**
     * Overrides the given dimensionValues with dimension defaults.
     *
     * @var boolean
     */
    protected $filterForDefaultDimensionValues = false;

    /**
     * @param array $dimensionValues
     */
    public function setDimensionValues($dimensionValues)
    {
        $this->dimensionValues = $dimensionValues;
    }

    /**
     * @param boolean $filterForDefaultDimensionValues
     */
    public function setFilterForDefaultDimensionValues($filterForDefaultDimensionValues)
    {
        $this->filterForDefaultDimensionValues = $filterForDefaultDimensionValues;
    }

    /**
     * Returns TRUE if the given node has the default dimension values.
     *
     * @param NodeData $node
     * @return boolean
     */
    public function matches(NodeData $node)
    {
        if ($this->filterForDefaultDimensionValues === true) {
            $configuredDimensions = $this->contentDimensionRepository->findAll();
            foreach ($configuredDimensions as $dimension) {
                $this->dimensionValues[$dimension->getIdentifier()] = array($dimension->getDefault());
            }
        }

        return ($node->getDimensionValues() === $this->dimensionValues);
    }
}
