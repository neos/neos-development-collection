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
 * The TypoScript Rendering Context
 *
 * Instances of this class act as a container for runtime information which
 * is potentially needed by TypoScript object during rendering time.
 * Most importantly that's the Controller Context (which contains the current
 * Request object and further MVC related information).
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class RenderingContext implements \F3\Fluid\Core\Rendering\RenderingContextInterface {

	/**
	 * @var \F3\TYPO3\Domain\Service\ContentContext $contentContext
	 */
	protected $contentContext;

	/**
	 * Template Variable Container. Contains all variables available through object accessors in the template
	 *
	 * @var F3\Fluid\Core\ViewHelper\TemplateVariableContainer
	 */
	protected $templateVariableContainer;

	/**
	 * Object manager which is bubbled through. The ViewHelperNode cannot get an ObjectManager injected because
	 * the whole syntax tree should be cacheable
	 *
	 * @var F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Controller context being passed to the ViewHelper
	 *
	 * @var F3\FLOW3\MVC\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * ViewHelper Variable Container
	 *
	 * @var F3\Fluid\Core\ViewHelpers\ViewHelperVariableContainer
	 */
	protected $viewHelperVariableContainer;

	/**
	 * Sets the content context
	 *
	 * @param \F3\TYPO3\Domain\Service\ContentContext $contentContext
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContentContext(\F3\TYPO3\Domain\Service\ContentContext $contentContext) {
		$this->contentContext = $contentContext;
	}

	/**
	 * Returns the content context
	 *
	 * @return \F3\TYPO3\Domain\Service\ContentContext
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentContext() {
		return $this->contentContext;
	}

	/**
	 * Inject the object manager
	 *
	 * @param F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Returns the object manager. Only the ViewHelperNode should do this.
	 *
	 * @param F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getObjectManager() {
		return $this->objectManager;
	}

	/**
	 * Injects the template variable container containing all variables available through ObjectAccessors
	 * in the template
	 *
	 * @param F3\Fluid\Core\ViewHelper\TemplateVariableContainer $templateVariableContainer The template variable container to set
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function injectTemplateVariableContainer(\F3\Fluid\Core\ViewHelper\TemplateVariableContainer $templateVariableContainer) {
		$this->templateVariableContainer = $templateVariableContainer;
	}

	/**
	 * Get the template variable container
	 *
	 * @return F3\Fluid\Core\ViewHelper\TemplateVariableContainer The Template Variable Container
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getTemplateVariableContainer() {
		return $this->templateVariableContainer;
	}

	/**
	 * Set the controller context which will be passed to the ViewHelper
	 *
	 * @param F3\FLOW3\MVC\Controller\ControllerContext $controllerContext The controller context to set
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setControllerContext(\F3\FLOW3\MVC\Controller\ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
	}

	/**
	 * Get the controller context which will be passed to the ViewHelper
	 *
	 * @return F3\FLOW3\MVC\Controller\ControllerContext The controller context to set
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getControllerContext() {
		return $this->controllerContext;
	}

	/**
	 * Set the ViewHelperVariableContainer
	 *
	 * @param F3\Fluid\Core\ViewHelper\ViewHelperVariableContainer $viewHelperVariableContainer
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function injectViewHelperVariableContainer(\F3\Fluid\Core\ViewHelper\ViewHelperVariableContainer $viewHelperVariableContainer) {
		$this->viewHelperVariableContainer = $viewHelperVariableContainer;
	}

	/**
	 * Get the ViewHelperVariableContainer
	 *
	 * @return F3\Fluid\Core\ViewHelper\ViewHelperVariableContainer
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getViewHelperVariableContainer() {
		return $this->viewHelperVariableContainer;
	}

}
?>