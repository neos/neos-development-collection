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
use Neos\Flow\Http\ContentStream;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\AccountRepository;
use Neos\Flow\Session\SessionInterface;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Service\ImpersonateService;
use Neos\Party\Domain\Service\PartyService;

/**
 * The Neos Impersonate controller
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
    protected $defaultViewImplementation = JsonView::class;

    /**
     * @var JsonView
     */
    protected $view = null;

    /**
     * @var array
     */
    protected $supportedMediaTypes = ['application/json'];

    /**
     * @return void
     */
    public function statusAction()
    {
        $this->response = $this->response->withAddedHeader('Content-Type', 'application/json');

        if ($this->impersonateService->isActive()) {
            $this->response = $this->response->withStatus(200);

            $currrentImpersonation = $this->impersonateService->getImpersonation();
            /** @var User $user */
            $user = $this->partyService->getAssignedPartyOfAccount($currrentImpersonation);

            $this->view->setVariablesToRender(['accountIdentifier', 'fullName']);

            $this->view
                ->assign('accountIdentifier', $currrentImpersonation->getAccountIdentifier())
                ->assign('fullName', $user->getName()->getFullName());
        } else {
            $this->response = $this->response->withStatus(404);
        }
    }

    /**
     * @return void
     * @throws StopActionException
     */
    public function undoAction()
    {
        $this->impersonateService->undoImpersonate();
        $this->redirect('index', 'Backend\Backend', 'Neos.Neos', null, 0, 303, 'html');
    }
}
