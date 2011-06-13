<?php
namespace F3\TYPO3\ViewHelpers\Uri;

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
 * A view helper for creating URIs to nodes.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <typo3:uri.node />
 * </code>
 *
 * Output:
 * homepage/about.html
 * (depending on current workspace, current node, format etc.)
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class NodeViewHelper extends \F3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Render the Uri.
	 *
	 * @param mixed $node A node object or a node path
	 * @param string $format Format to use for the URL, for example "html" or "json"
	 * @param boolean $absolute If set, an absolute URI is rendered
	 * @return string The rendered URI
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render($node = NULL, $format = NULL, $absolute = FALSE) {
		$uriBuilder = $this->controllerContext->getUriBuilder();
		$request = $this->controllerContext->getRequest();

		if ($node === NULL) {
			$contentContext = $this->viewHelperVariableContainer->get('F3\TYPO3', 'contentContext');
			if (!$contentContext instanceof \F3\TYPO3\Domain\Service\ContentContext) {
				throw new \F3\TYPO3\Exception(__CLASS__ . ' requires a valid ContentContext delivered through the View Helper Variable Container.', 1289557402);
			}
			$node = $contentContext->getCurrentNode();
		}

		if ($format === NULL) {
			$format = $request->getFormat();
		}

		$uri = $uriBuilder
			->reset()
			->setCreateAbsoluteUri($absolute)
			->setFormat($format)
			->uriFor(NULL, array('node' => $node));

		return $uri;
	}
}
?>