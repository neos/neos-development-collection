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
 * Common class for TypoScript objects
 *
 */
abstract class AbstractObject implements \TYPO3\TypoScript\ObjectInterface {

	/**
	 * The node the TypoScript Object is based on
	 *
	 * @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	protected $node;

	/**
	 * Content type of the node this TS Object is based on. Must be defined by the concrete implementation.
	 *
	 * @var string
	 */
	protected $contentType;

	/**
	 * @var array<\TYPO3\TypoScript\ProcessorChain>
	 */
	protected $propertyProcessorChains = array();

	/**
	 * @inject
	 * @var \TYPO3\TypoScript\ObjectFactory
	 */
	protected $typoScriptObjectFactory;

	/**
	 * Sets the node the TypoScript object is based on.
	 *
	 * All properties of the node can become part of the TypoScript object
	 * as well. If they can be set via TypoScript depends on if a setter
	 * method exists in the respective TypoScript Object class.
	 *
	 * The default "Node" TypoScript object will expose all available properties.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node The node the TypoScript object is based on
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Implement check for content type
	 */
	public function setNode(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$this->node = $node;
	}

	/**
	 * Returns the node the TypoScript object is based on
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The node the TypoScript object is based on
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNode() {
		return $this->node;
	}

	/**
	 * Sets the property processor chain for a specific property
	 *
	 * @param string $propertyName Name of the property to set the chain for
	 * @param \TYPO3\TypoScript\ProcessorChain $propertyProcessorChain The property processor chain for that property
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setPropertyProcessorChain($propertyName, \TYPO3\TypoScript\ProcessorChain $propertyProcessorChain) {
		$this->propertyProcessorChains[$propertyName] = $propertyProcessorChain;
	}

	/**
	 * Unsets the property processor chain for a specific property
	 *
	 * @param string $propertyName Name of the property to unset the chain for
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function unsetPropertyProcessorChain($propertyName) {
		unset($this->propertyProcessorChains[$propertyName]);
	}

	/**
	 * Returns the property processor chain for a specific property
	 *
	 * @param string $propertyName Name of the property to return the chain of
	 * @return \TYPO3\TypoScript\ProcessorChain $propertyProcessorChain: The property processor chain of that property
	 * @throws \TYPO3\TypoScript\Exception\NoProcessorChainFoundException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertyProcessorChain($propertyName) {
		if (!isset($this->propertyProcessorChains[$propertyName])) throw new \TYPO3\TypoScript\Exception\NoProcessorChainFoundException('Tried to retrieve the property processor chain for property "' . $propertyName . '" but no processor chain exists for that property.', 1179407935);
		return $this->propertyProcessorChains[$propertyName];
	}

	/**
	 * Tells if a processor chain for the given property exists
	 *
	 * @param string $propertyName Name of the property to check for
	 * @return boolean TRUE if a property chain exists, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function propertyHasProcessorChain($propertyName) {
		return isset($this->propertyProcessorChains[$propertyName]);
	}

	/**
	 * Returns a closure which on invoke runs the processor chain for the specified
	 * property and returns the result value.
	 *
	 * If the getter in the TypoScript object returns a non-NULL value that value
	 * - coming from the TS sourcecode - is used (overriding the value in the model).
	 *
	 * @param string $propertyName Name of the property to process
	 * @result mixed A proxy which can process the specified property or the actual value if no processors exist
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getPropertyProcessingProxy($propertyName) {
		$getterMethodName = 'get' . ucfirst($propertyName);
		if (!is_callable(array($this, $getterMethodName))) {
			throw new \InvalidArgumentException('Tried to create a processing proxy for non-existing getter ' . get_class($this) . '->' . $getterMethodName . '().', 1179406581);
		}

		$propertyValue = $this->$getterMethodName();
		if ($propertyValue === NULL && $this->node !== NULL) {
			if ($this->node->hasProperty($propertyName)) {
				$propertyValue = $this->node->getProperty($propertyName);
			}
		}

		$processorChains = isset($this->propertyProcessorChains[$propertyName]) ? $this->propertyProcessorChains[$propertyName] : NULL;
		return ($processorChains === NULL) ? $propertyValue : new \TYPO3\TypoScript\PropertyProcessingProxy($propertyValue, $processorChains);
	}
}
?>