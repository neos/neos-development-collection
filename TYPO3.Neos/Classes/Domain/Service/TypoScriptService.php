<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Service;

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
 * The TypoScript Service
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class TypoScriptService {


	/**
	 * @var \F3\TYPO3\Domain\Service\ContentContext
	 */
	protected $contentContext;

	/**
	 * @inject
	 * @var \F3\TypoScript\Parser
	 */
	public $typoScriptParser;

	/**
	 * Constructs this service
	 *
	 * @param \F3\TYPO3\Domain\Service\ContentContext $contentContext The context for this service
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(\F3\TYPO3\Domain\Service\ContentContext $contentContext) {
		$this->contentContext = $contentContext;
	}

	/**
	 * Returns the Content Context this service runs in
	 *
	 * @return \F3\TYPO3\Domain\Service\ContentContext
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentContext() {
		return $this->contentContext;
	}

	/**
	 * Returns a merged TypoScript object tree in the context of a node specified by the given
	 * node path.
	 *
	 * @param string $nodePath Path to the node to build the TypoScript Object Tree for
	 * @return array The merged object tree as of the given node or NULL if the given path does not point to a node
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMergedTypoScriptObjectTree($nodePath) {
		$nodeService = $this->contentContext->getNodeService();
		$nodes = $nodeService->getNodesOnPath($this->contentContext->getCurrentSite(), $nodePath);
		if (!is_array($nodes)) return NULL;

		$mergedTypoScriptCode = '';
		foreach ($nodes as $node) {
			$configurations = $node->getConfigurations();
			foreach ($configurations as $configuration) {
				if ($configuration instanceof \F3\TYPO3\Domain\Model\Configuration\TypoScriptTemplate) {
					$mergedTypoScriptCode .= $configuration->getSourceCode() . chr(10);
				}
			}
		}
		return  $this->typoScriptParser->parse($mergedTypoScriptCode);
	}
}
?>