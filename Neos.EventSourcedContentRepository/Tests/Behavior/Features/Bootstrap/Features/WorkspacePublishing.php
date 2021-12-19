<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Tests\Behavior\Features\Bootstrap\Features;

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
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\PublishIndividualNodesFromWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\PublishWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\WorkspaceCommandHandler;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;

/**
 * The workspace publishing feature trait for behavioral tests
 */
trait WorkspacePublishing
{
    abstract protected function getCurrentUserIdentifier(): ?UserIdentifier;

    abstract protected function getWorkspaceCommandHandler(): WorkspaceCommandHandler;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    /**
     * @Given /^the command PublishIndividualNodesFromWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandPublishIndividualNodesFromWorkspaceIsExecuted(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $nodeAddresses = array_map(function (array $serializedNodeAddress) {
            return NodeAddress::fromArray($serializedNodeAddress);
        }, $commandArguments['nodeAddresses']);
        $initiatingUserIdentifier = isset($commandArguments['initiatingUserIdentifier'])
            ? UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
            : $this->getCurrentUserIdentifier();

        $contentStreamIdentifierForMatchingPart = isset($commandArguments['contentStreamIdentifierForMatchingPart'])
            ? ContentStreamIdentifier::fromString($commandArguments['contentStreamIdentifierForMatchingPart'])
            : ContentStreamIdentifier::create();

        $contentStreamIdentifierForRemainingPart = isset($commandArguments['contentStreamIdentifierForRemainingPart'])
            ? ContentStreamIdentifier::fromString($commandArguments['contentStreamIdentifierForRemainingPart'])
            : ContentStreamIdentifier::create();

        $command = PublishIndividualNodesFromWorkspace::createFullyDeterministic(
            new WorkspaceName($commandArguments['workspaceName']),
            $nodeAddresses,
            $initiatingUserIdentifier,
            $contentStreamIdentifierForMatchingPart,
            $contentStreamIdentifierForRemainingPart
        );

        $this->lastCommandOrEventResult = $this->getWorkspaceCommandHandler()
            ->handlePublishIndividualNodesFromWorkspace($command);
    }

    /**
     * @Given /^the command PublishWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandPublishWorkspaceIsExecuted(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $initiatingUserIdentifier = isset($commandArguments['initiatingUserIdentifier'])
            ? UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
            : $this->getCurrentUserIdentifier();

        $command = new PublishWorkspace(
            new WorkspaceName($commandArguments['workspaceName']),
            $initiatingUserIdentifier
        );

        $this->lastCommandOrEventResult = $this->getWorkspaceCommandHandler()
            ->handlePublishWorkspace($command);
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
