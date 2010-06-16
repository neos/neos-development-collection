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
	protected $typoScriptParser;

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
		$nodes = $this->contentContext->getNodeService()->getNodesOnPath($nodePath);
		if (!is_array($nodes)) return NULL;

		$siteResourcesPackageKey = $this->contentContext->getCurrentSite()->getSiteResourcesPackageKey();
		$typoScriptsPath = 'resource://' . $siteResourcesPackageKey . '/Private/TypoScripts/';

		$mergedTypoScriptCode = '';
		foreach ($nodes as $node) {
			$typoScriptsPath .= $node->getNodeName() . '/';
			$mergedTypoScriptCode .= $this->readExternalTypoScriptFiles($typoScriptsPath) . chr(10);

			$configurations = $node->getConfigurations();
			foreach ($configurations as $configuration) {
				if ($configuration instanceof \F3\TYPO3\Domain\Model\Configuration\TypoScript) {
					$mergedTypoScriptCode .= $configuration->getSourceCode() . chr(10);
				}
			}
		}
		$this->typoScriptParser->setDefaultNamespace('F3\TYPO3\TypoScript');
		return  $this->typoScriptParser->parse($mergedTypoScriptCode);
	}

	/**
	 * Scans the directory of the given path for .ts2 files, reads them and returns their
	 * content merged as a string.
	 *
	 * @param string $path Path to the directory to read the files from
	 * @return string The merged content of the .ts2 files found
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function readExternalTypoScriptFiles($path) {
		$mergedTypoScriptCode = '';
		if (is_dir($path)) {
			$directoryIterator = new \DirectoryIterator($path);
			$filenames = array();
			foreach ($directoryIterator as $file) {
				$filename = $file->getFilename();
				if ($file->isFile() && substr($filename, -4) === '.ts2') {
					$filePathsAndNames[] = $file->getPathname();
				}
			}
			natsort($filePathsAndNames);
			foreach ($filePathsAndNames as $filePathAndName) {
				$mergedTypoScriptCode .= \F3\FLOW3\Utility\Files::getFileContents($filePathAndName) . chr(10);
			}
		}
		return $mergedTypoScriptCode;
	}
}
?>