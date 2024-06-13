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
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdsToPublishOrDiscard;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;

/**
 * The workspace discarding feature trait for behavioral tests
 */
trait WorkspaceDiscarding
{
    use CRTestSuiteRuntimeVariables;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    /**
     * @Given /^the command DiscardWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandDiscardWorkspaceIsExecuted(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $command = DiscardWorkspace::create(
            WorkspaceName::fromString($commandArguments['workspaceName']),
        );
        if (isset($commandArguments['newContentStreamId'])) {
            $command = $command->withNewContentStreamId(ContentStreamId::fromString($commandArguments['newContentStreamId']));
        }

        $this->currentContentRepository->handle($command);
    }


    /**
     * @Given /^the command DiscardIndividualNodesFromWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandDiscardIndividualNodesFromWorkspaceIsExecuted(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $nodesToDiscard = NodeIdsToPublishOrDiscard::fromArray($commandArguments['nodesToDiscard']);
        $command = DiscardIndividualNodesFromWorkspace::create(
            WorkspaceName::fromString($commandArguments['workspaceName']),
            $nodesToDiscard,
        );
        if (isset($commandArguments['newContentStreamId'])) {
            $command = $command->withNewContentStreamId(ContentStreamId::fromString($commandArguments['newContentStreamId']));
        }

        $this->currentContentRepository->handle($command);
    }


    /**
     * @Given /^the command DiscardIndividualNodesFromWorkspace is executed with payload and exceptions are caught:$/
     */
    public function theCommandDiscardIndividualNodesFromWorkspaceIsExecutedAndExceptionsAreCaught(TableNode $payloadTable): void
    {
        try {
            $this->theCommandDiscardIndividualNodesFromWorkspaceIsExecuted($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }
}
