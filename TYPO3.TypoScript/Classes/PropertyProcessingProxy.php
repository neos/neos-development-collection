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
 * A proxy class which is used for lazy rendering TypoScript object properties.
 *
 */
class PropertyProcessingProxy implements \TYPO3\Fluid\Core\Parser\SyntaxTree\RenderingContextAwareInterface {

	/**
	 * @var mixed
	 */
	protected $propertyValue;

	/**
	 * @var \TYPO3\TypoScript\ProcessorChain
	 */
	protected $processorChains;

	/**
	 * Constructs this proxy
	 *
	 * @param mixed $propertyValue
	 * @param \TYPO3\TypoScript\ProcessorChain $processorChain
	 */
	public function __construct($propertyValue, \TYPO3\TypoScript\ProcessorChain $processorChain) {
		$this->propertyValue = $propertyValue;
		$this->processorChains = $processorChain;
	}

	/**
	 * Injects the current rendering context.
	 *
	 * @param \TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
	 * @return void
	 */
	public function setRenderingContext(\TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
		$this->renderingContext = $renderingContext;
	}

	/**
	 * Converts this proxy into a string
	 *
	 * @return string
	 */
	public function __toString() {
		try {
			if ($this->propertyValue instanceof \TYPO3\TypoScript\ContentObjectInterface) {
				$this->propertyValue = $this->propertyValue->render($this->renderingContext);
			}
			return ($this->processorChains === NULL) ? $this->propertyValue : $this->processorChains->process($this->propertyValue);
		} catch (\Exception $exception) {
			return $exception->getMessage();
		}
	}
}
?>