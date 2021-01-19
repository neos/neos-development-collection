<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Ui\Domain\Model\Feedback\Operations;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedNeosAdjustments\Ui\ContentRepository\Service\WorkspaceService;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\Neos\Ui\Domain\Model\AbstractFeedback;
use Neos\Neos\Ui\Domain\Model\FeedbackInterface;
use Neos\Flow\Mvc\Controller\ControllerContext;

class UpdateWorkspaceInfo extends AbstractFeedback
{
    /**
     * @var WorkspaceName
     */
    protected $workspaceName;

    /**
     * @Flow\Inject
     * @var WorkspaceService
     */
    protected $workspaceService;

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * UpdateWorkspaceInfo constructor.
     *
     * @param WorkspaceName $workspaceName
     */
    public function __construct(WorkspaceName $workspaceName = null)
    {
        $this->workspaceName = $workspaceName;
    }

    /**
     * Set the workspace
     *
     * @param Workspace $workspace
     * @return void
     * @deprecated
     */
    public function setWorkspace(Workspace $workspace)
    {
        $this->workspaceName = new WorkspaceName($workspace->getName());
    }

    /**
     * Getter for WorkspaceName
     *
     * @return WorkspaceName
     */
    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    /**
     * Get the type identifier
     *
     * @return string
     */
    public function getType()
    {
        return 'Neos.Neos.Ui:UpdateWorkspaceInfo';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return sprintf('New workspace info available.');
    }

    /**
     * Checks whether this feedback is similar to another
     *
     * @param FeedbackInterface $feedback
     * @return boolean
     */
    public function isSimilarTo(FeedbackInterface $feedback)
    {
        if (!$feedback instanceof UpdateWorkspaceInfo) {
            return false;
        }

        return (string)$this->getWorkspaceName() === (string)$feedback->getWorkspaceName();
    }

    /**
     * Serialize the payload for this feedback
     *
     * @param ControllerContext $controllerContext
     * @return mixed
     */
    public function serializePayload(ControllerContext $controllerContext)
    {
        $workspace = $this->workspaceFinder->findOneByName($this->workspaceName);
        return [
            'name' => (string)$this->workspaceName,
            'publishableNodes' => $this->workspaceService->getPublishableNodeInfo(
                $this->workspaceName
            ),
            'baseWorkspace' => (string)$workspace->getBaseWorkspaceName()
        ];
    }
}
