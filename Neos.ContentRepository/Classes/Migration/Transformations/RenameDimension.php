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
 * Rename a dimension.
 */
class RenameDimension extends AbstractTransformation
{
    /**
     * @Flow\Inject
     * @var ContentDimensionRepository
     */
    protected $contentDimensionRepository;

    /**
     * The old name for the dimension.
     *
     * @var string
     */
    protected $oldDimensionName;

    /**
     * The new name for the dimension.
     *
     * @var string
     */
    protected $newDimensionName;

    /**
     * @param string $newDimensionName
     */
    public function setNewDimensionName($newDimensionName)
    {
        $this->newDimensionName = $newDimensionName;
    }

    /**
     * @return string
     */
    public function getNewDimensionName()
    {
        return $this->newDimensionName;
    }

    /**
     * @param string $oldDimensionName
     */
    public function setOldDimensionName($oldDimensionName)
    {
        $this->oldDimensionName = $oldDimensionName;
    }

    /**
     * @return string
     */
    public function getOldDimensionName()
    {
        return $this->oldDimensionName;
    }

    /**
     * Change the property on the given node.
     *
     * @param NodeData $nodeData
     * @return void
     */
    public function execute(NodeData $nodeData)
    {
        $dimensions = $nodeData->getDimensions();
        if ($dimensions !== []) {
            $hasChanges = false;
            $newDimensions  = [];
            foreach ($dimensions as $dimension) {
                /** @var NodeDimension $dimension */
                if ($dimension->getName() === $this->oldDimensionName) {
                    $dimension = new NodeDimension($dimension->getNodeData(), $this->newDimensionName, $dimension->getValue());
                    $hasChanges = true;
                } else {
                    $dimension = new NodeDimension($dimension->getNodeData(), $dimension->getName(), $dimension->getValue());
                }
                $newDimensions[] = $dimension;
            }
            if ($hasChanges) {
                $nodeData->setDimensions($newDimensions);
            }
        }
    }
}
