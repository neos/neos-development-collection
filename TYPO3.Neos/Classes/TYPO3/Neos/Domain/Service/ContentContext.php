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
use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use TYPO3\Neos\Domain\Model\Domain;
use TYPO3\Neos\Domain\Model\UserInterfaceMode;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\Context;

/**
 * The Content Context
 *
 * @Flow\Scope("prototype")
 * @api
 */
class ContentContext extends Context {

	/**
	 * @var Site
	 */
	protected $currentSite;

	/**
	 * @var Domain
	 */
	protected $currentDomain;

	/**
	 * @var NodeInterface
	 */
	protected $currentSiteNode;

	/**
	 * @Flow\Inject
	 * @var PrivilegeManagerInterface
	 */
	protected $privilegeManager;

	/**
	 * @Flow\Inject
	 * @var UserInterfaceModeService
	 */
	protected $interfaceRenderModeService;

	/**
	 * Constructor
	 *
	 * @param string $workspaceName
	 * @param \DateTime $currentDateTime
	 * @param array $dimensions
	 * @param array $targetDimensions
	 * @param boolean $invisibleContentShown
	 * @param boolean $removedContentShown
	 * @param boolean $inaccessibleContentShown
	 * @param Site $currentSite
	 * @param Domain $currentDomain
	 * @return ContentContext
	 */
	public function __construct($workspaceName, \DateTime $currentDateTime, array $dimensions, array $targetDimensions, $invisibleContentShown, $removedContentShown, $inaccessibleContentShown, $currentSite, $currentDomain) {
		parent::__construct($workspaceName, $currentDateTime, $dimensions, $targetDimensions, $invisibleContentShown, $removedContentShown, $inaccessibleContentShown);
		$this->currentSite = $currentSite;
		$this->currentDomain = $currentDomain;
		$this->targetDimensions = $targetDimensions;
	}

	/**
	 * Returns the current site from this frontend context
	 *
	 * @return Site The current site
	 */
	public function getCurrentSite() {
		return $this->currentSite;
	}

	/**
	 * Returns the current domain from this frontend context
	 *
	 * @return Domain The current domain
	 * @api
	 */
	public function getCurrentDomain() {
		return $this->currentDomain;
	}

	/**
	 * Returns the node of the current site.
	 *
	 * @return NodeInterface
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
			'dimensions' => $this->dimensions,
			'targetDimensions' => $this->targetDimensions,
			'invisibleContentShown' => $this->invisibleContentShown,
			'removedContentShown' => $this->removedContentShown,
			'inaccessibleContentShown' => $this->inaccessibleContentShown,
			'currentSite' => $this->currentSite,
			'currentDomain' => $this->currentDomain
		);
	}

	/**
	 * Returns TRUE if current context is live workspace, FALSE otherwise
	 *
	 * @return boolean
	 */
	public function isLive() {
		return ($this->getWorkspace()->getBaseWorkspace() === NULL);
	}

	/**
	 * Returns TRUE while rendering backend (not live workspace and access to backend granted), FALSE otherwise
	 *
	 * @return boolean
	 */
	public function isInBackend() {
		return (!$this->isLive() && $this->hasAccessToBackend());
	}

	/**
	 * @return UserInterfaceMode
	 */
	public function getCurrentRenderingMode() {
		return $this->interfaceRenderModeService->findModeByCurrentUser();
	}

	/**
	 * Is access to the neos backend granted by current authentications.
	 *
	 * @return boolean
	 */
	protected function hasAccessToBackend() {
		try {
			return $this->privilegeManager->isPrivilegeTargetGranted('TYPO3.Neos:Backend.GeneralAccess');
		} catch (\TYPO3\Flow\Security\Exception $exception) {
			return FALSE;
		}
	}

}
