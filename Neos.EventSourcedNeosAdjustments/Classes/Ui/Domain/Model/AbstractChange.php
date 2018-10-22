<?php

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

use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations\UpdateWorkspaceInfo;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Ui\ContentRepository\Service\NodeService;
use Neos\Neos\Ui\Domain\Model\Feedback\Operations\NodeCreated;
use Neos\Neos\Ui\Domain\Model\Feedback\Operations\ReloadDocument;
use Neos\Neos\Ui\Domain\Model\FeedbackCollection;

abstract class AbstractChange implements ChangeInterface
{
    /**
     * @var NodeInterface
     */
    protected $subject;

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
     * Set the subject
     *
     * @param NodeInterface $subject
     * @return void
     */
    public function setSubject(NodeInterface $subject)
    {
        $this->subject = $subject;
    }

    /**
     * Get the subject
     *
     * @return NodeInterface
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Helper method to inform the client, that new workspace information is available
     *
     * @return void
     */
    protected function updateWorkspaceInfo()
    {
        $flowQuery = new FlowQuery([$this->getSubject()]);
        $documentNode = $flowQuery->closest('[instanceof Neos.Neos:Document]')->get(0);

        $workspace = $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($documentNode->getContentStreamIdentifier());
        $updateWorkspaceInfo = new UpdateWorkspaceInfo($workspace->getWorkspaceName());

        $this->feedbackCollection->add($updateWorkspaceInfo);
    }

    /**
     * Inform the client to reload the currently-displayed document, because the rendering has changed.
     *
     * This method will be triggered if [nodeType].properties.[propertyName].ui.reloadIfChanged is TRUE.
     *
     * @param NodeInterface $node
     * @return void
     */
    protected function reloadDocument($node = null)
    {
        $reloadDocument = new ReloadDocument();
        if ($node) {
            $reloadDocument->setNode($node);
        }

        $this->feedbackCollection->add($reloadDocument);
    }

    /**
     * Inform the client that a node has been created, the client decides if and which tree should react to this change.
     *
     * @param NodeInterface $subject
     * @return void
     */
    protected function addNodeCreatedFeedback($subject = null)
    {
        $node = $subject ?: $this->getSubject();
        $nodeCreated = new NodeCreated();
        $nodeCreated->setNode($node);
        $this->feedbackCollection->add($nodeCreated);
    }
}
