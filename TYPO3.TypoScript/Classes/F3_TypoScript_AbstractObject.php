<?php
declare(ENCODING = 'utf-8');

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
 * Common class for TypoScript objects
 * 
 * @package		TypoScript
 * @version 	$Id$
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
abstract class F3_TypoScript_AbstractObject implements F3_TypoScript_ObjectInterface {
	
	/**
	 * @var array An array of F3_TypoScript_ProcessorChain objects
	 */
	protected $propertyProcessorChains = array();

	/**
	 * Sets the property processor chain for a specific property
	 *
	 * @param  string				$propertyName: Name of the property to set the chain for
	 * @param  F3_TypoScript_ProcessorChain $propertyProcessorChain: The property processor chain for that property
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setPropertyProcessorChain($propertyName, F3_TypoScript_ProcessorChain $propertyProcessorChain) {
		$this->propertyProcessorChains[$propertyName] = $propertyProcessorChain;
	}
	
	/**
	 * Unsets the property processor chain for a specific property
	 *
	 * @param  string				$propertyName: Name of the property to unset the chain for
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws LogicException
	 */
	public function unsetPropertyProcessorChain($propertyName) {
		if (!isset($this->propertyProcessorChains[$propertyName])) throw new LogicException('Tried to unset the property processor chain for property "' . $propertyName . '" but no processor chain exists for that property.', 1179407939);
		unset($this->propertyProcessorChains[$propertyName]);
	}
	
	/**
	 * Returns the property processor chain for a specific property
	 *
	 * @param  string				$propertyName: Name of the property to return the chain of
	 * @return F3_TypoScript_ProcessorChain $propertyProcessorChain: The property processor chain of that property
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws LogicException
	 */
	public function getPropertyProcessorChain($propertyName) {
		if (!isset($this->propertyProcessorChains[$propertyName])) throw new LogicException('Tried to retrieve the property processor chain for property "' . $propertyName . '" but no processor chain exists for that property.', 1179407935);
		return $this->propertyProcessorChains[$propertyName];
	}
	
	/**
	 * Tells if a processor chain for the given property exists
	 *
	 * @param  string				$propertyName: Name of the property to check for
	 * @return boolean				TRUE if a property chain exists, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function propertyHasProcessorChain($propertyName) {
		return isset($this->propertyProcessorChains[$propertyName]);
	}
	
	/**
	 * Runs the processors chain for the specified property and returns the result value.
	 *
	 * @param  string				$propertyName: Name of the property to process
	 * @result string				The processed value of the property
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws LogicException
	 */
	protected function getProcessedProperty($propertyName) {
		if (!property_exists($this, $propertyName)) throw new LogicException('Tried to run the processors chain for non-existing property "' . $propertyName . '".', 1179406581);
		if (!isset($this->propertyProcessorChains[$propertyName])) return $this->$propertyName;
		return $this->propertyProcessorChains[$propertyName]->process($this->$propertyName);
	}
}
?>