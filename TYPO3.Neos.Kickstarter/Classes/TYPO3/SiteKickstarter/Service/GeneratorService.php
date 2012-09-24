<?php
namespace TYPO3\SiteKickstarter\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "SiteKickstarter".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Service to generate site packages
 *
 */
class GeneratorService extends \TYPO3\Kickstart\Service\GeneratorService {

	/**
	 * Generate a Sites.xml for the given package and name.
	 *
	 * @param string $packageKey
	 * @param string $siteName
	 * @return array
	 */
	public function generateSitesXml($packageKey, $siteName) {
		$templatePathAndFilename = 'resource://TYPO3.SiteKickstarter/Private/Generator/Content/Sites.xml';

		$contextVariables = array();
		$contextVariables['packageKey'] = $packageKey;
		$contextVariables['siteName'] = $siteName;
		$packageKeyDomainPart = substr(strrchr($packageKey, '.'), 1);
		$contextVariables['siteNodeName'] = strtolower($packageKeyDomainPart);

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$sitesXmlPathAndFilename = $this->packageManager->getPackage($packageKey)->getResourcesPath() . 'Private/Content/Sites.xml';
		$this->generateFile($sitesXmlPathAndFilename, $fileContent);

		return $this->generatedFiles;
	}

	/**
	 * Generate basic TypoScript files.
	 *
	 * @param string $packageKey
	 * @param string $siteName
	 * @return array
	 */
	public function generateSitesTypoScript($packageKey, $siteName) {
		$templatePathAndFilename = 'resource://TYPO3.SiteKickstarter/Private/Generator/TypoScripts/Root.ts2';

		$contextVariables = array();
		$contextVariables['packageKey'] = $packageKey;
		$contextVariables['siteName'] = $siteName;
		$packageKeyDomainPart = substr(strrchr($packageKey, '.'), 1);
		$contextVariables['siteNodeName'] = $packageKeyDomainPart;

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$sitesTypoScriptPathAndFilename = $this->packageManager->getPackage($packageKey)->getResourcesPath() . 'Private/TypoScripts/Library/Root.ts2';
		$this->generateFile($sitesTypoScriptPathAndFilename, $fileContent);

		return $this->generatedFiles;
	}

	/**
	 * Generate basic template file.
	 *
	 * @param string $packageKey
	 * @param string $siteName
	 * @return array
	 */
	public function generateSitesTemplate($packageKey, $siteName) {
		$templatePathAndFilename = 'resource://TYPO3.SiteKickstarter/Private/Generator/Template/SiteTemplate.html';

		$contextVariables = array();
		$contextVariables['siteName'] = $siteName;
		$contextVariables['typo3ViewHelper'] = '{namespace typo3=TYPO3\TYPO3\ViewHelpers}';
		$contextVariables['typoScriptViewHelper'] = '{namespace ts=TYPO3\TypoScript\ViewHelpers}';
		$packageKeyDomainPart = substr(strrchr($packageKey, '.'), 1);
		$contextVariables['siteNodeName'] = lcfirst($packageKeyDomainPart);

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$sitesTypoScriptPathAndFilename = $this->packageManager->getPackage($packageKey)->getResourcesPath() . 'Private/Templates/Page/Default.html';
		$this->generateFile($sitesTypoScriptPathAndFilename, $fileContent);

		return $this->generatedFiles;
	}
}

?>