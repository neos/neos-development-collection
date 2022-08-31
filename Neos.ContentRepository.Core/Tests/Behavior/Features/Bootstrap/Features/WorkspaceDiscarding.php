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
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdentifiersToPublishOrDiscard;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardWorkspace;
use Neos\ContentRepository\Core\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The workspace discarding feature trait for behavioral tests
 */
trait WorkspaceDiscarding
{
    abstract protected function getContentRepository(): ContentRepository;
    abstract protected function getCurrentUserIdentifier(): ?UserIdentifier;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    /**
     * @Given /^the command DiscardWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandDiscardWorkspaceIsExecuted(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $initiatingUserIdentifier = isset($commandArguments['initiatingUserIdentifier'])
            ? UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
            : $this->getCurrentUserIdentifier();
        $newContentStreamIdentifier = isset($commandArguments['newContentStreamIdentifier'])
            ? ContentStreamIdentifier::fromString($commandArguments['newContentStreamIdentifier'])
            : ContentStreamIdentifier::create();

        $command = DiscardWorkspace::createFullyDeterministic(
            WorkspaceName::fromString($commandArguments['workspaceName']),
            $initiatingUserIdentifier,
            $newContentStreamIdentifier
        );

        $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);
    }


    /**
     * @Given /^the command DiscardIndividualNodesFromWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandDiscardIndividualNodesFromWorkspaceIsExecuted(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $nodesToDiscard = NodeIdentifiersToPublishOrDiscard::fromArray($commandArguments['nodesToDiscard']);
        $initiatingUserIdentifier = isset($commandArguments['initiatingUserIdentifier'])
            ? UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
            : $this->getCurrentUserIdentifier();
        $newContentStreamIdentifier = isset($commandArguments['newContentStreamIdentifier'])
            ? ContentStreamIdentifier::fromString($commandArguments['newContentStreamIdentifier'])
            : ContentStreamIdentifier::create();

        $command = DiscardIndividualNodesFromWorkspace::createFullyDeterministic(
            WorkspaceName::fromString($commandArguments['workspaceName']),
            $nodesToDiscard,
            $initiatingUserIdentifier,
            $newContentStreamIdentifier
        );

        $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);
    }
}
