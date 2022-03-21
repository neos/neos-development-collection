<?php
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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\Security\Account;
use Neos\Neos\Domain\Model\User;
use Neos\Party\Domain\Service\PartyService;
use Neos\Neos\Service\ImpersonateService;

/**
 * The Impersonate controller
 *
 * @Flow\Scope("singleton")
 */
class ImpersonateController extends ActionController
{
    /**
     * @var ImpersonateService
     * @Flow\Inject
     */
    protected $impersonateService;

    /**
     * @var PartyService
     * @Flow\Inject
     */
    protected $partyService;

    /**
     * @var string
     */
    protected $defaultViewObjectName = JsonView::class;

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = [
        'json' => JsonView::class
    ];

    /**
     * @var array
     */
    protected $supportedMediaTypes = [
        'application/json'
    ];

    /**
     * @param Account $account
     * @return void
     */
    public function impersonateAction(Account $account): void
    {
        $this->impersonateService->impersonate($account);
        $this->redirectIfPossible('impersonate');
    }

    /**
     * Fetching possible redirect options for the given action method and if everything is set we redirect to the
     * configured controller action.
     *
     * @param string $actionName
     * @return void
     */
    protected function redirectIfPossible($actionName): void
    {
        $action = $this->settings['redirectOptions'][$actionName]['action'] ?? '';
        $controller = $this->settings['redirectOptions'][$actionName]['controller'] ?? '';
        $package = $this->settings['redirectOptions'][$actionName]['package'] ?? '';

        if ($action !== '' && $controller !== '' && $package !== '' && $this->impersonateService->getImpersonation() === null) {
            $this->redirectWithParentRequest($action, $controller, $package);
        }
    }

    /**
     * @param string $actionName Name of the action to forward to
     * @param string $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
     * @param string $packageKey Key of the package containing the controller to forward to. If not specified, the current package is assumed.
     * @param array $arguments Array of arguments for the target action
     * @param integer $delay (optional) The delay in seconds. Default is no delay.
     * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
     * @param string $format The format to use for the redirect URI
     * @see redirect()
     */
    protected function redirectWithParentRequest($actionName, $controllerName = null, $packageKey = null, array $arguments = [], $delay = 0, $statusCode = 303, $format = null): void
    {
        $request = $this->getControllerContext()->getRequest()->getMainRequest();
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);

        if ($packageKey !== null && strpos($packageKey, '\\') !== false) {
            list($packageKey, $subpackageKey) = explode('\\', $packageKey, 2);
        } else {
            $subpackageKey = null;
        }
        if ($format === null) {
            $uriBuilder->setFormat($this->request->getFormat());
        } else {
            $uriBuilder->setFormat($format);
        }

        $uri = $uriBuilder->setCreateAbsoluteUri(true)->uriFor($actionName, $arguments, $controllerName, $packageKey, $subpackageKey);
        $this->redirectToUri($uri, $delay, $statusCode);
    }

    /**
     * @param User $user
     * @return string
     * @throws \Neos\Flow\Session\Exception\SessionNotStartedException
     */
    public function impersonateUserWithResponseAction(User $user)
    {
        /** @var Account $account */
        $account = $user->getAccounts()->first();
        $this->impersonateService->impersonate($account);
        $impersonateStatus = $this->getImpersonateStatus();
        $this->view->assign('value', $impersonateStatus);
    }

    /**
     * @return void
     * @throws StopActionException
     */
    public function restoreAction()
    {
        $this->impersonateService->restoreOriginalIdentity();
        $this->redirectIfPossible('restore');
    }


    /**
     * @return void
     * @throws StopActionException
     */
    public function restoreWithResponseAction()
    {
        /** @var Account $originalIdentity */
        $originalIdentity = $this->impersonateService->getOriginalIdentity();
        /** @var Account $impersonateIdentity */
        $impersonateIdentity = $this->impersonateService->getImpersonation();

        $response['status'] = false;
        if ($originalIdentity) {
            $response['status'] = true;
            $response['origin'] = [
                'accountIdentifier' => $originalIdentity->getAccountIdentifier(),
            ];
        }

        if ($impersonateIdentity) {
            $response['impersonate'] = [
                'accountIdentifier' => $impersonateIdentity->getAccountIdentifier(),
            ];
        }

        $this->impersonateService->restoreOriginalIdentity();
        $this->view->assign('value', $response);
    }

    /**
     * @return string
     */
    public function statusAction()
    {
        $impersonateStatus = $this->getImpersonateStatus();
        $this->view->assign('value', $impersonateStatus);
    }

    /**
     * @return array
     */
    public function getImpersonateStatus(): array
    {
        $impersonateStatus = [
            'status' => false
        ];

        if ($this->impersonateService->isActive()) {
            $currentImpersonation = $this->impersonateService->getImpersonation();
            $originalIdentity = $this->impersonateService->getOriginalIdentity();
            /** @var User $user */
            $user = $this->partyService->getAssignedPartyOfAccount($currentImpersonation);

            $impersonateStatus['status'] = true;
            $impersonateStatus['user'] = [
                'accountIdentifier' => $currentImpersonation->getAccountIdentifier(),
                'fullName' => $user->getName()->getFullName()
            ];

            if ($originalIdentity) {
                /** @var User $originUser */
                $originUser = $this->partyService->getAssignedPartyOfAccount($originalIdentity);
                $impersonateStatus['origin'] = [
                    'accountIdentifier' => $originalIdentity->getAccountIdentifier(),
                    'fullName' => $originUser->getName()->getFullName()
                ];
            }
        }

        return $impersonateStatus;
    }
}
