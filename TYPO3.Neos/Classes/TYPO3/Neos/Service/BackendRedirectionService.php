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
use TYPO3\Neos\Domain\Service\ContentContext;

/**
 * @Flow\Scope("singleton")
 */
class BackendRedirectionService {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Session\SessionInterface
	 */
	protected $session;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * Returns a specific URI string to redirect to after the login; or NULL if there is none.
	 *
	 * @param \TYPO3\Flow\Http\Request $httpRequest
	 * @return string
	 */
	public function getAfterLoginRedirectionUri(\TYPO3\Flow\Http\Request $httpRequest) {
		$user = $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');
		if ($user === NULL) {
			return '';
		}

		$workspaceName = $user->getPreferences()->get('context.workspace');

		$contentContext = $this->createContext($workspaceName);

		$contentContext->getWorkspace();
		$this->nodeDataRepository->persistEntities();

		if ($this->session->isStarted() && $this->session->hasKey('lastVisitedUri')) {
			$adjustRedirectionUri = $this->adjustRedirectionUriForContentContext($contentContext, $this->session->getData('lastVisitedUri'), $httpRequest);
			if ($adjustRedirectionUri !== FALSE) {
				return $adjustRedirectionUri;
			}
		}

		return $httpRequest->getBaseUri()->getPath() . '@' . $workspaceName . '.html';
	}

	/**
	 * Returns a specific URI string to redirect to after the logout; or NULL if there is none.
	 * In case of NULL, it's the responsibility of the AuthenticationController where to redirect,
	 * most likely to the authentication's index action.
	 *
	 * @param \TYPO3\Flow\Http\Request $httpRequest
	 * @return string A possible redirection URI, if any
	 */
	public function getAfterLogoutRedirectionUri(\TYPO3\Flow\Http\Request $httpRequest) {
		if ($this->session->isStarted() && $this->session->hasKey('lastVisitedUri')) {
			$contentContext = $this->createContext('live');

			$adjustRedirectionUri = $this->adjustRedirectionUriForContentContext($contentContext, $this->session->getData('lastVisitedUri'), $httpRequest);
			if ($adjustRedirectionUri !== FALSE) {
				return $adjustRedirectionUri;
			}
		}

		return '';
	}

	/**
	 * This adjusts a given URI for the use of an intended ContentContext.
	 * Basically, it, depending on the context's workspace name, either strips
	 * or adds the @.... part, which defines a workspace, from the URI.
	 *
	 * @param \TYPO3\Neos\Domain\Service\ContentContext $contentContext
	 * @param string $redirectionUri
	 * @param \TYPO3\Flow\Http\Request $httpRequest
	 * @return string|boolean
	 */
	protected function adjustRedirectionUriForContentContext(ContentContext $contentContext, $redirectionUri, \TYPO3\Flow\Http\Request $httpRequest) {
		$adjustedUri = $redirectionUri;

		$appendHtml = !strpos($adjustedUri, '.html') ? FALSE : TRUE;
		if (!strpos($adjustedUri, '@')) {
			$adjustedUri = str_replace('.html', '', $adjustedUri);
		} else {
			$adjustedUri = substr($adjustedUri, 0, strpos($adjustedUri, '@'));
		}

		$urlParts = parse_url($adjustedUri);
		$baseUri = $httpRequest->getBaseUri()->getPath();
		$targetNodePath = substr($urlParts['path'], strlen($baseUri));

		if ((string)$targetNodePath === '') {
			$targetNodePath = '/';
		}

		if ($urlParts['path'] && is_object($contentContext->getCurrentSiteNode()->getNode($targetNodePath))) {
			if ($contentContext->getWorkspaceName() !== 'live') {
				$adjustedUri .= '@' . $contentContext->getWorkspaceName();
			}
			if ($appendHtml === TRUE && $targetNodePath !== '/') {
				$adjustedUri .= '.html';
			}
			return $adjustedUri;
		}

		return FALSE;
	}

	/**
	 * Create a ContentContext to be used for the backend redirects.
	 *
	 * @param string $workspaceName
	 * @return \TYPO3\TYPO3CR\Domain\Service\ContextInterface
	 */
	protected function createContext($workspaceName) {
		$contextProperties = array(
			'workspaceName' => $workspaceName
		);

		$currentDomain = $this->domainRepository->findOneByActiveRequest();
		if ($currentDomain !== NULL) {
			$contextProperties['currentSite'] = $currentDomain->getSite();
			$contextProperties['currentDomain'] = $currentDomain;
		} else {
			$contextProperties['currentSite'] = $this->siteRepository->findFirst();
		}
		return $this->contextFactory->create($contextProperties);
	}
}
