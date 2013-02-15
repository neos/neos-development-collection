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

use \TYPO3\Flow\I18n\Locale;

use TYPO3\Flow\Annotations as Flow;

/**
 * The Content Context
 *
 * @Flow\Scope("prototype")
 * @api
 */
class ContentContext extends \TYPO3\TYPO3CR\Domain\Service\Context {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @var \DateTime
	 */
	protected $currentDateTime;

	/**
	 * @var \TYPO3\Flow\I18n\Locale
	 */
	protected $locale;

	/**
	 * @var \TYPO3\Neos\Domain\Model\Site
	 */
	protected $currentSite;

	/**
	 * @var \TYPO3\Neos\Domain\Model\Domain
	 */
	protected $currentDomain;

	/**
	 * Initializes the context after all dependencies have been injected.
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->locale = new Locale('mul_ZZ');

		$activeRequestHandler = $this->bootstrap->getActiveRequestHandler();
		if ($activeRequestHandler instanceof \TYPO3\Flow\Http\HttpRequestHandlerInterface) {
			$matchingDomains = $this->domainRepository->findByHost($activeRequestHandler->getHttpRequest()->getUri()->getHost());
			if (count ($matchingDomains) > 0) {
				$this->currentDomain = $matchingDomains[0];
				$this->currentSite = $matchingDomains[0]->getSite();
			} else {
				$this->currentSite = $this->siteRepository->findFirst();
			}
		} else {
			$this->currentSite = $this->siteRepository->findFirst();
		}
	}

	/**
	 * Returns the locale of this context.
	 *
	 * @return \TYPO3\Flow\I18n\Locale
	 */
	public function getLocale() {
		return $this->locale;
	}

	/**
	 * Returns the current site from this frontend context
	 *
	 * @return \TYPO3\Neos\Domain\Model\Site The current site
	 */
	public function getCurrentSite() {
		return $this->currentSite;
	}

	/**
	 * Sets the current site.
	 *
	 * Note that changing the current site after the context has been in use
	 * already can lead to unexpected behavior.
	 *
	 * @param \TYPO3\Neos\Domain\Model\Site $site
	 * @return void
	 */
	public function setCurrentSite(\TYPO3\Neos\Domain\Model\Site $site) {
		$this->currentSite = $site;
	}

	/**
	 * Returns the current domain from this frontend context
	 *
	 * @return \TYPO3\Neos\Domain\Model\Domain The current domain
	 * @api
	 */
	public function getCurrentDomain() {
		return $this->currentDomain;
	}

	/**
	 * Returns the node of the current site.
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	public function getCurrentSiteNode() {
		return ($this->currentSite === NULL) ? NULL : $this->getNode('/sites/' . $this->currentSite->getNodeName());
	}
}
?>