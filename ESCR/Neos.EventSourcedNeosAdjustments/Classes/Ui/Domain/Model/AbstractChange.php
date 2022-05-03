<?php
declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\Ui\Domain\Model;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorManager;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\NodeCreated;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\ReloadDocument;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\UpdateWorkspaceInfo;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Service\UserService;
use Neos\Neos\Ui\Domain\Model\FeedbackCollection;

abstract class AbstractChange implements ChangeInterface
{
    protected ?NodeInterface $subject;

    /**
     * @Flow\Inject
     * @var FeedbackCollection
     */
    protected $feedbackCollection;

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    public function setSubject(NodeInterface $subject): void
    {
        $this->subject = $subject;
    }

    public function getSubject(): ?NodeInterface
    {
        return $this->subject;
    }

    /**
     * Helper method to inform the client, that new workspace information is available
     */
    protected function updateWorkspaceInfo(): void
    {
        if (!is_null($this->subject)) {
            $documentNode = $this->findClosestDocumentNode($this->subject);
            if (!is_null($documentNode)) {
                $workspace = $this->workspaceFinder->findOneByCurrentContentStreamIdentifier(
                    $documentNode->getContentStreamIdentifier()
                );
                if (!is_null($workspace)) {
                    $updateWorkspaceInfo = new UpdateWorkspaceInfo($workspace->getWorkspaceName());
                    $this->feedbackCollection->add($updateWorkspaceInfo);
                }
            }
        }
    }

    final protected function findClosestDocumentNode(NodeInterface $node): ?NodeInterface
    {
        while ($node instanceof NodeInterface) {
            if ($node->getNodeType()->isOfType('Neos.Neos:Document')) {
                return $node;
            }
            $node = $this->findParentNode($node);
        }

        return null;
    }

    protected function findParentNode(NodeInterface $node): ?NodeInterface
    {
        return $this->nodeAccessorManager->accessorFor(
            $node->getContentStreamIdentifier(),
            $node->getDimensionSpacePoint(),
            $node->getVisibilityConstraints()
        )->findParentNode($node);
    }

    /**
     * Inform the client to reload the currently-displayed document, because the rendering has changed.
     *
     * This method will be triggered if [nodeType].properties.[propertyName].ui.reloadIfChanged is TRUE.
     */
    protected function reloadDocument(NodeInterface $node = null): void
    {
        $reloadDocument = new ReloadDocument();
        if ($node) {
            $reloadDocument->setNode($node);
        }

        $this->feedbackCollection->add($reloadDocument);
    }

    /**
     * Inform the client that a node has been created, the client decides if and which tree should react to this change.
     */
    protected function addNodeCreatedFeedback(NodeInterface $subject = null): void
    {
        $node = $subject ?: $this->getSubject();
        if ($node) {
            $nodeCreated = new NodeCreated();
            $nodeCreated->setNode($node);
            $this->feedbackCollection->add($nodeCreated);
        }
    }

    final protected function getInitiatingUserIdentifier(): UserIdentifier
    {
        /** @var User $user */
        $user = $this->userService->getBackendUser();

        return UserIdentifier::fromString($this->persistenceManager->getIdentifierByObject($user));
    }
}
