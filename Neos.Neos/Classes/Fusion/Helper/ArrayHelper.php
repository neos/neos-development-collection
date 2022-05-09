<?php
namespace Neos\Neos\Fusion\Helper;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Collections\Collection;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\ObjectAccess;

/**
 * Some Functional Programming Array helpers for Eel contexts
 *
 * These helpers are *WORK IN PROGRESS* and *NOT STABLE YET*
 *
 * @Flow\Proxy(false)
 */
class ArrayHelper implements ProtectedContextAwareInterface
{
    /**
     * Filter an array of objects, by only keeping the elements where each object's $filterProperty evaluates to true.
     *
     * @param array<mixed>|Collection<int|string,mixed> $set
     * @param string $filterProperty
     * @return array<mixed>
     */
    public function filter($set, $filterProperty)
    {
        return $this->filterInternal($set, $filterProperty, false);
    }

    /**
     * Filter an array of objects, by only keeping the elements where each object's $filterProperty evaluates to false.
     *
     * @param array<mixed>|Collection<int|string,mixed> $set
     * @param string $filterProperty
     * @return array<mixed>
     */
    public function filterNegated($set, $filterProperty)
    {
        return $this->filterInternal($set, $filterProperty, true);
    }

    /**
     * Internal method for filtering
     *
     * @param array<mixed>|Collection<int|string,mixed> $set
     * @param string $filterProperty
     * @param boolean $negate
     * @return array<mixed>
     */
    protected function filterInternal($set, $filterProperty, $negate)
    {
        if (is_object($set) && $set instanceof Collection) {
            $set = $set->toArray();
        }

        return array_filter($set, function ($element) use ($filterProperty, $negate) {
            $result = (boolean)ObjectAccess::getPropertyPath($element, $filterProperty);
            if ($negate) {
                $result = !$result;
            }

            return $result;
        });
    }

    /**
     * The input is assumed to be an array or Collection of objects.
     * Groups this input by the $groupingKey property of each element.
     *
     * @param array<mixed>|Collection<int|string,mixed> $set
     * @param string $groupingKey
     * @return array<mixed>
     */
    public function groupBy($set, $groupingKey)
    {
        $result = [];
        foreach ($set as $element) {
            $result[ObjectAccess::getPropertyPath($element, $groupingKey)][] = $element;
        }

        return $result;
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
