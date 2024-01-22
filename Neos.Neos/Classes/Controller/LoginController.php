<?php

declare(strict_types=1);

namespace Neos\Neos\Controller;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Error\Messages\Message;
use Neos\Flow\Http\Cookie;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Exception\InvalidFlashMessageConfigurationException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\FlashMessage\FlashMessageService;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\Security\Authentication\Controller\AbstractAuthenticationController;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Security\Exception\InvalidRequestPatternException;
use Neos\Flow\Security\Exception\NoRequestPatternFoundException;
use Neos\Flow\Session\Exception\SessionNotStartedException;
use Neos\Flow\Session\SessionInterface;
use Neos\Flow\Session\SessionManagerInterface;
use Neos\Fusion\View\FusionView;
use Neos\Neos\Controller\TranslationTrait;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Service\BackendRedirectionService;

/**
 * A controller which allows for logging into the backend
 */
class LoginController extends AbstractAuthenticationController
{
    use TranslationTrait;

    /**
     * @var string
     */
    protected $defaultViewObjectName = FusionView::class;

    #[Flow\Inject]
    protected SessionInterface $session;

    #[Flow\Inject]
    protected SessionManagerInterface $sessionManager;

    #[Flow\Inject]
    protected BackendRedirectionService $backendRedirectionService;

    #[Flow\Inject]
    protected DomainRepository $domainRepository;

    #[Flow\Inject]
    protected SiteRepository $siteRepository;

    #[Flow\Inject]
    protected FlashMessageService $flashMessageService;

    /**
     * @Flow\Inject
     * @var StringFrontend
     */
    protected $loginTokenCache;

    /**
     * @Flow\InjectConfiguration(package="Neos.Flow", path="session.name")
     * @var string
     */
    protected $sessionName;

    /**
     * @var array<string,class-string>
     */
    protected $viewFormatToObjectNameMap = [
        'html' => FusionView::class,
        'json' => JsonView::class,
    ];

    /**
     * @var array<int,string>
     */
    protected $supportedMediaTypes = [
        'text/html',
        'application/json',
    ];

    /**
     * @return void
     */
    public function initializeIndexAction(): void
    {
        /** @var array<string,mixed>|string|object|null $authenticationArgument */
        $authenticationArgument = $this->request->getInternalArgument('__authentication');
        if (is_array($authenticationArgument)) {
            if (
                isset($authenticationArgument['Neos']['Flow']['Security']['Authentication']
                    ['Token']['UsernamePassword']['username'])
            ) {
                $this->request->setArgument(
                    'username',
                    $authenticationArgument['Neos']['Flow']['Security']['Authentication']
                    ['Token']['UsernamePassword']['username']
                );
            }
        }
    }

    /**
     * Default action, displays the login screen
     *
     * @param string|null $username Optional: A username to pre-fill into the username field
     * @param bool $unauthorized
     * @return void
     * @throws InvalidFlashMessageConfigurationException
     * @throws InvalidRequestPatternException
     * @throws NoRequestPatternFoundException
     * @throws StopActionException
     * @throws \Neos\Neos\Domain\Exception
     */
    public function indexAction(?string $username = null, bool $unauthorized = false): void
    {
        if ($unauthorized || $this->securityContext->getInterceptedRequest()) {
            $this->response->setHttpHeader('X-Authentication-Required', '1');
        }
        if ($this->authenticationManager->isAuthenticated()) {
            $this->redirect('index', 'Backend\Backend');
        }
        $currentDomain = $this->domainRepository->findOneByActiveRequest();
        $currentSite = $currentDomain !== null ? $currentDomain->getSite() : $this->siteRepository->findDefault();

        $this->view->assignMultiple([
            'styles' => array_filter($this->settings['userInterface']['backendLoginForm']['stylesheets']),
            'username' => $username,
            'site' => $currentSite,
            'flashMessages' => $this->flashMessageService->getFlashMessageContainerForRequest($this->request)
                ->getMessagesAndFlush(),
        ]);
    }

