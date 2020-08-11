<?php
namespace Neos\ContentRepository\Migration\Filters;

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
use Neos\ContentRepository\Domain\Repository\ContentDimensionRepository;

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
    protected $dimensionValues = [];

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
     * Returns true if the given node has the default dimension values.
     *
     * @param NodeData $node
     * @return boolean
     */
    public function matches(NodeData $node)
    {
        if ($this->filterForDefaultDimensionValues === true) {
            $configuredDimensions = $this->contentDimensionRepository->findAll();
            foreach ($configuredDimensions as $dimension) {
                $this->dimensionValues[$dimension->getIdentifier()] = [$dimension->getDefault()];
            }
        }

        return ($node->getDimensionValues() === $this->dimensionValues);
    }
}
