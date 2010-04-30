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
class Template extends \F3\TypoScript\AbstractContentObject {

	/**
	 * @inject
	 * @var \F3\Fluid\Core\Parser\TemplateParser
	 */
	protected $templateParser;

	/**
	 * @inject
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 *
	 * @var mixed
	 */
	protected $source;

	protected $variables = array();

	/**
	 *
	 * @param <type> $source
	 */
	public function setSource($source) {
		$this->source = $source;
	}

	/**
	 *
	 * @return <type>
	 */
	public function getSource() {
		return $this->source;
	}

	/**
	 * Returns the rendered content of this content object
	 *
	 * @return string The rendered content as a string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRenderedContent() {
		$this->templateParser->setConfiguration($this->buildParserConfiguration());
		$parsedTemplate = $this->templateParser->parse((string)$this->source);
		$variableContainer = $parsedTemplate->getVariableContainer();
		return $parsedTemplate->render($this->buildRenderingContext());
	}

	/**
	 * Build parser configuration
	 *
	 * @return \F3\Fluid\Core\Parser\Configuration
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildParserConfiguration() {
		$parserConfiguration = $this->objectManager->create('F3\Fluid\Core\Parser\Configuration');
		$parserConfiguration->addInterceptor($this->objectManager->get('F3\Fluid\Core\Parser\Interceptor\Escape'));
		$parserConfiguration->addInterceptor($this->objectManager->get('F3\Fluid\Core\Parser\Interceptor\Resource'));
		return $parserConfiguration;
	}

	/**
	 * Build the rendering context
	 *
	 * @param \F3\Fluid\Core\ViewHelper\TemplateVariableContainer $variableContainer
	 * @return \F3\Fluid\Core\Rendering\RenderingContext
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function buildRenderingContext(\F3\Fluid\Core\ViewHelper\TemplateVariableContainer $variableContainer = NULL) {
		if ($variableContainer === NULL) {
			$variableContainer = $this->objectManager->create('F3\Fluid\Core\ViewHelper\TemplateVariableContainer', $this->variables);
		}

		$renderingContext = $this->objectManager->create('F3\Fluid\Core\Rendering\RenderingContext');
		$renderingContext->setTemplateVariableContainer($variableContainer);

		$viewHelperVariableContainer = $this->objectManager->create('F3\Fluid\Core\ViewHelper\ViewHelperVariableContainer');
		$viewHelperVariableContainer->setView($this);
		$renderingContext->setViewHelperVariableContainer($viewHelperVariableContainer);
		return $renderingContext;
	}
}
?>