    /**
     * Logs a user in if a session identifier is available under the given token in the token cache.
     *
     * @param string $token
     * @return void
     * @throws StopActionException
     * @throws SessionNotStartedException
     */
    public function tokenLoginAction(string $token): void
    {
        /** @var string|false $newSessionId */
        $newSessionId = $this->loginTokenCache->get($token);
        $this->loginTokenCache->remove($token);

        if ($newSessionId === false) {
            $this->logger->warning(sprintf('Token-based login failed, non-existing or expired token %s', $token));
            $this->redirect('index');
        }

        $this->logger->debug(sprintf('Token-based login succeeded, token %s', $token));

        $newSession = $this->sessionManager->getSession($newSessionId);
        if ($newSession?->canBeResumed()) {
            $newSession->resume();
        }
        if (!$newSession?->isStarted()) {
            $this->logger->error(sprintf(
                'Failed resuming or starting session %s which was referred to in the login token %s.',
                $newSessionId,
                $token
            ));
        }

        $this->replaceSessionCookie($newSessionId);
        $this->redirect('index', 'Backend\Backend');
    }

    /**
     * Is called if authentication failed.
     *
     * @param AuthenticationRequiredException $exception The exception thrown while the authentication process
     * @return void
     */
    protected function onAuthenticationFailure(AuthenticationRequiredException $exception = null): void
    {
        if ($this->view instanceof JsonView) {
            $this->view->assign('value', ['success' => false]);
        } else {
            $this->addFlashMessage(
                $this->getLabel('login.wrongCredentials.body'),
                $this->getLabel('login.wrongCredentials.title'),
                Message::SEVERITY_ERROR,
                [],
                $exception === null ? 1347016771 : $exception->getCode()
            );
        }
    }

    /**
     * Is called if authentication was successful.
     *
     * @param ActionRequest|null $originalRequest The request that was intercepted by the security framework,
     *                                            NULL if there was none
     * @throws SessionNotStartedException
     * @throws StopActionException
     * @throws \Neos\Flow\Mvc\Exception\NoSuchArgumentException
     */
    protected function onAuthenticationSuccess(ActionRequest $originalRequest = null): null
    {
        if ($this->view instanceof JsonView) {
            $this->view->assign(
                'value',
                [
                    'success' => $this->authenticationManager->isAuthenticated(),
                    'csrfToken' => $this->securityContext->getCsrfProtectionToken()
                ]
            );
            return null;
        } else {
            if ($originalRequest !== null) {
                // Redirect to the location that redirected to the login form because the user was nog logged in
                $this->redirectToRequest($originalRequest);
            }

            $this->redirect('index', 'Backend\Backend');
        }
    }

    /**
     * Logs out a - possibly - currently logged in account.
     * The possible redirection URI is queried from the redirection service
     * at first, before the actual logout takes place, and the session gets destroyed.
     *
     * @Flow\SkipCsrfProtection
     *
     * @return void
     */
    public function logoutAction(): void
    {
        parent::logoutAction();
        switch ($this->request->getFormat()) {
            case 'json':
                $this->view->assign('value', ['success' => true]);
                break;
            default:
                $this->addFlashMessage(
                    $this->getLabel('login.loggedOut.body'),
                    $this->getLabel('login.loggedOut.title'),
                    Message::SEVERITY_NOTICE,
                    [],
                    1318421560
                );
                $this->redirect('index');
        }
    }

    /**
     * Disable the default error flash message
     *
     */
    protected function getErrorFlashMessage(): false
    {
        return false;
    }

    /**
     * Sets the session cookie to the given identifier, overriding an existing cookie.
     *
     * @param string $sessionIdentifier
     * @return void
     */
    protected function replaceSessionCookie(string $sessionIdentifier): void
    {
        $sessionCookie = new Cookie($this->sessionName, $sessionIdentifier);
        $this->response->setCookie($sessionCookie);
    }
}
