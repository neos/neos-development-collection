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
 * The Content Context
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @api
 */
class ContentContext {

	/**
	 * @inject
	 * @var F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

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
	 * @var \DateTime
	 */
	protected $currentDateTime;

	/**
	 * @var \F3\TYPO3\Domain\Service\ContentService
	 */
	protected $contentService;

	/**
	 * @var \F3\TYPO3\Domain\Service\NodeService
	 */
	protected $nodeService;

	/**
	 * @var \F3\TYPO3\Domain\Service\TypoScriptService
	 */
	protected $typoScriptService;

	/**
	 * @var \F3\FLOW3\I18n\Locale
	 */
	protected $locale;

	/**
	 * @var \F3\TYPO3\Domain\Model\Structure\Site
	 */
	protected $currentSite;

	/**
	 * @var \F3\TYPO3\Domain\Model\Configuration\Domain
	 */
	protected $currentDomain;

	/**
	 * @var \F3\TYPO3\Domain\Model\Content\Page
	 */
	protected $currentNodeContent;

	/**
	 * @var string
	 */
	protected $nodePath;

	/**
	 * Constructs this content context
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		$this->currentDateTime = new \DateTime();
	}

	/**
	 * Initializes the context after all dependencies have been injected.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		$this->contentService = $this->objectManager->create('F3\TYPO3\Domain\Service\ContentService', $this);
		$this->nodeService = $this->objectManager->create('F3\TYPO3\Domain\Service\NodeService', $this);
		$this->typoScriptService = $this->objectManager->create('F3\TYPO3\Domain\Service\TypoScriptService', $this);
		$this->locale = $this->objectManager->create('F3\FLOW3\I18n\Locale', 'mul-ZZ');

		$matchingDomains = $this->domainRepository->findByHost($this->environment->getHTTPHost());
		if (count ($matchingDomains) > 0) {
			$this->currentDomain = $matchingDomains[0];
			$this->currentSite = $matchingDomains[0]->getSite();
		} else {
			$site = $this->siteRepository->findFirst();
			$this->currentSite = ($site === FALSE) ? NULL : $site;
		}
	}

	/**
	 * Returns the current date and time in form of a \DateTime
	 * object.
	 *
	 * If you use this method for getting the current date and time
	 * everywhere in your code, it will be possible to simulate a certain
	 * time in unit tests or in the actual application (for realizing previews etc).
	 *
	 * @return \DateTime The current date and time - or a simulated version of it
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getCurrentDateTime() {
		return $this->currentDateTime;
	}

	/**
	 * Sets the simulated date and time. This time will then always be returned
	 * by getCurrentDateTime().
	 *
	 * @param \DateTime $currentDateTime A date and time to simulate.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setCurrentDateTime(\DateTime $currentDateTime) {
		$this->currentDateTime = $currentDateTime;
	}

	/**
	 * Returns the content service which is bound to this context.
	 * Only use THIS method for retrieving an instance of the Content Service!
	 *
	 * @return \F3\TYPO3\Domain\Service\ContentService
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getContentService() {
		return $this->contentService;
	}

	/**
	 * Returns the node service which is bound to this context.
	 * Only use THIS method for retrieving an instance of the Node Service!
	 *
	 * @return \F3\TYPO3\Domain\Service\NodeService
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getNodeService() {
		return $this->nodeService;
	}

	/**
	 * Returns the TypoScript service which is bound to this context.
	 * Only use THIS method for retrieving an instance of the TypoScript service!
	 *
	 * @return \F3\TYPO3\Domain\Service\TypoScriptService
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getTypoScriptService() {
		return $this->typoScriptService;
	}

	/**
	 * Returns the locale of this context.
	 *
	 * @return \F3\FLOW3\I18n\Locale
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getLocale() {
		return $this->locale;
	}

	/**
	 * Returns the current site from this frontend context
	 *
	 * @return \F3\TYPO3\Domain\Model\Structure\Site The current site
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getCurrentSite() {
		return $this->currentSite;
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
	 * Sets the current node content object.
	 *
	 * This method is typically called by a route part handler or by some other
	 * part of TYPO3 which wants to mock the "current node content" information.
	 *
	 * @return \F3\TYPO3\Domain\Model\Content\ContentInterface $nodeContent
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setCurrentNodeContent(\F3\TYPO3\Domain\Model\Content\ContentInterface $nodeContent) {
		$this->currentNodeContent = $nodeContent;
	}

	/**
	 * Returns the current node content, for example the page (ie. the Page content object)
	 * from this frontend context
	 *
	 * @return \F3\TYPO3\Domain\Model\Content\Page The current page
	 * @author Robert Lemke <robert@tpyo3.org>
	 * @api
	 */
	public function getCurrentNodeContent() {
		return $this->currentNodeContent;
	}

	/**
	 * Sets the current node path.
	 * This method is typically called by a specialized route part handler.
	 *
	 * @param string $nodePath The current node path, e.g. "homepage/products/typo3"
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setCurrentNodePath($nodePath) {
		$this->nodePath = $nodePath;
	}

	/**
	 * Returns the current node path.
	 *
	 * @return string The current node path, e.g. "homepage/products/typo3"
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getCurrentNodePath() {
		return $this->nodePath;
	}
}
?>