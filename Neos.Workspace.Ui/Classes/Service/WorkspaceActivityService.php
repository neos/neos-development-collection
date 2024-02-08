<?php

/*
 * This file is part of the Neos.Workspace.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security\Context;
use Psr\Log\LoggerInterface;

/**
 * TODO: Reimplement with new API
 */
#[Flow\Scope('singleton')]
class WorkspaceActivityService
{
//    #[Flow\Inject]
//    protected WorkspaceDetailsRepository $workspaceDetailsRepository;

    #[Flow\Inject]
    protected Context $securityContext;

    #[Flow\Inject]
    protected LoggerInterface $systemLogger;

    #[Flow\Inject]
    protected PersistenceManagerInterface $persistenceManager;

    /**
     * @var array<string, boolean>
     */
    protected array $updatedWorkspaces = [];

    //public function nodePublished(NodeInterface $node, Workspace $targetWorkspace = null): void
    //{
    //    if (!$targetWorkspace) {
    //        return;
    //    }
    //    $this->updatedWorkspaces[$targetWorkspace->getName()] = $targetWorkspace;
    //}
    //
    //public function nodeDiscarded(NodeInterface $node): void
    //{
    //    $this->updatedWorkspaces[$node->getWorkspace()->getName()] = $node->getWorkspace();
    //}
    //
    //public function shutdownObject(): void
    //{
    //    $currentUser = $this->securityContext->getAccount()->getAccountIdentifier();
    //
    //    foreach ($this->updatedWorkspaces as $updatedWorkspace) {
    //        $workspaceDetails = $this->workspaceDetailsRepository->findOneByWorkspace($updatedWorkspace);
    //
    //        if ($workspaceDetails) {
    //            $workspaceDetails->setLastChangedDate(new \DateTime());
    //            $workspaceDetails->setLastChangedBy($currentUser);
    //            $this->workspaceDetailsRepository->update($workspaceDetails);
    //        } else {
    //            $workspaceDetails = new WorkspaceDetails($updatedWorkspace, null, new \DateTime(), $currentUser);
    //            $this->workspaceDetailsRepository->add($workspaceDetails);
    //        }
    //    }
    //
    //    $this->persistenceManager->persistAll();
    //}
}
