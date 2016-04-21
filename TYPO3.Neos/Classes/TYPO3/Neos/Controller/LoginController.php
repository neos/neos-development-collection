<?php
namespace TYPO3\Neos\Controller;

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
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\Http\Cookie;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\View\JsonView;
use TYPO3\Flow\Security\Authentication\Controller\AbstractAuthenticationController;
use TYPO3\Flow\Security\Exception\AuthenticationRequiredException;
use TYPO3\Flow\Session\SessionInterface;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Service\BackendRedirectionService;

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
     * @var \TYPO3\Flow\Cache\Frontend\StringFrontend
     */
    protected $loginTokenCache;

    /**
     * @Flow\InjectConfiguration(package="TYPO3.Flow", path="session.name")
     * @var string
     */
    protected $sessionName;

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = array(
        'html' => 'TYPO3\Fluid\View\TemplateView',
        'json' => 'TYPO3\Flow\Mvc\View\JsonView'
    );

    /**
     * @var array
     */
    protected $supportedMediaTypes = array(
        'text/html',
        'application/json'
    );

    /**
     * @return void
     */
    public function initializeIndexAction()
    {
        if (is_array($this->request->getInternalArgument('__authentication'))) {
            $authentication = $this->request->getInternalArgument('__authentication');
            if (isset($authentication['TYPO3']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['username'])) {
                $this->request->setArgument('username', $authentication['TYPO3']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['username']);
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
        if ($this->securityContext->getInterceptedRequest() || $unauthorized) {
            $this->response->setStatus(401);
        }
        if ($this->authenticationManager->isAuthenticated()) {
            $this->redirect('index', 'Backend\Backend');
        }
        $currentDomain = $this->domainRepository->findOneByActiveRequest();
        $currentSite = $currentDomain !== null ? $currentDomain->getSite() : $this->siteRepository->findFirstOnline();
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
        $sessionId = $this->loginTokenCache->get($token);
        $this->loginTokenCache->remove($token);

        if ($sessionId === false) {
            $this->systemLogger->log(sprintf('Token-based login failed, non-existing or expired token %s', $token), LOG_WARNING);
            $this->redirect('index');
        } else {
            $this->systemLogger->log(sprintf('Token-based login succeeded, token %s', $token), LOG_DEBUG);
            $this->replaceSessionCookie($sessionId);
            $this->redirect('index', 'Backend\Backend');
        }
    }

    /**
     * Is called if authentication failed.
     *
     * @param AuthenticationRequiredException $exception The exception thrown while the authentication process
     * @return void
     */
    protected function onAuthenticationFailure(AuthenticationRequiredException $exception = null)
    {
        $this->addFlashMessage('The entered username or password was wrong', 'Wrong credentials', Message::SEVERITY_ERROR, array(), ($exception === null ? 1347016771 : $exception->getCode()));
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
            $this->view->assign('value', array('success' => $this->authenticationManager->isAuthenticated(), 'csrfToken' => $this->securityContext->getCsrfProtectionToken()));
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
     * @return void
     */
    public function logoutAction()
    {
        $possibleRedirectionUri = $this->backendRedirectionService->getAfterLogoutRedirectionUri($this->request);
        parent::logoutAction();
        switch ($this->request->getFormat()) {
            case 'json':
                $this->view->assign('value', array('success' => true));
            break;
            default:
                if ($possibleRedirectionUri !== null) {
                    $this->redirectToUri($possibleRedirectionUri);
                }
                $this->addFlashMessage('Successfully logged out', 'Logged out', Message::SEVERITY_NOTICE, array(), 1318421560);
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
