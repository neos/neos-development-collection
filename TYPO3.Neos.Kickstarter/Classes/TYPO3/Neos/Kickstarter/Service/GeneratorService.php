<?php
namespace TYPO3\Neos\Kickstarter\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos.Kickstarter".*
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Package\MetaData;
use TYPO3\Flow\Package\MetaData\PackageConstraint;
use TYPO3\Flow\Package\MetaDataInterface;
use TYPO3\Flow\Package\PackageManagerInterface;

/**
 * Service to generate site packages
 */
class GeneratorService extends \TYPO3\Kickstart\Service\GeneratorService {

	/**
	 * @Flow\Inject
	 * @var PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * Generate a site package and fill it with boilerplate data.
	 *
	 * @param string $packageKey
	 * @param string $siteName
	 * @return array
	 */
	public function generateSitePackage($packageKey, $siteName) {
		$packageMetaData = new MetaData($packageKey);
		$packageMetaData->addConstraint(new PackageConstraint(MetaDataInterface::CONSTRAINT_TYPE_DEPENDS, 'TYPO3.Neos'));
		$packageMetaData->addConstraint(new PackageConstraint(MetaDataInterface::CONSTRAINT_TYPE_DEPENDS, 'TYPO3.Neos.NodeTypes'));
		$this->packageManager->createPackage($packageKey, $packageMetaData, NULL, 'typo3-flow-site');
		$this->generateSitesXml($packageKey, $siteName);
		$this->generateSitesTypoScript($packageKey, $siteName);
		$this->generateSitesTemplate($packageKey, $siteName);
		$this->generateNodeTypesConfiguration($packageKey);

		return $this->generatedFiles;
	}

	/**
	 * Generate a "Sites.xml" for the given package and name.
	 *
	 * @param string $packageKey
	 * @param string $siteName
	 * @return void
	 */
	protected function generateSitesXml($packageKey, $siteName) {
		$templatePathAndFilename = 'resource://TYPO3.Neos.Kickstarter/Private/Generator/Content/Sites.xml';

		$contextVariables = array();
		$contextVariables['packageKey'] = $packageKey;
		$contextVariables['siteName'] = $siteName;
		$packageKeyDomainPart = substr(strrchr($packageKey, '.'), 1) ?: $packageKey;
		$contextVariables['siteNodeName'] = strtolower($packageKeyDomainPart);

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$sitesXmlPathAndFilename = $this->packageManager->getPackage($packageKey)->getResourcesPath() . 'Private/Content/Sites.xml';
		$this->generateFile($sitesXmlPathAndFilename, $fileContent);
	}

	/**
	 * Generate basic TypoScript files.
	 *
	 * @param string $packageKey
	 * @param string $siteName
	 * @return void
	 */
	protected function generateSitesTypoScript($packageKey, $siteName) {
		$templatePathAndFilename = 'resource://TYPO3.Neos.Kickstarter/Private/Generator/TypoScripts/Root.ts2';

		$contextVariables = array();
		$contextVariables['packageKey'] = $packageKey;
		$contextVariables['siteName'] = $siteName;
		$packageKeyDomainPart = substr(strrchr($packageKey, '.'), 1) ?: $packageKey;
		$contextVariables['siteNodeName'] = $packageKeyDomainPart;

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$sitesTypoScriptPathAndFilename = $this->packageManager->getPackage($packageKey)->getResourcesPath() . 'Private/TypoScripts/Library/Root.ts2';
		$this->generateFile($sitesTypoScriptPathAndFilename, $fileContent);
	}

	/**
	 * Generate basic template file.
	 *
	 * @param string $packageKey
	 * @param string $siteName
	 * @return void
	 */
	protected function generateSitesTemplate($packageKey, $siteName) {
		$templatePathAndFilename = 'resource://TYPO3.Neos.Kickstarter/Private/Generator/Template/SiteTemplate.html';

		$contextVariables = array();
		$contextVariables['siteName'] = $siteName;
		$contextVariables['neosViewHelper'] = '{namespace neos=TYPO3\Neos\ViewHelpers}';
		$contextVariables['typoScriptViewHelper'] = '{namespace ts=TYPO3\TypoScript\ViewHelpers}';
		$packageKeyDomainPart = substr(strrchr($packageKey, '.'), 1) ?: $packageKey;
		$contextVariables['siteNodeName'] = lcfirst($packageKeyDomainPart);

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$sitesTypoScriptPathAndFilename = $this->packageManager->getPackage($packageKey)->getResourcesPath() . 'Private/Templates/Page/Default.html';
		$this->generateFile($sitesTypoScriptPathAndFilename, $fileContent);
	}

	/**
	 * Generate a example NodeTypes.yaml
	 *
	 * @param string $packageKey
	 * @return void
	 */
	protected function generateNodeTypesConfiguration($packageKey) {
		$templatePathAndFilename = 'resource://TYPO3.Neos.Kickstarter/Private/Generator/Configuration/NodeTypes.yaml';

		$fileContent = file_get_contents($templatePathAndFilename);

		$sitesTypoScriptPathAndFilename = $this->packageManager->getPackage($packageKey)->getConfigurationPath() . 'NodeTypes.yaml';
		$this->generateFile($sitesTypoScriptPathAndFilename, $fileContent);
	}
}
