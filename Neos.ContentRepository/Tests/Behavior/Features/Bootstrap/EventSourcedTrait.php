<?php

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
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\DimensionValues;
use Neos\EventSourcing\Event\EventInterface;
use Neos\EventSourcing\Event\EventPublisher;
use Neos\EventSourcing\Event\EventTypeResolver;
use Neos\EventSourcing\EventStore\EventAndRawEvent;
use Neos\EventSourcing\EventStore\EventStoreManager;
use Neos\EventSourcing\EventStore\StreamNameFilter;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use PHPUnit\Framework\Assert;

/**
 * Features context
 */
trait EventSourcedTrait
{

    /**
     * @var EventTypeResolver
     */
    private $eventTypeResolver;


    /**
     * @var PropertyMapper
     */
    private $propertyMapper;


    /**
     * @var EventPublisher
     */
    private $eventPublisher;

    /**
     * @var EventStoreManager
     */
    private $eventStoreManager;

    private $currentEventStreamAsArray;

    /**
     * @Given /^the Event "([^"]*)" was published to stream "([^"]*)" with payload:$/
     */
    public function theEventWasPublishedToStreamWithPayload($eventType, $streamName, TableNode $payloadTable)
    {
        $eventClassName = $this->eventTypeResolver->getEventClassNameByType($eventType);
        $eventPayload = $this->readPayloadTable($payloadTable);

        /** @var EventInterface $event */
        $configuration = new PropertyMappingConfiguration();
        $configuration->allowAllProperties();
        $configuration->skipUnknownProperties();
        $configuration->forProperty('*')->allowAllProperties()->skipUnknownProperties();

        $event = $this->propertyMapper->convert($eventPayload, $eventClassName, $configuration);

        $this->eventPublisher->publish($streamName, $event);
    }

    protected function readPayloadTable(TableNode $payloadTable)
    {
        $eventPayload = [];
        foreach ($payloadTable->getHash() as $line) {
            if (!empty($line['Type'])) {
                switch ($line['Type']) {
                    case 'json':
                        $eventPayload[$line['Key']] = json_decode($line['Value'], true);
                        break;
                    case 'DimensionSpacePoint':
                        $eventPayload[$line['Key']] = new DimensionSpacePoint(json_decode($line['Value'], true));
                        break;
                    case 'DimensionSpacePointSet':
                        $tmp = json_decode($line['Value'], true);
                        $convertedPoints = [];
                        if (isset($tmp['points'])) {
                            foreach ($tmp['points'] as $point) {
                                $convertedPoints[] = new DimensionSpacePoint($point['coordinates']);
                            }
                        }
                        $eventPayload[$line['Key']] = new DimensionSpacePointSet($convertedPoints);
                        break;
                    default:
                        throw new \Exception("TODO" . json_encode($line));
                }
            } else {
                $eventPayload[$line['Key']] = $line['Value'];
            }
        }

        return $eventPayload;
    }

    /**
     * @When /^the command "([^"]*)" is executed with payload:$/
     */
    public function theCommandIsExecutedWithPayload($shortCommandName, TableNode $payloadTable)
    {
        list($commandClassName, $commandHandlerClassName, $commandHandlerMethod) = self::resolveShortCommandName($shortCommandName);
        $commandArguments = $this->readPayloadTable($payloadTable);

        /** @var EventInterface $event */
        $command = $this->propertyMapper->convert($commandArguments, $commandClassName);
        $commandHandler = $this->objectManager->get($commandHandlerClassName);

        $commandHandler->$commandHandlerMethod($command);
    }

    protected static function resolveShortCommandName($shortCommandName)
    {
        switch ($shortCommandName) {
            case 'CreateRootNode':
                return [
                    \Neos\ContentRepository\Domain\Context\Node\Command\CreateRootNode::class,
                    \Neos\ContentRepository\Domain\Context\Node\NodeCommandHandler::class,
                    'handleCreateRootNode'
                ];
            case 'CreateNodeAggregateWithNode':
                return [
                    \Neos\ContentRepository\Domain\Context\Node\Command\CreateNodeAggregateWithNode::class,
                    \Neos\ContentRepository\Domain\Context\Node\NodeCommandHandler::class,
                    'handleCreateNodeAggregateWithNode'
                ];
            default:
                throw new \Exception('The short command name "' . $shortCommandName . '" is currently not supported by the tests.');
        }
    }


    /**
     * @Then /^I expect exactly (\d+) events? to be published on stream "([^"]*)"$/
     */
    public function iExpectExactlyEventToBePublishedOnStream($numberOfEvents, $streamName)
    {
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($streamName);
        $stream = $eventStore->get(new StreamNameFilter($streamName));
        $this->currentEventStreamAsArray = iterator_to_array($stream);
        Assert::assertEquals($numberOfEvents, count($this->currentEventStreamAsArray), 'Number of events did not match');
    }

    /**
     * @Then /^event number (\d+) is of type "([^"]*)" with payload:/
     */
    public function eventNumberIs($eventNumber, $eventType, TableNode $payloadTable)
    {
        /* @var $actualEvent EventAndRawEvent */
        $actualEvent = $this->currentEventStreamAsArray[$eventNumber];
        Assert::assertEquals($eventType, $actualEvent->getRawEvent()->getType(), 'Event Type does not match: "' . $actualEvent->getRawEvent()->getType() . '" !== "' . $eventType . '"');

        $actualEventPayload = $actualEvent->getRawEvent()->getPayload();

        foreach ($payloadTable->getHash() as $assertionTableRow) {
            $actualValue = \Neos\Utility\Arrays::getValueByPath($actualEventPayload, $assertionTableRow['Key']);
            $expectedValue = $assertionTableRow['Expected'];
            if (isset($assertionTableRow['AssertionType']) && $assertionTableRow['AssertionType'] === 'json') {
                $expectedValue = json_decode($expectedValue, true);
            }

            Assert::assertEquals($expectedValue, $actualValue, 'ERROR at ' . $assertionTableRow['Key'] . ': ' . json_encode($actualValue) . ' !== ' . json_encode($expectedValue));
        }
    }
}
