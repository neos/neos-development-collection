<?php
namespace TYPO3\TypoScript;

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

/**
 * Represents the invocation of a TypoScript processor
 *
 * @FLOW3\Scope("prototype")
 */
class ProcessorInvocation {

	/**
	 * An instance of the class providing the processor
	 * @var object
	 */
	protected $processorObject;

	/**
	 * Arguments to pass to the processor method
	 * @var array
	 */
	protected $processorArguments;

	/**
	 * Constructor of the processor invocation.
	 *
	 * @param \TYPO3\TypoScript\ProcessorInterface $processorObject An instance of the class containing the processor
	 * @param array $processorArguments associative array of Arguments to pass to the processor method
	 * @return void
	 * @throws \TYPO3\TypoScript\Exception\InvalidProcessorException
	 */
	public function __construct(\TYPO3\TypoScript\ProcessorInterface $processorObject, array $processorArguments) {
		if (!is_object($processorObject)) {
			throw new \TYPO3\TypoScript\Exception\InvalidProcessorException('The processor object is not an object!', 1179409471);
		}
		$this->processorObject = $processorObject;
		$this->processorArguments = $processorArguments;
	}

	/**
	 * Invokes the processor to process the given subject
	 *
	 * @param mixed $subject The subject (mostly a string) to process
	 * @return mixed The processed value
	 */
	public function process($subject) {
		foreach($this->processorArguments as $argumentName => $argumentValue) {
			\TYPO3\FLOW3\Reflection\ObjectAccess::setProperty($this->processorObject, $argumentName, $argumentValue);
		}
		return $this->processorObject->process($subject);
	}
}
?>