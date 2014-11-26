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
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;

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
	 * Pattern used for determining the TypoScript root file for a site
	 *
	 * @var string
	 */
	protected $siteRootTypoScriptPattern = 'resource://%s/Private/TypoScript/Root.ts2';

	/**
	 * Pattern used for determining the TypoScript root file for a site
	 *
	 * @var string
	 * @deprecated since 1.2 will be removed in 2.0
	 */
	protected $legacySiteRootTypoScriptPattern = 'resource://%s/Private/TypoScripts/Library/Root.ts2';

	/**
	 * Pattern used for determining the TypoScript root file for autoIncludes
	 *
	 * @var string
	 */
	protected $autoIncludeTypoScriptPattern = 'resource://%s/Private/TypoScript/Root.ts2';

	/**
	 * Array of TypoScript files to include before the site TypoScript
	 *
	 * Example:
	 *
	 *     array(
	 *         'resources://MyVendor.MyPackageKey/Private/TypoScript/Root.ts2',
	 *         'resources://SomeVendor.OtherPackage/Private/TypoScript/Root.ts2'
	 *     )
	 *
	 * @var array
	 */
	protected $prependTypoScriptIncludes = array();

	/**
	 * Array of TypoScript files to include after the site TypoScript
	 *
	 * Example:
	 *
	 *     array(
	 *         'resources://MyVendor.MyPackageKey/Private/TypoScript/Root.ts2',
	 *         'resources://SomeVendor.OtherPackage/Private/TypoScript/Root.ts2'
	 *     )
	 *
	 * @var array
	 */
	protected $appendTypoScriptIncludes = array();

	/**
	 * @Flow\Inject(setting="typoScript.autoInclude")
	 * @var array
	 */
	protected $autoIncludeConfiguration = array();

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * Initializes the parser
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->typoScriptParser->setObjectTypeNamespace('default', 'TYPO3.Neos');
	}

	/**
	 * Create a runtime for the given site node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $currentSiteNode
	 * @param \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext
	 * @return \TYPO3\TypoScript\Core\Runtime
	 */
	public function createRuntime(NodeInterface $currentSiteNode, \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext) {
		$typoScriptObjectTree = $this->getMergedTypoScriptObjectTree($currentSiteNode);
		$typoScriptRuntime = new \TYPO3\TypoScript\Core\Runtime($typoScriptObjectTree, $controllerContext);
		return $typoScriptRuntime;
	}

	/**
	 * Returns a merged TypoScript object tree in the context of the given nodes
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $startNode Node marking the starting point
	 * @return array The merged object tree as of the given node
	 * @throws \TYPO3\Neos\Domain\Exception
	 */
	public function getMergedTypoScriptObjectTree(NodeInterface $startNode) {
		$contentContext = $startNode->getContext();
		$siteResourcesPackageKey = $contentContext->getCurrentSite()->getSiteResourcesPackageKey();

		$siteRootTypoScriptPathAndFilename = sprintf($this->siteRootTypoScriptPattern, $siteResourcesPackageKey);
		$siteRootTypoScriptCode = $this->readExternalTypoScriptFile($siteRootTypoScriptPathAndFilename);
		$expectedSiteRootTypoScriptPathAndFilename = $siteRootTypoScriptPathAndFilename;

		if ($siteRootTypoScriptCode === '') {
			$siteRootTypoScriptPathAndFilename = sprintf($this->legacySiteRootTypoScriptPattern, $siteResourcesPackageKey);
			$siteRootTypoScriptCode = $this->readExternalTypoScriptFile($siteRootTypoScriptPathAndFilename);
		}

		if (trim($siteRootTypoScriptCode) === '') {
			throw new \TYPO3\Neos\Domain\Exception(sprintf('The site package %s did not contain a root TypoScript configuration. Please make sure that there is one at %s.', $siteResourcesPackageKey, $expectedSiteRootTypoScriptPathAndFilename), 1357215211);
		}

		$mergedTypoScriptCode = '';
		$mergedTypoScriptCode .= $this->generateNodeTypeDefinitions();
		$mergedTypoScriptCode .= $this->getTypoScriptIncludes($this->prepareAutoIncludeTypoScript());
		$mergedTypoScriptCode .= $this->getTypoScriptIncludes($this->prependTypoScriptIncludes);
		$mergedTypoScriptCode .= $siteRootTypoScriptCode;
		$mergedTypoScriptCode .= $this->getTypoScriptIncludes($this->appendTypoScriptIncludes);

		return $this->typoScriptParser->parse($mergedTypoScriptCode, $siteRootTypoScriptPathAndFilename);
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
	 * Generate TypoScript prototype definitions for all node types
	 *
	 * Only fully qualified node types (e.g. MyVendor.MyPackage:NodeType) will be considered.
	 *
	 * @return string
	 */
	protected function generateNodeTypeDefinitions() {
		$code = '';
		/** @var NodeType $nodeType */
		foreach ($this->nodeTypeManager->getNodeTypes(FALSE) as $nodeType) {
			$code .= $this->generateTypoScriptForNodeType($nodeType);
		}
		return $code;
	}

	/**
	 * Generate a TypoScript prototype definition for a given node type
	 *
	 * A node will be rendered by TYPO3.Neos:Content by default with a template in
	 * resource://PACKAGE_KEY/Private/Templates/NodeTypes/NAME.html and forwards all public
	 * node properties to the template TypoScript object.
	 *
	 * @param NodeType $nodeType
	 * @return string
	 */
	protected function generateTypoScriptForNodeType(NodeType $nodeType) {
		if (strpos($nodeType->getName(), ':') === FALSE) {
			return '';
		}

		if ($nodeType->isOfType('TYPO3.Neos:Content')) {
			$basePrototypeName = 'TYPO3.Neos:Content';
		} elseif ($nodeType->isOfType('TYPO3.Neos:Document')) {
			$basePrototypeName = 'TYPO3.Neos:Document';
		} else {
			$basePrototypeName = 'TYPO3.TypoScript:Template';
		}

		$output = 'prototype(' . $nodeType->getName() . ') < prototype(' . $basePrototypeName . ') {' . chr(10);

		list($packageKey, $relativeName) = explode(':', $nodeType->getName(), 2);
		$templatePath = 'resource://' . $packageKey . '/Private/Templates/NodeTypes/' . $relativeName . '.html';
		$output .= "\t" . 'templatePath = \'' . $templatePath . '\'' . chr(10);

		foreach ($nodeType->getProperties() as $propertyName => $propertyConfiguration) {
			if (isset($propertyName[0]) && $propertyName[0] !== '_') {
				$output .= "\t" . $propertyName . ' = ${q(node).property("' . $propertyName . '")}' . chr(10);
				if (isset($propertyConfiguration['type']) && isset($propertyConfiguration['ui']['inlineEditable']) && $propertyConfiguration['type'] === 'string' && $propertyConfiguration['ui']['inlineEditable'] === TRUE) {
					$output .= "\t" . $propertyName . '.@process.convertUris = TYPO3.Neos:ConvertUris' . chr(10);
				}
			}
		}

		$output .= '}' . chr(10);
		return $output;
	}

	/**
	 * Concatenate the given TypoScript resources with include statements
	 *
	 * @param array $typoScriptResources An array of TypoScript resource URIs
	 * @return string A string of include statements for all resources
	 */
	protected function getTypoScriptIncludes(array $typoScriptResources) {
		$code = chr(10);
		foreach ($typoScriptResources as $typoScriptResource) {
			$code .= 'include: ' . (string)$typoScriptResource . chr(10);
		}
		$code .= chr(10);
		return $code;
	}

	/**
	 * Prepares an array with TypoScript paths to auto include before the Site TypoScript.
	 *
	 * @return array
	 */
	protected function prepareAutoIncludeTypoScript() {
		$autoIncludeTypoScript = array();
		foreach (array_keys($this->packageManager->getActivePackages()) as $packageKey) {
			if (isset($this->autoIncludeConfiguration[$packageKey]) && $this->autoIncludeConfiguration[$packageKey] === TRUE) {
				$autoIncludeTypoScript[] = sprintf($this->autoIncludeTypoScriptPattern, $packageKey);
			}
		}

		return $autoIncludeTypoScript;
	}

	/**
	 * Set the pattern for including the site root TypoScript
	 *
	 * @param string $siteRootTypoScriptPattern A string for the sprintf format that takes the site package key as a single placeholder
	 * @return void
	 */
	public function setSiteRootTypoScriptPattern($siteRootTypoScriptPattern) {
		$this->siteRootTypoScriptPattern = $siteRootTypoScriptPattern;
	}

	/**
	 * Get the TypoScript resources that are included before the site TypoScript.
	 *
	 * @return array
	 */
	public function getPrependTypoScriptIncludes() {
		return $this->prependTypoScriptIncludes;
	}

	/**
	 * Set TypoScript resources that should be prepended before the site TypoScript,
	 * it defaults to the Neos Root.ts2 TypoScript.
	 *
	 * @param array $prependTypoScriptIncludes
	 * @return void
	 */
	public function setPrependTypoScriptIncludes(array $prependTypoScriptIncludes) {
		$this->prependTypoScriptIncludes = $prependTypoScriptIncludes;
	}

	/**
	 * Get TypoScript resources that will be appended after the site TypoScript.
	 *
	 * @return array
	 */
	public function getAppendTypoScriptIncludes() {
		return $this->appendTypoScriptIncludes;
	}

	/**
	 * Set TypoScript resources that should be appended after the site TypoScript,
	 * this defaults to an empty array.
	 *
	 * @param array $appendTypoScriptIncludes An array of TypoScript resource URIs
	 * @return void
	 */
	public function setAppendTypoScriptIncludes(array $appendTypoScriptIncludes) {
		$this->appendTypoScriptIncludes = $appendTypoScriptIncludes;
	}

}
