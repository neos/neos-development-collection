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

/**
 * Interface of a TypoScript object
 *
 */
interface ObjectInterface {

	/**
	 * Sets the node the TypoScript object is based on.
	 *
	 * All properties of the node can become part of the TypoScript object
	 * as well. If they can be set via TypoScript depends on if a setter
	 * method exists in the respective TypoScript Object class.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node The node the TypoScript object is based on
	 * @return void
	 */
	public function setNode(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node);

	/**
	 * Returns the node the TypoScript object is based on
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The node the TypoScript object is based on
	 */
	public function getNode();

	/**
	 * Sets the property processor chain for a specific property
	 *
	 * @param string $propertyName Name of the property to set the chain for
	 * @param \TYPO3\TypoScript\ProcessorChain $propertyProcessorChain The property processor chain for that property
	 * @return void
	 */
	public function setPropertyProcessorChain($propertyName, \TYPO3\TypoScript\ProcessorChain $propertyProcessorChain);

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
	 * @return \TYPO3\TypoScript\ProcessorChain $propertyProcessorChain: The property processor chain of that property
	 * @throws \TYPO3\TypoScript\Exception\NoProcessorChainFoundException
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