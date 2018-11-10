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

use Neos\Diff\Diff;
use Neos\Diff\Renderer\Html\HtmlArrayRenderer;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Error\Messages\Message;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\AccountRepository;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\AccessDeniedException;
use Neos\Flow\Session\SessionInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ContentContextFactory;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Domain\Service\SiteService;
use Neos\Neos\Service\ImpersonateService;
use Neos\Neos\Service\PublishingService;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Exception\WorkspaceException;
use Neos\ContentRepository\TypeConverter\NodeConverter;
use Neos\ContentRepository\Utility;
use Neos\Neos\Utility\User as UserUtility;

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
