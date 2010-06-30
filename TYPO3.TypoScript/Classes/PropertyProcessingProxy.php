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
 * A proxy class which is used for lazy rendering TypoScript object properties.
 *
 * @version $Id: AbstractObject.php 4271 2010-05-05 15:38:09Z robert $
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PropertyProcessingProxy implements \F3\Fluid\Core\Parser\SyntaxTree\RenderingContextAwareInterface {

	/**
	 * @var mixed
	 */
	protected $propertyValue;

	/**
	 * @var \F3\TypoScript\ProcessorChain
	 */
	protected $processorChains;

	/**
	 * Constructs this proxy
	 *
	 * @param mixed $propertyValue
	 * @param \F3\TypoScript\ProcessorChain $processorChain
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($propertyValue, \F3\TypoScript\ProcessorChain $processorChain) {
		$this->propertyValue = $propertyValue;
		$this->processorChains = $processorChain;
	}

	/**
	 * Injects the current rendering context.
	 *
	 * @param \F3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setRenderingContext(\F3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
		$this->renderingContext = $renderingContext;
	}

	/**
	 * Converts this proxy into a string
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __toString() {
		try {
			if ($this->propertyValue instanceof \F3\TypoScript\ContentObjectInterface) {
				$this->propertyValue = $this->propertyValue->render($this->renderingContext);
			}
			return ($this->processorChains === NULL) ? $this->propertyValue : $this->processorChains->process($this->propertyValue);
		} catch (\Exception $exception) {
			return $exception->getMessage();
		}
	}
}
?>