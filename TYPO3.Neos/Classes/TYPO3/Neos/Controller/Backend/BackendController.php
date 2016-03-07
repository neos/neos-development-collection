<?php
namespace TYPO3\Neos\Controller\Backend;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\I18n\Locale;
use TYPO3\Flow\Utility\Algorithms;

/**
 * The Neos Backend controller
 *
 * @Flow\Scope("singleton")
 */
class BackendController extends \TYPO3\Flow\Mvc\Controller\ActionController
{

    /**
     * @Flow\Inject
     * @var \TYPO3\Neos\Service\BackendRedirectionService
     */
    protected $backendRedirectionService;

    /**
     * @Flow\Inject
     * @var \TYPO3\Neos\Service\XliffService
     */
    protected $xliffService;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Cache\Frontend\StringFrontend
     */
    protected $loginTokenCache;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Session\SessionInterface
     */
    protected $currentSession;

    /**
     * Default action of the backend controller.
     *
     * @return void
     */
    public function indexAction()
    {
        $redirectionUri = $this->backendRedirectionService->getAfterLoginRedirectionUri($this->request);
        if ($redirectionUri === null) {
            $redirectionUri = $this->uriBuilder->uriFor('index', array(), 'Login', 'TYPO3.Neos');
        }
        $this->redirectToUri($redirectionUri);
    }

    /**
     * Redirects to the Neos backend on the given hostname, passing a one-time login token
     *
     * @param string $hostname
     * @return void
     */
    public function switchSiteAction($hostname)
    {
        $token = Algorithms::generateRandomToken(32);
        $this->loginTokenCache->set($token, $this->currentSession->getId());

        $requestUri = $this->controllerContext->getRequest()->getHttpRequest()->getUri();
        $baseUri = $this->controllerContext->getRequest()->getHttpRequest()->getBaseUri();

        $uri = $this->controllerContext->getUriBuilder()
            ->reset()
            ->uriFor('tokenLogin', ['token' => $token], 'Login', 'TYPO3.Neos');
        $uri = sprintf('%s://%s%s%s',
            $requestUri->getScheme(),
            $hostname,
            rtrim($baseUri->getPath(), '/'), // remove trailing slash, $uri has leading slash already
            $uri
        );

        $this->redirectToUri($uri);
    }

    /**
     * Returns the cached json array with the xliff labels
     *
     * @param string $locale
     * @return string
     */
    public function xliffAsJsonAction($locale)
    {
        $this->response->setHeader('Content-Type', 'application/json');

        return $this->xliffService->getCachedJson(new Locale($locale));
    }
}
