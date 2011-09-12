<?php
namespace TYPO3\TypoScript;

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
 * The implementation of a processor chain
 *
 * @scope prototype
 */
class ProcessorChain {

	/**
	 * @var array
	 */
	protected $processorInvocations = array();

	/**
	 * Sets the processor invocation with a specified index representing the order
	 * in the processor chain.
	 *
	 * @param integer $index A numeric index expressing the order of the processor in the overall chain
	 * @param \TYPO3\TypoScript\ProcessorInvocation $processorInvocation The processor invocation
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \InvalidArgumentException
	 */
	public function setProcessorInvocation($index, \TYPO3\TypoScript\ProcessorInvocation $processorInvocation) {
		if (!is_integer($index)) throw new \InvalidArgumentException('Index must be of type integer, ' . gettype($index) . ' given.', 1179416592);
		$this->processorInvocations[$index] = $processorInvocation;
		ksort($this->processorInvocations);
	}

	/**
	 * Runs the through the processor chain to process the specified string.
	 *
	 * @param string $subject The string to process by the processor chain
	 * @return string The result of the processing
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function process($subject) {
		foreach ($this->processorInvocations as $processorInvocation) {
			$subject = $processorInvocation->process($subject);
		}
		return $subject;
	}
}
?>