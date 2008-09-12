<?php
declare(ENCODING = 'utf-8');
namespace F3::TypoScript;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * Represents the invocation of a TypoScript processor
 * 
 * @package		TypoScript
 * @version 	$Id$
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 *
 * @scope prototype
 */
class ProcessorInvocation {
	
	/**
	 * @var object An instance of the class providing the processor
	 */
	protected $processorObject;
	
	/**
	 * @var string Name of the processor method
	 */
	protected $processorMethodName;

	/**
	 * @var array Arguments to pass to the processor method
	 */
	protected $processorArguments;
	
	/**
	 * Constructor of the processor invocation.
	 *
	 * @param  object						$processorObject: An instance of the class containing the processor
	 * @param  string						$processorMethodName: Name of the processor method
	 * @param  array						$processorArguments: Arguments to pass to the processor method
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws RuntimeException
	 */
	public function __construct($processorObject, $processorMethodName, array $processorArguments) {
		if (is_object($processorObject) && is_string($processorMethodName) && method_exists($processorObject, $processorMethodName)) {
			$this->processorObject = $processorObject;
			$this->processorMethodName = $processorMethodName;
			$this->processorArguments = $processorArguments;
		} else {
			throw new RuntimeException('The processor object is not an object or the specified processor method does not exist!', 1179409471);
		}
	}
	
	/**
	 * Invokes the processor to process the given subject
	 * 
	 * @param  string						$subject: The string to process
	 * @return string						The processed string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function process($subject) {
		$arguments = $this->processorArguments;
		array_unshift($arguments, $subject);
		return call_user_func_array(array($this->processorObject, $this->processorMethodName), $arguments);
	}
}
?>