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

use TYPO3\Flow\I18n\Locale;

use TYPO3\Flow\Annotations as Flow;

/**
 * The Content Context
 *
 * @Flow\Scope("prototype")
 * @api
 */
class ContentContext extends \TYPO3\TYPO3CR\Domain\Service\Context {

	/**
	 * @var \TYPO3\Neos\Domain\Model\Site
	 */
	protected $currentSite;

	/**
	 * @var \TYPO3\Neos\Domain\Model\Domain
	 */
	protected $currentDomain;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	protected $currentSiteNode;

	/**
	 * @param string $workspaceName
	 * @param \DateTime $currentDateTime
	 * @param \TYPO3\Flow\I18n\Locale $locale
	 * @param boolean $invisibleContentShown
	 * @param boolean $removedContentShown
	 * @param boolean $inaccessibleContentShown
	 * @param \TYPO3\Neos\Domain\Model\Site $currentSite
	 * @param \TYPO3\Neos\Domain\Model\Domain $currentDomain
	 * @return \TYPO3\Neos\Domain\Service\ContentContext
	 */
	public function __construct($workspaceName, \DateTime $currentDateTime, \TYPO3\Flow\I18n\Locale $locale, $invisibleContentShown, $removedContentShown, $inaccessibleContentShown, $currentSite, $currentDomain) {
		$this->workspaceName = $workspaceName;
		$this->currentDateTime = $currentDateTime;
		$this->locale = $locale;
		$this->invisibleContentShown = $invisibleContentShown;
		$this->removedContentShown = $removedContentShown;
		$this->inaccessibleContentShown = $inaccessibleContentShown;
		$this->currentSite = $currentSite;
		$this->currentDomain = $currentDomain;
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
		if ($this->currentSite !== NULL && $this->currentSiteNode === NULL) {
			$this->currentSiteNode = $this->getNode('/sites/' . $this->currentSite->getNodeName());
		}
		return $this->currentSiteNode;
	}

	/**
	 * Returns the properties of this context.
	 *
	 * @return array
	 */
	public function getProperties() {
		return array(
			'workspaceName' => $this->workspaceName,
			'currentDateTime' => $this->currentDateTime,
			'locale' => $this->locale,
			'invisibleContentShown' => $this->invisibleContentShown,
			'removedContentShown' => $this->removedContentShown,
			'inaccessibleContentShown' => $this->inaccessibleContentShown,
			'currentSite' => $this->currentSite,
			'currentDomain' => $this->currentDomain
		);
	}

}
