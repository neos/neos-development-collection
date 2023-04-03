<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Features;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdsToPublishOrDiscard;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The workspace publishing feature trait for behavioral tests
 */
trait WorkspacePublishing
{
    abstract protected function getContentRepository(): ContentRepository;
    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    /**
     * @Given /^the command PublishIndividualNodesFromWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandPublishIndividualNodesFromWorkspaceIsExecuted(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $nodesToPublish = NodeIdsToPublishOrDiscard::fromArray($commandArguments['nodesToPublish']);

        $contentStreamIdForMatchingPart = isset($commandArguments['contentStreamIdForMatchingPart'])
            ? ContentStreamId::fromString($commandArguments['contentStreamIdForMatchingPart'])
            : ContentStreamId::create();

        $contentStreamIdForRemainingPart = isset($commandArguments['contentStreamIdForRemainingPart'])
            ? ContentStreamId::fromString($commandArguments['contentStreamIdForRemainingPart'])
            : ContentStreamId::create();

        $command = PublishIndividualNodesFromWorkspace::createFullyDeterministic(
            WorkspaceName::fromString($commandArguments['workspaceName']),
            $nodesToPublish,
            $contentStreamIdForMatchingPart,
            $contentStreamIdForRemainingPart
        );

        $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);;
    }

    /**
     * @Given /^the command PublishWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandPublishWorkspaceIsExecuted(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);

        $command = new PublishWorkspace(
            WorkspaceName::fromString($commandArguments['workspaceName']),
        );

        $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);;
    }

    /**
     * @Given /^the command PublishWorkspace is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandPublishWorkspaceIsExecutedAndExceptionsAreCaught(TableNode $payloadTable): void
    {
        try {
            $this->theCommandPublishWorkspaceIsExecuted($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }
}
