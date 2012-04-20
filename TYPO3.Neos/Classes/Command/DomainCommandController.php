<?php
namespace TYPO3\TYPO3\Command;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3,
	TYPO3\TYPO3\Domain\Model\Domain as Domain,
	TYPO3\TYPO3\Domain\Model\Site as Site;

/**
 * Domain command controller for the TYPO3.TYPO3 package
 *
 * @FLOW3\Scope("singleton")
 */
class DomainCommandController extends \TYPO3\FLOW3\Cli\CommandController {

	/**
	 * @var \TYPO3\TYPO3\Domain\Repository\DomainRepository
	 * @FLOW3\Inject
	 */
	protected $domainRepository;

	/**
	 * @var \TYPO3\TYPO3\Domain\Repository\SiteRepository
	 * @FLOW3\Inject
	 */
	protected $siteRepository;

	/**
	 * Add a domain record
	 *
	 * @param string $siteNodeName The nodeName of the site rootNode, e.g. "phoenixtypo3org"
	 * @param string $hostPattern The host pattern to match on, e.g. "phoenix.typo3.org"
	 * @return void
	 */
	public function addCommand($siteNodeName, $hostPattern) {
		$site = $this->siteRepository->findOneByNodeName($siteNodeName);
		if (!$site instanceof Site) {
			$this->outputLine('No site found with nodeName "%s".', array($siteNodeName));
			$this->quit(1);
		}

		$domains = $this->domainRepository->findByHostPattern($hostPattern);
		if ($domains->count() > 0) {
			$this->outputLine('The host pattern "%s" is not unique', array($hostPattern));
			$this->quit(1);
		}

		$domain = new Domain();
		$domain->setSite($site);
		$domain->setHostPattern($hostPattern);
		$this->domainRepository->add($domain);

		$this->outputLine('Domain created');
	}

	/**
	 * Display a list of available domain records
	 *
	 * @param string $hostPattern An optional host pattern to search for
	 * @return void
	 */
	public function listCommand($hostPattern = NULL) {
		if ($hostPattern === NULL) {
			$domains = $this->domainRepository->findAll();
		} else {
			$domains = $this->domainRepository->findByHost($hostPattern);
		}

		if (count($domains) === 0) {
			$this->outputLine('No domains available');
			$this->quit(0);
		}

		$longestNodeName = 9;
		$longestHostPattern = 12;
		$availableDomains = array();

		foreach ($domains as $domain) {
			array_push($availableDomains, array(
				'nodeName' => $domain->getSite()->getNodeName(),
				'hostPattern' => $domain->getHostPattern()
			));
			if (strlen($domain->getSite()->getNodeName()) > $longestNodeName) {
				$longestNodeName = strlen($domain->getSite()->getNodeName());
			}
			if (strlen($domain->getHostPattern()) > $longestHostPattern) {
				$longestHostPattern = strlen($domain->getHostPattern());
			}
		}

		$this->outputLine();
		$this->outputLine(' ' . str_pad('Node name', $longestNodeName + 15) . 'Host pattern');
		$this->outputLine(str_repeat('-', $longestNodeName + $longestHostPattern + 15 + 2));
		foreach ($availableDomains as $domain) {
			$this->outputLine(' ' . str_pad($domain['nodeName'], $longestNodeName + 15) . $domain['hostPattern']);
		}
		$this->outputLine();
	}

	/**
	 * Delete a domain record
	 *
	 * @param string $hostPattern The host pattern of the domain to remove
	 * @return void
	 */
	public function deleteCommand($hostPattern) {
		$domain = $this->domainRepository->findOneByHostPattern($hostPattern);
		if (!$domain instanceof Domain) {
			$this->outputLine('Domain is not found');
			$this->quit(1);
		}

		$this->domainRepository->remove($domain);
		$this->outputLine('Domain deleted');
	}

}
?>