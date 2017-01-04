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

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Exception as TypoScriptException;
use Neos\Fusion\Exception;

/**
 * Abstract implementation of a collection renderer for Fusion.
 */
abstract class AbstractCollectionImplementation extends AbstractFusionObject
{
    /**
     * The number of rendered nodes, filled only after evaluate() was called.
     *
     * @var integer
     */
    protected $numberOfRenderedNodes;

    /**
     * Render the array collection by triggering the itemRenderer for every element
     *
     * @return array
     */
    public function getCollection()
    {
        return $this->tsValue('collection');
    }

    /**
     * @return string
     */
    public function getItemName()
    {
        return $this->tsValue('itemName');
    }

    /**
     * @return string
     */
    public function getItemKey()
    {
        return $this->tsValue('itemKey');
    }

    /**
     * If set iteration data (index, cycle, isFirst, isLast) is available in context with the name given.
     *
     * @return string
     */
    public function getIterationName()
    {
        return $this->tsValue('iterationName');
    }

    /**
     * Evaluate the collection nodes as concatenated string
     *
     * @return string
     * @throws FusionException
     */
    public function evaluate()
    {
        return implode('', $this->evaluateAsArray());
    }

    /**
     * Evaluate the collection nodes as array
     *
     * @return array
     * @throws FusionException
     */
    public function evaluateAsArray()
    {
        $collection = $this->getCollection();

        $result = [];
        if ($collection === null) {
            return $result;
        }
        $this->numberOfRenderedNodes = 0;
        $itemName = $this->getItemName();
        if ($itemName === null) {
            throw new Exception('The Collection needs an itemName to be set.', 1344325771);
        }
        $itemKey = $this->getItemKey();
        $iterationName = $this->getIterationName();
        $collectionTotalCount = count($collection);
        foreach ($collection as $collectionKey => $collectionElement) {
            $context = $this->tsRuntime->getCurrentContext();
            $context[$itemName] = $collectionElement;
            if ($itemKey !== null) {
                $context[$itemKey] = $collectionKey;
            }
            if ($iterationName !== null) {
                $context[$iterationName] = $this->prepareIterationInformation($collectionTotalCount);
            }

            $this->tsRuntime->pushContextArray($context);
            $result[] =  $this->tsRuntime->render($this->path . '/itemRenderer');
            $this->tsRuntime->popContext();
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
        $iteration = array(
            'index' => $this->numberOfRenderedNodes,
            'cycle' => ($this->numberOfRenderedNodes + 1),
            'isFirst' => false,
            'isLast' => false,
            'isEven' => false,
            'isOdd' => false
        );

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
