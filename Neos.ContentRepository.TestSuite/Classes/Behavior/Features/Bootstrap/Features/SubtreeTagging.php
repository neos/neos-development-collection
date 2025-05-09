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
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use Neos\EventStore\Model\Event\StreamName;

/**
 * The tagging trait for behavioral tests
 */
trait SubtreeTagging
{
    use CRTestSuiteRuntimeVariables;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    abstract protected function publishEvent(string $eventType, StreamName $streamName, array $eventPayload): void;


    /**
     * @Given /^the event SubtreeWasTagged was published with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theEventSubtreeWasTaggedWasPublishedWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        $streamName = ContentStreamEventStreamName::fromContentStreamId(
            ContentStreamId::fromString($eventPayload['contentStreamId'])
        );

        $this->publishEvent('SubtreeWasTagged', $streamName->getEventStreamName(), $eventPayload);
    }


    /**
     * @Given /^the event SubtreeWasUntagged was published with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theEventSubtreeWasUntaggedWasPublishedWithPayload(TableNode $payloadTable)
    {
        $eventPayload = $this->readPayloadTable($payloadTable);
        $streamName = ContentStreamEventStreamName::fromContentStreamId(
            ContentStreamId::fromString($eventPayload['contentStreamId'])
        );

        $this->publishEvent('SubtreeWasUntagged', $streamName->getEventStreamName(), $eventPayload);
    }
}
