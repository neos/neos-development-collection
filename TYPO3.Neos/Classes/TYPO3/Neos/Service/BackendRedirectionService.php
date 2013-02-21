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
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * Returns a specific URI string to redirect to after the login; or NULL if there is none.
	 *
	 * @return string
	 */
	public function getAfterLoginRedirectionUri() {
		$user = $this->securityContext->getPartyByType('TYPO3\Neos\Domain\Model\User');
		$workspaceName = $user->getPreferences()->get('context.workspace');

			// Hack: Create the workspace if it does not exist yet.
		$contentContext = new ContentContext($workspaceName);
		$contentContext->getWorkspace();
		$this->nodeRepository->setContext($contentContext);

		if ($this->session->hasKey('lastVisitedUri')) {
			return $this->adjustRedirectionUriForContentContext($contentContext, $this->session->getData('lastVisitedUri'));
		}

		return '/@' . $workspaceName . '.html';
	}

	/**
	 * Returns a specific URI string to redirect to after the logout; or NULL if there is none.
	 * In case of NULL, it's the responsibility of the AuthenticationController where to redirect,
	 * most likely to the authentication's index action.
	 *
	 * @return string A possible redirection URI, if any
	 */
	public function getAfterLogoutRedirectionUri() {
		if ($this->session->hasKey('lastVisitedUri')) {
			$contentContext = new ContentContext('live');
			$this->nodeRepository->setContext($contentContext);

			return $this->adjustRedirectionUriForContentContext($contentContext, $this->session->getData('lastVisitedUri'));
		}

		return NULL;
	}

	/**
	 * This adjusts a given URI for the use of an intended ContentContext.
	 * Basically, it, depending on the context's workspace name, either strips
	 * or adds the @.... part, which defines a workspace, from the URI.
	 *
	 * @param \TYPO3\Neos\Domain\Service\ContentContext $contentContext
	 * @param string $redirectionUri
	 * @return string
	 */
	protected function adjustRedirectionUriForContentContext(ContentContext $contentContext, $redirectionUri) {
		$adjustedUri = $redirectionUri;

		$appendHtml = !strpos($adjustedUri, '.html') ? FALSE : TRUE;
		if (!strpos($adjustedUri, '@')) {
			$adjustedUri = str_replace('.html', '', $adjustedUri);
		} else {
			$adjustedUri = substr($adjustedUri, 0, strpos($adjustedUri, '@'));
		}

		$urlParts = parse_url($adjustedUri);
		$targetNodePath = ($urlParts['path'] === '/' ? '/' : substr($urlParts['path'], 1));
		if ($urlParts['path'] && is_object($contentContext->getCurrentSiteNode()->getNode($targetNodePath))) {
			if ($contentContext->getWorkspaceName() !== 'live') {
				$adjustedUri .= '@' . $contentContext->getWorkspaceName();
			}
			if ($appendHtml === TRUE) {
				$adjustedUri .= '.html';
			}

			return $adjustedUri;
		}

		return $redirectionUri;
	}
}

?>