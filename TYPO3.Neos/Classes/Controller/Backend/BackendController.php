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
use TYPO3\Flow\Cache\Frontend\StringFrontend;
use TYPO3\Flow\I18n\Locale;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Session\SessionInterface;
use TYPO3\Flow\Utility\Algorithms;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\Neos\Service\BackendRedirectionService;
use TYPO3\Neos\Service\LinkingService;
use TYPO3\Neos\Service\XliffService;

/**
 * The Neos Backend controller
 *
 * @Flow\Scope("singleton")
 */
class BackendController extends ActionController
{

    /**
     * @Flow\Inject
     * @var BackendRedirectionService
     */
    protected $backendRedirectionService;

    /**
     * @Flow\Inject
     * @var XliffService
     */
    protected $xliffService;

    /**
     * @Flow\Inject
     * @var LinkingService
     */
    protected $linkingService;

    /**
     * @Flow\Inject
     * @var StringFrontend
     */
    protected $loginTokenCache;

    /**
     * @Flow\Inject
     * @var SessionInterface
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
     * Redirects to the Neos backend on the given site, passing a one-time login token
     *
     * @param Site $site
     * @return void
     */
    public function switchSiteAction($site)
    {
        $token = Algorithms::generateRandomToken(32);
        $this->loginTokenCache->set($token, $this->currentSession->getId());
        $siteUri = $this->linkingService->createSiteUri($this->controllerContext, $site);

        $loginUri = $this->controllerContext->getUriBuilder()
            ->reset()
            ->uriFor('tokenLogin', ['token' => $token], 'Login', 'TYPO3.Neos');
        $this->redirectToUri($siteUri . $loginUri);
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
