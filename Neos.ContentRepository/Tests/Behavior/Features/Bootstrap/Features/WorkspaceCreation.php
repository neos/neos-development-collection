<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\Features;

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
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;
use Neos\EventSourcing\EventStore\StreamName;

/**
 * The workspace creation feature trait for behavioral tests
 */
trait WorkspaceCreation
{
    abstract protected function getCurrentUserIdentifier(): ?UserIdentifier;

    abstract protected function getWorkspaceCommandHandler(): WorkspaceCommandHandler;

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

        $userIdentifier = isset($commandArguments['initiatingUserIdentifier'])
            ? UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
            : $this->getCurrentUserIdentifier();

        $command = new CreateRootWorkspace(
            WorkspaceName::fromString($commandArguments['workspaceName']),
            new WorkspaceTitle($commandArguments['workspaceTitle'] ?? ucfirst($commandArguments['workspaceName'])),
            new WorkspaceDescription($commandArguments['workspaceDescription'] ?? 'The workspace "' . $commandArguments['workspaceName'] . '"'),
            $userIdentifier,
            ContentStreamIdentifier::fromString($commandArguments['newContentStreamIdentifier'])
        );

        $this->lastCommandOrEventResult = $this->getWorkspaceCommandHandler()
            ->handleCreateRootWorkspace($command);
    }
    /**
     * @Given /^the event RootWorkspaceWasCreated was published with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theEventRootWorkspaceWasCreatedWasPublishedToStreamWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        if (!isset($eventPayload['initiatingUserIdentifier'])) {
            $eventPayload['initiatingUserIdentifier'] = (string)$this->getCurrentUserIdentifier();
        }
        $newContentStreamIdentifier = ContentStreamIdentifier::fromString($eventPayload['newContentStreamIdentifier']);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($newContentStreamIdentifier);
        $this->publishEvent('Neos.EventSourcedContentRepository:RootWorkspaceWasCreated', $streamName->getEventStreamName(), $eventPayload);
    }

    /**
     * @When /^the command CreateWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandCreateWorkspaceIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $userIdentifier = isset($commandArguments['initiatingUserIdentifier'])
            ? UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
            : $this->getCurrentUserIdentifier();

        $command = new CreateWorkspace(
            WorkspaceName::fromString($commandArguments['workspaceName']),
            WorkspaceName::fromString($commandArguments['baseWorkspaceName']),
            new WorkspaceTitle($commandArguments['workspaceTitle'] ?? ucfirst($commandArguments['workspaceName'])),
            new WorkspaceDescription($commandArguments['workspaceDescription'] ?? 'The workspace "' . $commandArguments['workspaceName'] . '"'),
            $userIdentifier,
            ContentStreamIdentifier::fromString($commandArguments['newContentStreamIdentifier']),
            isset($commandArguments['workspaceOwner']) ? UserIdentifier::fromString($commandArguments['workspaceOwner']) : null
        );

        $this->lastCommandOrEventResult = $this->getWorkspaceCommandHandler()
            ->handleCreateWorkspace($command);
    }


    /**
     * @When /^the command RebaseWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandRebaseWorkspaceIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $userIdentifier = isset($commandArguments['initiatingUserIdentifier'])
            ? UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
            : $this->getCurrentUserIdentifier();

        $rebasedContentStreamIdentifier = isset($commandArguments['rebasedContentStreamIdentifier'])
            ? ContentStreamIdentifier::fromString($commandArguments['rebasedContentStreamIdentifier'])
            : ContentStreamIdentifier::create();

        $command = RebaseWorkspace::createFullyDeterministic(
            WorkspaceName::fromString($commandArguments['workspaceName']),
            $userIdentifier,
            $rebasedContentStreamIdentifier,
        );

        $this->lastCommandOrEventResult = $this->getWorkspaceCommandHandler()
            ->handleRebaseWorkspace($command);
    }
}
