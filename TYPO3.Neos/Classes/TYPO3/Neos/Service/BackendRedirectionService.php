<?php
namespace TYPO3\Neos\Service;

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
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Session\SessionInterface;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

/**
 * @Flow\Scope("singleton")
 */
class BackendRedirectionService {

	/**
	 * @Flow\Inject
	 * @var SessionInterface
	 */
	protected $session;

	/**
	 * @Flow\Inject
	 * @var Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @Flow\Inject
	 * @var SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @Flow\Inject
	 * @var UserService
	 */
	protected $userService;

	/**
	 * Returns a specific URI string to redirect to after the login; or NULL if there is none.
	 *
	 * @param ActionRequest $actionRequest
	 * @return string
	 */
	public function getAfterLoginRedirectionUri(ActionRequest $actionRequest) {
		$user = $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');
		if ($user === NULL) {
			return NULL;
		}
		$workspaceName = $this->userService->getCurrentWorkspaceName();
		$contentContext = $this->createContext($workspaceName);
		// create workspace if it does not exist
		$contentContext->getWorkspace();
		$this->nodeDataRepository->persistEntities();

		$uriBuilder = new UriBuilder();
		$uriBuilder->setRequest($actionRequest);
		$uriBuilder->setFormat('html');
		$uriBuilder->setCreateAbsoluteUri(TRUE);

		$lastVisitedNode = $this->getLastVisitedNode($contentContext);
		if ($lastVisitedNode !== NULL) {
			return $uriBuilder->uriFor('show', array('node' => $lastVisitedNode), 'Frontend\\Node', 'TYPO3.Neos');
		}

		return $uriBuilder->uriFor('show', array('node' => $contentContext->getCurrentSiteNode()), 'Frontend\\Node', 'TYPO3.Neos');
	}

	/**
	 * Returns a specific URI string to redirect to after the logout; or NULL if there is none.
	 * In case of NULL, it's the responsibility of the AuthenticationController where to redirect,
	 * most likely to the LoginController's index action.
	 *
	 * @param ActionRequest $actionRequest
	 * @return string A possible redirection URI, if any
	 */
	public function getAfterLogoutRedirectionUri(ActionRequest $actionRequest) {
		$contentContext = $this->createContext('live');
		$lastVisitedNode = $this->getLastVisitedNode($contentContext);
		if ($lastVisitedNode === NULL) {
			return NULL;
		}
		$uriBuilder = new UriBuilder();
		$uriBuilder->setRequest($actionRequest);
		$uriBuilder->setFormat('html');
		$uriBuilder->setCreateAbsoluteUri(TRUE);
		return $uriBuilder->uriFor('show', array('node' => $lastVisitedNode), 'Frontend\\Node', 'TYPO3.Neos');
	}

	/**
	 * @param ContentContext $contentContext
	 * @return NodeInterface
	 */
	protected function getLastVisitedNode(ContentContext $contentContext) {
		if (!$this->session->isStarted() || !$this->session->hasKey('lastVisitedNode')) {
			return NULL;
		}
		$lastVisitedNode = $contentContext->getNodeByIdentifier($this->session->getData('lastVisitedNode'));
		return $lastVisitedNode;
	}

	/**
	 * Create a ContentContext to be used for the backend redirects.
	 *
	 * @param string $workspaceName
	 * @return ContentContext
	 */
	protected function createContext($workspaceName) {
		$contextProperties = array(
			'workspaceName' => $workspaceName,
			'invisibleContentShown' => TRUE,
			'inaccessibleContentShown' => TRUE
		);

		$currentDomain = $this->domainRepository->findOneByActiveRequest();
		if ($currentDomain !== NULL) {
			$contextProperties['currentSite'] = $currentDomain->getSite();
			$contextProperties['currentDomain'] = $currentDomain;
		} else {
			$contextProperties['currentSite'] = $this->siteRepository->findFirstOnline();
		}
		return $this->contextFactory->create($contextProperties);
	}
}
