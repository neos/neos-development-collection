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
 * The Frontend Content Context
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class FrontendContentContext extends \F3\TYPO3\Domain\Service\ContentContext {

	/**
	 * @inject
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @inject
	 * @var \F3\TYPO3\Domain\Repository\Structure\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @inject
	 * @var F3\TYPO3\Domain\Repository\Configuration\DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @var \F3\TYPO3\Domain\Model\Structure\Site
	 */
	protected $currentSite;

	/**
	 * @var \F3\TYPO3\Domain\Model\Configuration\Domain
	 */
	protected $currentDomain;

	/**
	 * Does further initialization for the frontend context
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		parent::initializeObject();

		$matchingDomains = $this->domainRepository->findByHost($this->environment->getHTTPHost());
		if (count ($matchingDomains) > 0) {
			$this->currentDomain = $matchingDomains[0];
			$this->currentSite = $matchingDomains[0]->getSite();
		} else {
			$sites = $this->siteRepository->findAll();
			if (count ($sites) > 0) {
				$this->currentSite = $sites[0];
			}
		}
	}

	/**
	 * Returns the current site from this frontend context
	 *
	 * @return \F3\TYPO3\Domain\Model\Structure\Site The current site
	 */
	public function getCurrentSite() {
		return $this->currentSite;
	}

	/**
	 * Returns the current site from this frontend context
	 *
	 * @return \F3\TYPO3\Domain\Model\Structure\Domain The current site
	 */
	public function getCurrentDomain() {
		return $this->currentDomain;
	}
}
?>