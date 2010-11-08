<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\View;

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
 * A view which renders a node based on a TypoScript template
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class TypoScriptView extends \F3\FLOW3\MVC\View\AbstractView {

	/**
	 * @inject
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @inject
	 * @var \F3\TYPO3\Domain\Service\TypoScriptService
	 */
	protected $typoScriptService;

	/**
	 * @inject
	 * @var \F3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @inject
	 * @var \F3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * Renders the node assigned to this view, based on the TypoScript configuration
	 * which applies to the current content context.
	 *
	 * @return string Rendered node
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render() {
		if (!isset($this->variables['value']) || !$this->variables['value'] instanceof \F3\TYPO3CR\Domain\Model\Node) {
			return 'TypoScriptView: A valid node must be assigned to the variable "value" via the TypoScriptView\'s assign() method.';
		}

		$node = $this->variables['value'];
		$contentContext = $node->getContext();

		$type = 'default';
		$typoScriptObjectTree = $this->typoScriptService->getMergedTypoScriptObjectTree($contentContext->getCurrentSiteNode(), $node);
		if ($typoScriptObjectTree === NULL || count($typoScriptObjectTree) === 0) {
			throw new \F3\TYPO3\Controller\Exception\NoTypoScriptConfigurationException('No TypoScript template was found for the current position in the content tree.', 1255513200);
		}

		foreach ($typoScriptObjectTree as $firstLevelTypoScriptObject) {
			if ($firstLevelTypoScriptObject instanceof \F3\TYPO3\TypoScript\Page && $firstLevelTypoScriptObject->getType() === $type) {
				$pageTypoScriptObject = $firstLevelTypoScriptObject;
				break;
			}
		}

		if (!isset($pageTypoScriptObject)) {
			throw new \F3\TYPO3\Controller\Exception\NoTypoScriptPageObjectException('No TypoScript Page object with type "' . $type . '" was found in the current TypoScript configuration.', 1255513201);
		}

		$renderingContext = $this->objectManager->create('F3\TypoScript\RenderingContext');
		$renderingContext->setControllerContext($this->controllerContext);
		$renderingContext->setContentContext($contentContext);

		$pageTypoScriptObject->setRenderingContext($renderingContext);
		return $pageTypoScriptObject->render();
	}

}

?>