<?php
namespace TYPO3\TypoScript\Core;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

use TYPO3\FLOW3\Reflection\ObjectAccess;

/**
 * Evaluate the processors for a given property
 */
class ProcessorEvaluator {

	/**
	 * Evaluate all processors
	 *
	 * @param array $processorConfiguration
	 * @param string $propertyName
	 * @param mixed $value
	 * @return mixed
	 */
	public function evaluateProcessor($processorConfiguration, $propertyName, $value) {
		if (!isset($processorConfiguration[$propertyName])) {
			return $value;
		}
		ksort($processorConfiguration[$propertyName]);
		foreach ($processorConfiguration[$propertyName] as $singleProcessorConfiguration) {
			$processorClassName = $singleProcessorConfiguration['__processorClassName'];
			$processor = new $processorClassName();
			unset ($singleProcessorConfiguration['__processorClassName']);

			foreach ($singleProcessorConfiguration as $propertyName => $propertyValue) {
				if (!ObjectAccess::setProperty($processor, $propertyName, $propertyValue)) {
					throw new \TYPO3\TypoScript\Exception(sprintf('Property "%s" could not be set on processor "%s".', $propertyName, $processorClassName), 1332493740);
				}
			}

			$value = $processor->process($value);
		}
		return $value;
	}
}
?>