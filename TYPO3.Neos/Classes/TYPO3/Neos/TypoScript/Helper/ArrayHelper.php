<?php
namespace TYPO3\Neos\TypoScript\Helper;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\Common\Collections\Collection;
use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Reflection\ObjectAccess;

/**
 * Some Functional Programming Array helpers for Eel contexts
 *
 * These helpers are *WORK IN PROGRESS* and *NOT STABLE YET*
 *
 * @Flow\Proxy(false)
 */
class ArrayHelper implements ProtectedContextAwareInterface {

	/**
	 * Filter an array of objects, by only keeping the elements where each object's $filterProperty evaluates to TRUE.
	 *
	 * @param array|Collection $set
	 * @param string $filterProperty
	 * @return array
	 */
	public function filter($set, $filterProperty) {
		return $this->filterInternal($set, $filterProperty, FALSE);
	}

	/**
	 * Filter an array of objects, by only keeping the elements where each object's $filterProperty evaluates to FALSE.
	 *
	 * @param array|Collection $set
	 * @param string $filterProperty
	 * @return array
	 */
	public function filterNegated($set, $filterProperty) {
		return $this->filterInternal($set, $filterProperty, TRUE);
	}

	/**
	 * Internal method for filtering
	 *
	 * @param array|Collection $set
	 * @param string $filterProperty
	 * @param boolean $negate
	 * @return array
	 */
	protected function filterInternal($set, $filterProperty, $negate) {
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
	 * The input is assumed to be an array or Collection of objects. Groups this input by the $groupingKey property of each element.
	 *
	 * @param array|Collection $set
	 * @param string $groupingKey
	 * @return array
	 */
	public function groupBy($set, $groupingKey) {
		$result = array();
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
	public function allowsCallOfMethod($methodName) {
		return TRUE;
	}
}