<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Tests\Behavior\Features\Bootstrap;

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Intermediary\Domain\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Intermediary\Domain\Command\PropertyValuesToWrite;
use Neos\ContentRepository\Intermediary\Domain\Command\SetNodeProperties;
use Neos\ContentRepository\Intermediary\Domain\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifiersByNodePaths;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * Custom context trait for executing intermediary commands
 */
trait IntermediaryCommandTrait
{
    protected NodeAggregateCommandHandler $intermediaryNodeAggregateCommandHandler;

    private ?\Exception $lastCommandException = null;

    abstract protected function getObjectManager(): ObjectManagerInterface;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    public function setupIntermediaryCommandTrait(): void
    {
        $this->intermediaryNodeAggregateCommandHandler = $this->getObjectManager()->get(NodeAggregateCommandHandler::class);
    }

    /**
     * @When /^the intermediary command CreateNodeAggregateWithNode is executed with payload:$/
     * @param TableNode $payloadTable
     */
    public function theIntermediaryCommandCreateNodeAggregateWithNodeIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        if (!isset($commandArguments['initiatingUserIdentifier'])) {
            $commandArguments['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }

        $command = new CreateNodeAggregateWithNode(
            ContentStreamIdentifier::fromString($commandArguments['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($commandArguments['nodeAggregateIdentifier']),
            NodeTypeName::fromString($commandArguments['nodeTypeName']),
            new OriginDimensionSpacePoint($commandArguments['originDimensionSpacePoint']),
            UserIdentifier::fromString($commandArguments['initiatingUserIdentifier']),
            NodeAggregateIdentifier::fromString($commandArguments['parentNodeAggregateIdentifier']),
            isset($commandArguments['succeedingSiblingNodeAggregateIdentifier'])
                ? NodeAggregateIdentifier::fromString($commandArguments['succeedingSiblingNodeAggregateIdentifier'])
                : null,
            isset($commandArguments['nodeName'])
                ? NodeName::fromString($commandArguments['nodeName'])
                : null,
            isset($commandArguments['initialPropertyValues'])
                ? $this->unserializeProperties($commandArguments['initialPropertyValues'])
                : null,
            isset($commandArguments['tetheredDescendantNodeAggregateIdentifiers'])
                ? NodeAggregateIdentifiersByNodePaths::fromArray($commandArguments['tetheredDescendantNodeAggregateIdentifiers'])
                : null
        );

        $this->lastCommandOrEventResult = $this->intermediaryNodeAggregateCommandHandler
            ->handleCreateNodeAggregateWithNode($command);
    }

    /**
     * @When /^the intermediary command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     */
    public function theIntermediaryCommandCreateNodeAggregateWithNodeIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable)
    {
        try {
            $this->theIntermediaryCommandCreateNodeAggregateWithNodeIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @When /^the intermediary command SetNodeProperties is executed with payload:$/
     * @param TableNode $payloadTable
     */
    public function theIntermediaryCommandSetPropertiesIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        if (!isset($commandArguments['initiatingUserIdentifier'])) {
            $commandArguments['initiatingUserIdentifier'] = 'initiating-user-identifier';
        }

        $command = new SetNodeProperties(
            ContentStreamIdentifier::fromString($commandArguments['contentStreamIdentifier']),
            NodeAggregateIdentifier::fromString($commandArguments['nodeAggregateIdentifier']),
            OriginDimensionSpacePoint::fromArray($commandArguments['originDimensionSpacePoint']),
            $this->unserializeProperties($commandArguments['propertyValues']),
            UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
        );

        $this->lastCommandOrEventResult = $this->intermediaryNodeAggregateCommandHandler
            ->handleSetNodeProperties($command);
    }

    /**
     * @When /^the intermediary command SetNodeProperties is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     */
    public function theIntermediaryCommandSetPropertiesIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable)
    {
        try {
            $this->theIntermediaryCommandSetPropertiesIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    protected function unserializeProperties(array $properties): PropertyValuesToWrite
    {
        return PropertyValuesToWrite::fromArray($properties);
    }
}
