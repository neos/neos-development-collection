<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
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
 * A TypoScript Template object
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class Template extends \F3\Fluid\View\AbstractTemplateView implements \F3\TypoScript\ObjectInterface {

	/**
	 * @var array<\F3\TypoScript\ProcessorChain>
	 */
	protected $propertyProcessorChains = array();

	/**
	 * @inject
	 * @var \F3\TypoScript\ObjectFactory
	 */
	protected $typoScriptObjectFactory;

	/**
	 * @var mixed
	 */
	protected $source;

	/**
	 * The rendering context as passed to render()
	 *
	 * @transient
	 * @var \F3\TypoScript\RenderingContext
	 */
	protected $renderingContext;

	/**
	 * @var \F3\Fluid\Core\Parser\Interceptor\Resource
	 */
	protected $resourceInterceptor;

	/**
	 * Dummy method
	 *
	 * @param object $model Not used
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setModel($model) {
	}

	/**
	 * Dummy method
	 *
	 * @return NULL
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getModel() {
	}

	/**
	 * Sets the Fluid template source.
	 *
	 * Valid sources are:
	 *
	 *  - plain string containing the actual template
	 *  - TypoScript Content Object which can be rendered into a template
	 *  - a resource:// reference
	 *
	 *
	 * @param mixed $source The Fluid template source
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setSource($source) {
		$this->source = $source;
	}

	/**
	 * Returns the Fluid template source.
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSource() {
		return $this->source;
	}

	/**
	 * Returns the (usually HTML) template source of this Template object.
	 * Basically transforms the configured source pointer into real source code.
	 *
	 * @param string $actionName Not used in this implementation
	 * @return string The template source
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function  getTemplateSource($actionName) {
		if ($this->source instanceof \F3\TypoScript\ContentObjectInterface) {
			$this->source->setRenderingContext($this->renderingContext);
			return $this->source->render();
		} elseif (is_string($this->source)) {
			if (substr($this->source, 0, 11) === 'resource://') {
				if (file_exists($this->source)) {
					$uriParts = parse_url($this->source);
					$this->resourceInterceptor->setDefaultPackageKey($uriParts['host']);
					return file_get_contents($this->source);
				} else {
					return 'WARNING: Could not open template source "' . $this->source . '".';
				}
			} else {
				return $this->source;
			}
		}
		return 'WARNING: Invalid template source (type: ' . gettype($this->source) . ').';
	}

	/**
	 * If a template source is available
	 *
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasTemplate() {
		return $this->source !== NULL;
	}

	/**
	 * Dummy method to satisfy the TemplateView contract.
	 * Layouts are not supported at this level.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLayoutSource($layoutName = 'default') {
		throw new \F3\Fluid\View\Exception\InvalidTemplateResourceException('Layouts are not directly supported by the TypoScript Template object', 1277298477);
	}

	/**
	 * Dummy method to satisfy the TemplateView contract.
	 * Partials are not supported at this level.
	 *
	 * @param string $partialName
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPartialSource($partialName) {
		throw new \F3\Fluid\View\Exception\InvalidTemplateResourceException('Partials are not directly supported by the TypoScript Template object', 1277298476);
	}

	/**
	 * Build parser configuration
	 *
	 * @return \F3\Fluid\Core\Parser\Configuration
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function buildParserConfiguration() {
		$this->resourceInterceptor = $this->objectManager->get('F3\Fluid\Core\Parser\Interceptor\Resource');
		$parserConfiguration = $this->objectManager->create('F3\Fluid\Core\Parser\Configuration');
		$parserConfiguration->addInterceptor($this->resourceInterceptor);
		return $parserConfiguration;
	}

	/**
	 * Sets the property processor chain for a specific property
	 *
	 * @param string $propertyName Name of the property to set the chain for
	 * @param \F3\TypoScript\ProcessorChain $propertyProcessorChain The property processor chain for that property
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setPropertyProcessorChain($propertyName, \F3\TypoScript\ProcessorChain $propertyProcessorChain) {
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
	 * @return \F3\TypoScript\ProcessorChain $propertyProcessorChain: The property processor chain of that property
	 * @throws \F3\TypoScript\Exception\NoProcessorChainFoundException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertyProcessorChain($propertyName) {
		if (!isset($this->propertyProcessorChains[$propertyName])) throw new \F3\TypoScript\Exception\NoProcessorChainFoundException('Tried to retrieve the property processor chain for property "' . $propertyName . '" but no processor chain exists for that property.', 1179407935);
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
		if (!method_exists($this, $getterMethodName)) {
			throw new \InvalidArgumentException('Tried to create a processing proxy for non-existing getter ' . get_class($this) . '->' . $getterMethodName . '().', 1179406581);
		}

		$propertyValue = $this->$getterMethodName();
		if ($propertyValue === NULL && $this->model !== NULL) {
			if (\F3\FLOW3\Reflection\ObjectAccess::isPropertyGettable($this->model, $propertyName)) {
				$propertyValue = \F3\FLOW3\Reflection\ObjectAccess::getProperty($this->model, $propertyName);
			}
		}

		$processorChains = isset($this->propertyProcessorChains[$propertyName]) ? $this->propertyProcessorChains[$propertyName] : NULL;
		return ($processorChains === NULL) ? $propertyValue : new \F3\TypoScript\PropertyProcessingProxy($propertyValue, $processorChains);
	}

}
?>