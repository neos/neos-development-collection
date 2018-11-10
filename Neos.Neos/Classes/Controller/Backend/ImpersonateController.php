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
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\AccountRepository;
use Neos\Flow\Session\SessionInterface;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Service\ImpersonateService;

/**
 * The Neos Workspaces module controller
 *
 * @Flow\Scope("singleton")
 */
class ImpersonateController extends AbstractModuleController
{
    /**
     * @var AccountRepository
     * @Flow\Inject
     */
    protected $accountRepository;

    /**
     * @var ImpersonateService
     * @Flow\Inject
     */
    protected $impersonateService;

    /**
     * @var SessionInterface
     * @Flow\Inject
     */
    protected $session;

    /**
     * @var PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * @param Account $account
     * @return void
     */
    public function enableAction(Account $account)
    {
        $this->impersonateService->impersonate($account);
        $this->redirect('index');
    }

    /**
     * @return void
     */
    public function resetAction()
    {
        $this->impersonateService->undoImpersonate();
        $this->redirect('index');
    }
}
