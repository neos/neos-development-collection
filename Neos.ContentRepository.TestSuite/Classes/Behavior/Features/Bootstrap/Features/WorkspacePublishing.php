<?php

/*
 * This file is part of the Neos.ContentRepository.TestSuite package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Features;

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdsToPublishOrDiscard;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;

/**
 * The workspace publishing feature trait for behavioral tests
 */
trait WorkspacePublishing
{
    use CRTestSuiteRuntimeVariables;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    /**
     * @Given /^the command PublishIndividualNodesFromWorkspace is executed with payload:$/
     * @throws \Exception
     */
    public function theCommandPublishIndividualNodesFromWorkspaceIsExecuted(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $nodesToPublish = NodeIdsToPublishOrDiscard::fromArray($commandArguments['nodesToPublish']);

        $command = PublishIndividualNodesFromWorkspace::create(
            array_key_exists('workspaceName', $commandArguments)
                ? WorkspaceName::fromString($commandArguments['workspaceName'])
                : $this->currentWorkspaceName,
            $nodesToPublish,
        );
        if (isset($commandArguments['contentStreamIdForMatchingPart'])) {
            $command = $command->withContentStreamIdForMatchingPart(ContentStreamId::fromString($commandArguments['contentStreamIdForMatchingPart']));
        }
        if (isset($commandArguments['contentStreamIdForRemainingPart'])) {
            $command = $command->withContentStreamIdForRemainingPart(ContentStreamId::fromString($commandArguments['contentStreamIdForRemainingPart']));
        }

        $this->lastCommandOrEventResult = $this->currentContentRepository->handle($command);
    }

    /**
     * @Given /^the command PublishIndividualNodesFromWorkspace is executed with payload and exceptions are caught:$/
     */
    public function theCommandPublishIndividualNodesFromWorkspaceIsExecutedAndExceptionsAreCaught(TableNode $payloadTable): void
    {
        try {
            $this->theCommandPublishIndividualNodesFromWorkspaceIsExecuted($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @Given /^the command PublishWorkspace is executed with payload:$/
     * @throws \Exception
     */
    public function theCommandPublishWorkspaceIsExecuted(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);

        $command = PublishWorkspace::create(
            WorkspaceName::fromString($commandArguments['workspaceName']),
        );
        if (array_key_exists('newContentStreamId', $commandArguments)) {
            $command = $command->withNewContentStreamId(
                ContentStreamId::fromString($commandArguments['newContentStreamId'])
            );
        }

        $this->lastCommandOrEventResult = $this->currentContentRepository->handle($command);
    }

    /**
     * @Given /^the command PublishWorkspace is executed with payload and exceptions are caught:$/
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
