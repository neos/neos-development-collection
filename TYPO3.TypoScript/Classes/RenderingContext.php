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
class RenderingContext {

	/**
	 * @var \F3\FLOW3\MVC\Controller\ControllerContext $controllerContext
	 */
	protected $controllerContext;

	/**
	 * @var \F3\TYPO3\Domain\Service\ContentContext $contentContext
	 */
	protected $contentContext;

	/**
	 * Constructs this context container
	 *
	 * @param \F3\FLOW3\MVC\Controller\ControllerContext $controllerContext
	 * @param \F3\TYPO3\Domain\Service\ContentContext $contentContext
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(\F3\FLOW3\MVC\Controller\ControllerContext $controllerContext,
			  \F3\TYPO3\Domain\Service\ContentContext $contentContext) {
		$this->controllerContext = $controllerContext;
		$this->contentContext = $contentContext;
	}

	/**
	 * Returns the controller context
	 *
	 * @return \F3\FLOW3\MVC\Controller\ControllerContext
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getControllerContext() {
		return $this->controllerContext;
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
}
?>