<?php
namespace TYPO3\SiteKickstarter\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "SiteKickstarter".       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Files as Files;

/**
 * Command controller for the Kickstart generator
 *
 */
class SiteCommandController extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 * @Flow\Inject
	 */
	protected $packageManager;

	/**
	 * @var \TYPO3\SiteKickstarter\Service\GeneratorService
	 * @Flow\Inject
	 */
	protected $generatorService;

	/**
	 * Kickstart a new site package
	 *
	 * This command generates a new site package with basic TypoScript and Sites.xml
	 *
	 * @param string $packageKey The packageKey for your site
	 * @param string $siteName The siteName of your site
	 * @return string
	 */
	public function kickstartCommand($packageKey, $siteName) {
		if (!$this->packageManager->isPackageKeyValid($packageKey)) {
			$this->outputLine('Package key "%s" is not valid. Only UpperCamelCase with alphanumeric characters and underscore, please!', array($packageKey));
			$this->quit(1);
		}

		if ($this->packageManager->isPackageAvailable($packageKey)) {
			$this->outputLine('Package "%s" already exists.', array($packageKey));
			$this->quit(1);
		}
		$packagePath = Files::getUnixStylePath(Files::concatenatePaths(array(FLOW_PATH_PACKAGES, 'Sites')));
		$this->packageManager->createPackage($packageKey, NULL, $packagePath, 'typo3-flow-site');

		$generatedFiles = array();
		$generatedFiles += $this->generatorService->generateSitesXml($packageKey, $siteName);
		$generatedFiles += $this->generatorService->generateSitesTypoScript($packageKey, $siteName);
		$generatedFiles += $this->generatorService->generateSitesTemplate($packageKey, $siteName);
		$this->outputLine(implode(PHP_EOL, $generatedFiles));
	}
}

?>