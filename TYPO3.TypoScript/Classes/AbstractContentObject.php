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
 * Common class for TypoScript Content Objects
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class AbstractContentObject extends \F3\TypoScript\AbstractObject implements \F3\TypoScript\ContentObjectInterface {

	/**
	 * A valid source for a TypoScript Template object which should be the default
	 * this TypoScript object. Should be overriden by the actual TS object implementation.
	 *
	 * @var string
	 */
	protected $typoScriptObjectTemplateSource = '';

	/**
	 * @var \F3\TYPO3\TypoScript\Template
	 */
	protected $typoScriptObjectTemplate;

	/**
	 * Names of the properties of this TypoScript which should be available in
	 * this TS object's template while rendering it.
	 *
	 * Note: Make sure that a getter method for the respective property exists.
	 *
	 * @var array
	 */
	protected $presentationModelPropertyNames = array();

	/**
	 * The rendering context as passed to render()
	 * 
	 * @transient
	 * @var \F3\TypoScript\RenderingContext
	 */
	protected $renderingContext;

	/**
	 * Injects a fresh template
	 *
	 * @param \F3\TYPO3\TypoScript\Template $template
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectTypoScriptObjectTemplate(\F3\TYPO3\TypoScript\Template $template) {
		$this->typoScriptObjectTemplate = $template;
	}

	/**
	 * Overrides the TypoScript Object Template Source
	 *
	 * @param mixed $source May be a plain string, resource URI or TypoScript Template Object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setTypoScriptObjectTemplateSource($source) {
		$this->typoScriptObjectTemplateSource = $source;
	}

	/**
	 * Returns the TypoScript template source
	 *
	 * @return mixed
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTypoScriptObjectTemplateSource() {
		return $this->typoScriptObjectTemplateSource;
	}

	/**
	 * Returns the rendered content of this content object
	 *
	 * @param \F3\TypoScript\RenderingContext $renderingContext
	 * @return string The rendered content as a string - usually (X)HTML, XML or just plaing text
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render(\F3\TypoScript\RenderingContext $renderingContext) {
		$this->renderingContext = $renderingContext;

		foreach ($this->presentationModelPropertyNames as $propertyName) {
			$this->typoScriptObjectTemplate->assign($propertyName, $this->getProcessedProperty($propertyName, $renderingContext));
		}
		$this->typoScriptObjectTemplate->setSource($this->typoScriptObjectTemplateSource);
		$content = $this->typoScriptObjectTemplate->render($renderingContext);

		if (!isset($this->propertyProcessorChains['_root'])) return $content;
		return $this->propertyProcessorChains['_root']->process($content);
	}
}
?>