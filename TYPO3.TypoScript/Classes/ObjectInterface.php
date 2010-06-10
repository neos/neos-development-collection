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
 * Interface of a TypoScript object
 *
 * @version $Id$
 * @author Robert Lemke <robert@typo3.org>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
interface ObjectInterface {

	/**
	 * Sets the Domain Model the TypoScript object is based on.
	 *
	 * All accesible properties of that model can become properties of the TypoScript
	 * object as well. If they can be set via TypoScript depends on if a setter
	 * method exists in the respective TypoScript Object class.
	 *
	 * @param object $model The domain model the TypoScript object is based on
	 * @return void
	 * @throws \F3\TypoScript\Exception\InvalidModelException if the given model is not an instance of $this->modelType
	 */
	public function setModel($model);

	/**
	 * Returns the model the TypoScript object is based on
	 *
	 * @return object The domain model the TypoScript object is based on
	 */
	public function getModel();

	/**
	 * Sets the property processor chain for a specific property
	 *
	 * @param string $propertyName Name of the property to set the chain for
	 * @param \F3\TypoScript\ProcessorChain $propertyProcessorChain The property processor chain for that property
	 * @return void
	 */
	public function setPropertyProcessorChain($propertyName, \F3\TypoScript\ProcessorChain $propertyProcessorChain);

	/**
	 * Unsets the property processor chain for a specific property
	 *
	 * @param string $propertyName Name of the property to unset the chain for
	 * @return void
	 */
	public function unsetPropertyProcessorChain($propertyName);

	/**
	 * Returns the property processor chain for a specific property
	 *
	 * @param string $propertyName Name of the property to return the chain of
	 * @return \F3\TypoScript\ProcessorChain $propertyProcessorChain: The property processor chain of that property
	 * @throws \F3\TypoScript\Exception\NoProcessorChainFoundException
	 */
	public function getPropertyProcessorChain($propertyName);

	/**
	 * Tells if a processor chain for the given property exists
	 *
	 * @param string $propertyName Name of the property to check for
	 * @return boolean TRUE if a property chain exists, otherwise FALSE
	 */
	public function propertyHasProcessorChain($propertyName);

}
?>