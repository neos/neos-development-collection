<?php
declare(strict_types=1);

namespace Neos\Neos\Controller\Backend;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Exception\InvalidDataException;
use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Http\Component\SetHeaderComponent;
use Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException;
use Neos\Flow\I18n\Locale;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\Exception\UnsupportedRequestTypeException;
use Neos\Flow\Mvc\Routing\Exception\MissingActionNameException;
use Neos\Flow\Package\Exception\UnknownPackageException;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Session\Exception\SessionNotStartedException;
use Neos\Flow\Session\SessionInterface;
use Neos\Flow\Utility\Algorithms;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Service\BackendRedirectionService;
use Neos\Neos\Service\LinkingService;
use Neos\Neos\Service\XliffService;

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
     * @throws MissingActionNameException
     * @throws StopActionException
     * @throws UnsupportedRequestTypeException
     * @throws \Neos\Flow\Http\Exception
     * @throws IllegalObjectTypeException
     */
    public function indexAction(): void
    {
        $redirectionUri = $this->backendRedirectionService->getAfterLoginRedirectionUri($this->request);
        if ($redirectionUri === null) {
            $redirectionUri = $this->uriBuilder->uriFor('index', [], 'Login', 'Neos.Neos');
        }
        $this->redirectToUri($redirectionUri);
    }

    /**
     * Redirects to the Neos backend on the given site, passing a one-time login token
     *
     * @param Site $site
     * @return void
     * @throws \Neos\Cache\Exception
     * @throws InvalidDataException
     * @throws StopActionException
     * @throws UnsupportedRequestTypeException
     * @throws MissingActionNameException
     * @throws SessionNotStartedException
     * @throws \Neos\Neos\Exception
     * @throws \Exception
     */
    public function switchSiteAction($site): void
    {
        $token = Algorithms::generateRandomToken(32);
        $this->loginTokenCache->set($token, $this->currentSession->getId());
        $siteUri = $this->linkingService->createSiteUri($this->controllerContext, $site);

        $loginUri = $this->controllerContext->getUriBuilder()
            ->reset()
            ->uriFor('tokenLogin', ['token' => $token], 'Login', 'Neos.Neos');
        $this->redirectToUri($siteUri . $loginUri);
    }

    /**
     * Returns the cached json array with the xliff labels
     *
     * @param string $locale
     * @return string
     * @throws InvalidLocaleIdentifierException
     * @throws \Neos\Cache\Exception
     * @throws UnknownPackageException
     */
    public function xliffAsJsonAction(string $locale): string
    {
        $this->response->setContentType('application/json');
        $this->response->setComponentParameter(SetHeaderComponent::class, 'Cache-Control', 'max-age=' . (3600 * 24 * 7));

        return $this->xliffService->getCachedJson(new Locale($locale));
    }
}
