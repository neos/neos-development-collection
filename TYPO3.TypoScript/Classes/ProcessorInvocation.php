<?php
declare(ENCODING = 'utf-8');
namespace F3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Represents the invocation of a TypoScript processor
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
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
	 * @param \F3\TypoScript\ProcessorInterface $processorObject An instance of the class containing the processor
	 * @param array $processorArguments associative array of Arguments to pass to the processor method
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @throws \F3\TypoScript\Exception\InvalidProcessorException
	 */
	public function __construct(\F3\TypoScript\ProcessorInterface $processorObject, array $processorArguments) {
		if (!is_object($processorObject)) {
			throw new \F3\TypoScript\Exception\InvalidProcessorException('The processor object is not an object!', 1179409471);
		}
		$this->processorObject = $processorObject;
		$this->processorArguments = $processorArguments;
	}

	/**
	 * Invokes the processor to process the given subject
	 *
	 * @param mixed $subject The subject (mostly a string) to process
	 * @return mixed The processed value
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function process($subject) {
		foreach($this->processorArguments as $argumentName => $argumentValue) {
			\F3\FLOW3\Reflection\ObjectAccess::setProperty($this->processorObject, $argumentName, $argumentValue);
		}
		return $this->processorObject->process($subject);
	}
}
?>