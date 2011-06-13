<?php
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

use \F3\FLOW3\I18n\Locale;

/**
 * The Content Context
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @api
 */
class ContentContext extends \F3\TYPO3CR\Domain\Service\Context {

	/**
	 * @inject
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @inject
	 * @var \F3\TYPO3\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @inject
	 * @var \F3\TYPO3\Domain\Repository\DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @var \DateTime
	 */
	protected $currentDateTime;

	/**
	 * @var \F3\FLOW3\I18n\Locale
	 */
	protected $locale;

	/**
	 * @var \F3\TYPO3\Domain\Model\Site
	 */
	protected $currentSite;

	/**
	 * @var \F3\TYPO3\Domain\Model\Domain
	 */
	protected $currentDomain;

	/**
	 * Initializes the context after all dependencies have been injected.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		$this->locale = new Locale('mul_ZZ');

		$matchingDomains = $this->domainRepository->findByHost($this->environment->getHTTPHost());
		if (count ($matchingDomains) > 0) {
			$this->currentDomain = $matchingDomains[0];
			$this->currentSite = $matchingDomains[0]->getSite();
		} else {
			$this->currentSite = $this->siteRepository->findFirst();
		}
	}

	/**
	 * Returns the locale of this context.
	 *
	 * @return \F3\FLOW3\I18n\Locale
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLocale() {
		return $this->locale;
	}

	/**
	 * Returns the current site from this frontend context
	 *
	 * @return \F3\TYPO3\Domain\Model\Site The current site
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @param \F3\TYPO3\Domain\Model\Site $site
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setCurrentSite(\F3\TYPO3\Domain\Model\Site $site) {
		$this->currentSite = $site;
	}

	/**
	 * Returns the current site from this frontend context
	 *
	 * @return \F3\TYPO3\Domain\Model\Structure\Domain The current site
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getCurrentDomain() {
		return $this->currentDomain;
	}

	/**
	 * Returns the node of the current site.
	 *
	 * @return \F3\TYPO3CR\Domain\Model\NodeInterface
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCurrentSiteNode() {
		return ($this->currentSite === NULL) ? NULL : $this->getNode('/sites/' . $this->currentSite->getNodeName());
	}
}
?>