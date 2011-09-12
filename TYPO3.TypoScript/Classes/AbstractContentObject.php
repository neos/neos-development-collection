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
 * Common class for TypoScript Content Objects
 *
 */
abstract class AbstractContentObject extends \TYPO3\TypoScript\AbstractObject implements \TYPO3\TypoScript\ContentObjectInterface {

	/**
	 * A valid source for a TypoScript Template object which should be the default
	 * this TypoScript object. Should be overriden by the actual TS object implementation.
	 *
	 * @var string
	 */
	protected $templateSource;

	/**
	 * @var \TYPO3\TYPO3\TypoScript\Template
	 */
	protected $template;

	/**
	 * Names of the properties of this TypoScript which should be available in
	 * this TS object's template while rendering it.
	 *
	 * @var array
	 */
	protected $presentationModelPropertyNames = array();

	/**
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * The rendering context as passed to render()
	 *
	 * @transient
	 * @var \TYPO3\TypoScript\RenderingContext
	 */
	protected $renderingContext;

	/**
	 * Injects the system logger
	 *
	 * @param \TYPO3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectSystemLogger(\TYPO3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Injects a fresh template
	 *
	 * @param \TYPO3\TYPO3\TypoScript\Template $template
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectTemplate(\TYPO3\TYPO3\TypoScript\Template $template) {
		$this->template = $template;
		$this->template->setSource($this->templateSource);
	}

	/**
	 * Sets the rendering context
	 *
	 * @param \TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setRenderingContext(\TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
		if (!$renderingContext instanceof \TYPO3\TypoScript\RenderingContext) {
			throw new \InvalidArgumentException('AbstractContentObject only supports \TYPO3\TypoScript\RenderingContext as a rendering context.', 1277825291);
		}
		$this->renderingContext = $renderingContext;
	}

	/**
	 * Overrides the template
	 *
	 * Note: You rarely want to override the actual template object - that's only
	 *       the case if you want to use an alternative templating engine.
	 *       If all you want is a Fluid template, then just set the templateSource
	 *       instead of setting the template object.
	 *
	 * @param \TYPO3\TYPO3\TypoScript\Template $template
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setTemplate(\TYPO3\TYPO3\TypoScript\Template $template) {
		$this->template = $template;
	}

	/**
	 * Returns the page template object
	 *
	 * @return \TYPO3\TYPO3\TypoScript\Template
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTemplate() {
		return $this->template;
	}

	/**
	 * Returns the rendered content of this content object
	 *
	 * Any exception thrown while preparing or rendering the template is caught,
	 * logged and returned as an HTML comment.
	 *
	 * @return string The rendered content as a string - usually (X)HTML, XML or just plain text
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render() {
		try {
			$this->template->setRenderingContext($this->renderingContext);
			foreach ($this->presentationModelPropertyNames as $propertyName) {
				$this->template->assign($propertyName, $this->getPropertyProcessingProxy($propertyName));
			}
			if ($this->node !== NULL) {
				if (!$this->node->isAccessible()) {
					return '';
				}
				$this->template->assign('node', $this->node);
			}

			if (isset($this->propertyProcessorChains['_root'])) {
				return $this->propertyProcessorChains['_root']->process($this->template->render());
			} else {
				return $this->template->render();
			}
		} catch (\Exception $exception) {
			$this->systemLogger->logException(new \TYPO3\TypoScript\Exception('Exception caught in ' . get_class($this) . '::render()', 1289997632, $exception));
			$message = 'Exception #' . $exception->getCode() . ' thrown while rendering ' . get_class($this) . '. See log for more details.';
			return ($this->renderingContext->getObjectManager()->getContext() === 'Development') ? ('<strong>' . $message . '</strong>') : ('<!--' . $message . '-->');
		}
	}

	/**
	 * Casts this TypoScript Object to a string by invoking the render() method.
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __toString() {
		return $this->render();
	}


}
?>