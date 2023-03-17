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
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardWorkspace;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The workspace discarding feature trait for behavioral tests
 */
trait WorkspaceDiscarding
{
    abstract protected function getContentRepository(): ContentRepository;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    /**
     * @Given /^the command DiscardWorkspace is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandDiscardWorkspaceIsExecuted(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $newContentStreamId = isset($commandArguments['newContentStreamId'])
            ? ContentStreamId::fromString($commandArguments['newContentStreamId'])
            : ContentStreamId::create();

        $command = DiscardWorkspace::createFullyDeterministic(
            WorkspaceName::fromString($commandArguments['workspaceName']),
            $newContentStreamId
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
        $nodesToDiscard = NodeIdsToPublishOrDiscard::fromArray($commandArguments['nodesToDiscard']);
        $newContentStreamId = isset($commandArguments['newContentStreamId'])
            ? ContentStreamId::fromString($commandArguments['newContentStreamId'])
            : ContentStreamId::create();

        $command = DiscardIndividualNodesFromWorkspace::createFullyDeterministic(
            WorkspaceName::fromString($commandArguments['workspaceName']),
            $nodesToDiscard,
            $newContentStreamId
        );

        $this->lastCommandOrEventResult = $this->getContentRepository()->handle($command);
    }
}
