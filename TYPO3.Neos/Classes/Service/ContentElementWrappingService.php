<?php
namespace TYPO3\TYPO3\Service;

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
 * The content element wrapping service adds the necessary markup around
 * a content element such that it can be edited using the Content Module
 * of the TYPO3 Backend.
 *
 * @scope singleton
 */
class ContentElementWrappingService {

	/**
	 * Wrap the $content identified by $node with the needed markup for
	 * the backend.
	 * $parameters can be used to further pass parameters to the content element.
	 *
	 * @param TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param string $content
	 * @param array $parameters
	 */
	public function wrapContentObject(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $content, $parameters = array()) {
		// TODO: If not in backend, return content directly

		$tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('div');
		$tagBuilder->addAttribute('about', $node->getContextPath());

		$tagBuilder->addAttribute('data-__workspacename', $node->getWorkspace()->getName());
		$tagBuilder->addAttribute('data-_hidden', ($node->isHidden() ? 'true' : 'false'));

		$cssClasses = array('t3-contentelement');

			// TODO: This must be received from the schema...
		switch ($node->getContentType()) {
			case 'TYPO3.TYPO3:Text':
				$cssClasses[] = 't3-text';
				break;
			default: // Plugin
				$cssClasses[] = 't3-plugin';
				break;
		}

		foreach ($parameters as $key => $value) {
			$tagBuilder->addAttribute('data-' . $key, $value);
		}

		if ($node->isHidden()) {
			$cssClasses[] = 't3-contentelement-hidden';
		}

		$tagBuilder->addAttribute('class', implode(' ', $cssClasses));

		$tagBuilder->setContent($content);
		return $tagBuilder->render();
	}
}
?>