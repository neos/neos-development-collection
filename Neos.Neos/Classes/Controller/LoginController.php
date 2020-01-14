<?php
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
use Neos\Flow\Http\Component\SetHeaderComponent;
use Neos\Flow\Http\Cookie;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\Security\Authentication\Controller\AbstractAuthenticationController;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Session\SessionInterface;
use Neos\Flow\Session\SessionManagerInterface;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Service\BackendRedirectionService;

/**
 * A controller which allows for logging into the backend
 */
class LoginController extends AbstractAuthenticationController
{

    /**
     * @Flow\Inject
     * @var SessionInterface
     */
    protected $session;

    /**
     * @Flow\Inject
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @Flow\Inject
     * @var BackendRedirectionService
     */
    protected $backendRedirectionService;

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
     * @var StringFrontend
     */
    protected $loginTokenCache;

    /**
     * @Flow\InjectConfiguration(package="Neos.Flow", path="session.name")
     * @var string
     */
    protected $sessionName;

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = [
        'html' => TemplateView::class,
        'json' => JsonView::class
    ];

    /**
     * @var array
     */
    protected $supportedMediaTypes = [
        'text/html',
        'application/json'
    ];

    /**
     * @return void
     */
    public function initializeIndexAction()
    {
        if (is_array($this->request->getInternalArgument('__authentication'))) {
            $authentication = $this->request->getInternalArgument('__authentication');
            if (isset($authentication['Neos']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['username'])) {
                $this->request->setArgument('username', $authentication['Neos']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['username']);
            }
        }
    }

    /**
     * Default action, displays the login screen
     *
     * @param string $username Optional: A username to pre-fill into the username field
     * @param boolean $unauthorized
     * @return void
     */
    public function indexAction($username = null, $unauthorized = false)
    {
        if ($unauthorized || $this->securityContext->getInterceptedRequest()) {
            $this->response->setComponentParameter(SetHeaderComponent::class, 'X-Authentication-Required', '1');
        }
        if ($this->authenticationManager->isAuthenticated()) {
            $this->redirect('index', 'Backend\Backend');
        }
        $currentDomain = $this->domainRepository->findOneByActiveRequest();
        $currentSite = $currentDomain !== null ? $currentDomain->getSite() : $this->siteRepository->findDefault();
        $this->view->assignMultiple([
            'styles' => array_filter($this->settings['userInterface']['backendLoginForm']['stylesheets']),
            'username' => $username,
            'site' => $currentSite
        ]);
    }

    /**
     * Logs a user in if a session identifier is available under the given token in the token cache.
     *
     * @param string $token
     * @return void
     */
    public function tokenLoginAction($token)
    {
        $newSessionId = $this->loginTokenCache->get($token);
        $this->loginTokenCache->remove($token);

        if ($newSessionId === false) {
            $this->logger->warning(sprintf('Token-based login failed, non-existing or expired token %s', $token));
            $this->redirect('index');
        }

        $this->logger->debug(sprintf('Token-based login succeeded, token %s', $token));

        $newSession = $this->sessionManager->getSession($newSessionId);
        if ($newSession->canBeResumed()) {
            $newSession->resume();
        }
        if ($newSession->isStarted()) {
            $newSession->putData('lastVisitedNode', null);
        } else {
            $this->logger->error(sprintf('Failed resuming or starting session %s which was referred to in the login token %s.', $newSessionId, $token));
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
    protected function onAuthenticationFailure(AuthenticationRequiredException $exception = null)
    {
        if ($this->view instanceof JsonView) {
            $this->view->assign('value', ['success' => false]);
        } else {
            $this->addFlashMessage('The entered username or password was wrong', 'Wrong credentials', Message::SEVERITY_ERROR, [], ($exception === null ? 1347016771 : $exception->getCode()));
        }
    }

    /**
     * Is called if authentication was successful.
     *
     * @param ActionRequest $originalRequest The request that was intercepted by the security framework, NULL if there was none
     * @return void
     */
    protected function onAuthenticationSuccess(ActionRequest $originalRequest = null)
    {
        if ($this->view instanceof JsonView) {
            $this->view->assign('value', ['success' => $this->authenticationManager->isAuthenticated(), 'csrfToken' => $this->securityContext->getCsrfProtectionToken()]);
        } else {
            if ($this->request->hasArgument('lastVisitedNode') && strlen($this->request->getArgument('lastVisitedNode')) > 0) {
                $this->session->putData('lastVisitedNode', $this->request->getArgument('lastVisitedNode'));
            }
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
    public function logoutAction()
    {
        $possibleRedirectionUri = $this->backendRedirectionService->getAfterLogoutRedirectionUri($this->request);
        parent::logoutAction();
        switch ($this->request->getFormat()) {
            case 'json':
                $this->view->assign('value', ['success' => true]);
            break;
            default:
                if ($possibleRedirectionUri !== null) {
                    $this->redirectToUri($possibleRedirectionUri);
                }
                $this->addFlashMessage('Successfully logged out', 'Logged out', Message::SEVERITY_NOTICE, [], 1318421560);
                $this->redirect('index');
        }
    }

    /**
     * Disable the default error flash message
     *
     * @return boolean
     */
    protected function getErrorFlashMessage()
    {
        return false;
    }

    /**
     * Sets the session cookie to the given identifier, overriding an existing cookie.
     *
     * @param string $sessionIdentifier
     * @return void
     */
    protected function replaceSessionCookie($sessionIdentifier)
    {
        $sessionCookie = new Cookie($this->sessionName, $sessionIdentifier);
        $this->response->setCookie($sessionCookie);
    }
}
