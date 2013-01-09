<?php
namespace TYPO3\Neos\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Files;

/**
 * The TypoScript Service
 *
 * @Flow\Scope("prototype")
 * @api
 */
class TypoScriptService {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TypoScript\Core\Parser
	 */
	protected $typoScriptParser;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * Pattern used for determining the TypoScripts root path for a site.
	 *
	 * @var string
	 */
	protected $typoScriptsPathPattern = 'resource://%s/Private/TypoScripts';

	/**
	 * Initializes the parser
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->typoScriptParser->setObjectTypeNamespace('default', 'TYPO3.Neos');
	}

	/**
	 * Returns a merged TypoScript object tree in the context of the given nodes
	 *
	 * The start node and end node mark the starting point and end point of the
	 * path to take while searching for TypoScript configuration. The path of the
	 * start node must be the base path of the end node's path.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $startNode Node marking the starting point
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $endNode Node marking the end point
	 * @return array The merged object tree as of the given node
	 */
	public function getMergedTypoScriptObjectTree(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $startNode, \TYPO3\TYPO3CR\Domain\Model\NodeInterface $endNode) {
		$contentContext = $this->nodeRepository->getContext();
		$parentNodes = $contentContext->getNodesOnPath($startNode->getPath(), $endNode->getPath());
		if (!is_array($parentNodes)) {
			return NULL;
		}

		$siteResourcesPackageKey = $contentContext->getCurrentSite()->getSiteResourcesPackageKey();
		$typoScriptsPath = sprintf($this->typoScriptsPathPattern, $siteResourcesPackageKey);

		$rootTypoScriptPath = $typoScriptsPath . '/Library/Root.ts2';
		$siteRootTypoScriptCode = $this->readExternalTypoScriptFile($rootTypoScriptPath);
		if (trim($siteRootTypoScriptCode) === '') {
			throw new \TYPO3\Neos\Domain\Exception(sprintf('The site package %s did not contain a root TypoScript configuration. Please make sure that there is one at %s.', $siteResourcesPackageKey, $rootTypoScriptPath), 1357215211);
		}

		$mergedTypoScriptCode = $this->readExternalTypoScriptFile('resource://TYPO3.Neos/Private/DefaultTypoScript/All.ts2') . $siteRootTypoScriptCode;

		$currentTypoScriptPath = $typoScriptsPath . '/Nodes';
		foreach ($parentNodes as $node) {
			$nodeName = $node->getName();
			$mergedTypoScriptCode .= Files::getFileContents($this->getMixedCasedPathAndFilename($currentTypoScriptPath . '/' . $nodeName . '.ts2')) . chr(10);
			$currentTypoScriptPath .= '/' . basename($this->getMixedCasedPathAndFilename($currentTypoScriptPath . '/' . $nodeName));

			$typoScriptNodes = $node->getChildNodes('TYPO3.Neos:TypoScript');
			foreach ($typoScriptNodes as $typoScriptNode) {
				$mergedTypoScriptCode .= $typoScriptNode->getProperty('sourceCode') . chr(10);
			}
		}
		return $this->typoScriptParser->parse($mergedTypoScriptCode, $typoScriptsPath);
	}

	/**
	 * Returns a merged TypoScript object tree loaded from a specified resource location.
	 *
	 * @param string $typoScriptResourcePath
	 * @return array The merged object tree as of the given node
	 */
	public function readTypoScriptFromSpecificPath($typoScriptResourcePath) {
		$mergedTypoScriptCode = $this->readExternalTypoScriptFiles($typoScriptResourcePath) . chr(10);
		return $this->typoScriptParser->parse($mergedTypoScriptCode);
	}

	/**
	 * Reads the TypoScript file from the given path and filename.
	 * If it doesn't exist, this function will just return an empty string.
	 *
	 * @param string $pathAndFilename Path and filename of the TypoScript file
	 * @return string The content of the .ts2 file, plus one chr(10) at the end
	 */
	protected function readExternalTypoScriptFile($pathAndFilename) {
		return (file_exists($pathAndFilename)) ? Files::getFileContents($pathAndFilename) . chr(10) : '';
	}

	/**
	 * Checks if the directory specified in $pathAndFilename exists and if so,
	 * tries to find a file matching the name in $pathAndFilename through case
	 * insensitive comparison of the file name.
	 *
	 * You must specify a valid case sensitive path – only the filename maybe
	 * case insensitive.
	 *
	 * @param string $pathAndFilename Path and filename
	 * @return mixed Either the resolved case sensitive path and filename or FALSE
	 */
	protected function getMixedCasedPathAndFilename($pathAndFilename) {
		$path = dirname($pathAndFilename);
		if (!is_dir($path)) {
			return FALSE;
		}
		$needleFilename = strtolower(basename($pathAndFilename));
		foreach (new \DirectoryIterator($path) as $fileInfo) {
			$haystackFilename = $fileInfo->getBasename();
			if (strtolower($haystackFilename) === $needleFilename) {
				return $fileInfo->getPathname();
			}
		}
		return FALSE;
	}
}

?>