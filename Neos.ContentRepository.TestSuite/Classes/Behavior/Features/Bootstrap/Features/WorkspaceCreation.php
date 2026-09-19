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
use Neos\ContentRepository\Core\Feature\WorkspaceEventStreamName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
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
     * @Given /^the event WorkspaceWasRemoved was published with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theEventWorkspaceWasRemovedWasPublishedToStreamWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        $workspaceName = WorkspaceName::fromString($eventPayload['workspaceName']);
        $streamName = WorkspaceEventStreamName::fromWorkspaceName($workspaceName);
        $this->publishEvent('WorkspaceWasRemoved', $streamName->getEventStreamName(), $eventPayload);
    }
}
