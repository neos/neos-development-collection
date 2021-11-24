<?php
namespace Neos\Fusion\FusionObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Fusion\Exception as FusionException;

/**
 * Map a collection of items using the itemRenderer
 *
 * //fusionPath items *Collection
 * //fusionPath itemRenderer the Fusion object which is triggered for each item
 * //fusionPath keyRenderer the Fusion object which is triggered for each item to render the key in the result collection
 */
class MapImplementation extends AbstractFusionObject
{
    /**
     * The number of rendered nodes, filled only after evaluate() was called.
     *
     * @var integer
     */
    protected $numberOfRenderedNodes;

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->fusionValue('items');
    }

    /**
     * @return string
     */
    public function getItemName()
    {
        return $this->fusionValue('itemName');
    }

    /**
     * @return string
     */
    public function getItemKey()
    {
        return $this->fusionValue('itemKey');
    }

    /**
     * If set iteration data (index, cycle, isFirst, isLast) is available in context with the name given.
     *
     * @return string
     */
    public function getIterationName()
    {
        return $this->fusionValue('iterationName');
    }

    /**
     * Evaluate the collection nodes as array
     *
     * @return array
     * @throws FusionException
     */
    public function evaluate()
    {
        $collection = $this->getItems();

        $result = [];
        if ($collection === null) {
            return $result;
        }
        $this->numberOfRenderedNodes = 0;
        $itemName = $this->getItemName();
        if ($itemName === null) {
            throw new FusionException('The Collection needs an itemName to be set.', 1344325771);
        }
        $itemKey = $this->getItemKey();
        $iterationName = $this->getIterationName();
        $collectionTotalCount = count($collection);

        $keyRenderPath = $this->path . '/keyRenderer';
        $keyRendererIsAvailable = $this->runtime->canRender($keyRenderPath);

        $itemRenderPath = $this->path . '/itemRenderer';
        $fallbackRenderPath = $this->path . '/content';

        if ($this->runtime->canRender($itemRenderPath) === false && $this->runtime->canRender($fallbackRenderPath)) {
            $itemRenderPath = $fallbackRenderPath;
        }


        foreach ($collection as $collectionKey => $collectionElement) {
            $context = $this->runtime->getCurrentContext();
            $context[$itemName] = $collectionElement;

            if ($itemKey !== null) {
                $context[$itemKey] = $collectionKey;
            }

            if ($iterationName !== null) {
                $context[$iterationName] = $this->prepareIterationInformation($collectionTotalCount);
            }

            $this->runtime->pushContextArray($context);

            if ($keyRendererIsAvailable) {
                $collectionKey = $this->runtime->render($keyRenderPath);
            }
            $result[$collectionKey] = $this->runtime->render($itemRenderPath);

            $this->runtime->popContext();
            $this->numberOfRenderedNodes++;
        }

        return $result;
    }

    /**
     * @param integer $collectionCount
     * @return array
     */
    protected function prepareIterationInformation($collectionCount)
    {
        $iteration = [
            'index' => $this->numberOfRenderedNodes,
            'cycle' => ($this->numberOfRenderedNodes + 1),
            'isFirst' => false,
            'isLast' => false,
            'isEven' => false,
            'isOdd' => false
        ];

        if ($this->numberOfRenderedNodes === 0) {
            $iteration['isFirst'] = true;
        }
        if (($this->numberOfRenderedNodes + 1) === $collectionCount) {
            $iteration['isLast'] = true;
        }
        if (($this->numberOfRenderedNodes + 1) % 2 === 0) {
            $iteration['isEven'] = true;
        } else {
            $iteration['isOdd'] = true;
        }

        return $iteration;
    }
}
