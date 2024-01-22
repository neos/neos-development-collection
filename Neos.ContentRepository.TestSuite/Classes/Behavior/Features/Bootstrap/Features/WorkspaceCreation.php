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
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use Neos\EventStore\Model\Event\StreamName;

/**
 * The workspace creation feature trait for behavioral tests
 */
trait WorkspaceCreation
{
    use CRTestSuiteRuntimeVariables;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    abstract protected function publishEvent(string $eventType, StreamName $streamName, array $eventPayload): void;

    /**
     * @When /^the command CreateRootWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandCreateRootWorkspaceIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);

        $command = CreateRootWorkspace::create(
            WorkspaceName::fromString($commandArguments['workspaceName']),
            new WorkspaceTitle($commandArguments['workspaceTitle'] ?? ucfirst($commandArguments['workspaceName'])),
            new WorkspaceDescription($commandArguments['workspaceDescription'] ?? 'The workspace "' . $commandArguments['workspaceName'] . '"'),
            ContentStreamId::fromString($commandArguments['newContentStreamId'])
        );

        $this->lastCommandOrEventResult = $this->currentContentRepository->handle($command);
    }
    /**
     * @Given /^the event RootWorkspaceWasCreated was published with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theEventRootWorkspaceWasCreatedWasPublishedToStreamWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        $newContentStreamId = ContentStreamId::fromString($eventPayload['newContentStreamId']);
        $streamName = ContentStreamEventStreamName::fromContentStreamId($newContentStreamId);
        $this->publishEvent('RootWorkspaceWasCreated', $streamName->getEventStreamName(), $eventPayload);
    }

    /**
     * @When /^the command CreateWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandCreateWorkspaceIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);

        $command = CreateWorkspace::create(
            WorkspaceName::fromString($commandArguments['workspaceName']),
            WorkspaceName::fromString($commandArguments['baseWorkspaceName']),
            new WorkspaceTitle($commandArguments['workspaceTitle'] ?? ucfirst($commandArguments['workspaceName'])),
            new WorkspaceDescription($commandArguments['workspaceDescription'] ?? 'The workspace "' . $commandArguments['workspaceName'] . '"'),
            ContentStreamId::fromString($commandArguments['newContentStreamId']),
            isset($commandArguments['workspaceOwner']) ? UserId::fromString($commandArguments['workspaceOwner']) : null
        );

        $this->lastCommandOrEventResult = $this->currentContentRepository->handle($command);
    }


    /**
     * @When /^the command RebaseWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandRebaseWorkspaceIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $command = RebaseWorkspace::create(
            WorkspaceName::fromString($commandArguments['workspaceName']),
        );
        if (isset($commandArguments['rebasedContentStreamId'])) {
            $command = $command->withRebasedContentStreamId(ContentStreamId::fromString($commandArguments['rebasedContentStreamId']));
        }

        $this->lastCommandOrEventResult = $this->currentContentRepository->handle($command);
    }
}
