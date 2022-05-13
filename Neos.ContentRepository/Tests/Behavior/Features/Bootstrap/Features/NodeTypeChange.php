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
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Feature\Common\NodeAggregateIdentifiersByNodePaths;
use Neos\ContentRepository\Feature\NodeTypeChange\Command\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

/**
 * The node type change trait for behavioral tests
 */
trait NodeTypeChange
{
    abstract protected function getCurrentContentStreamIdentifier(): ?ContentStreamIdentifier;

    abstract protected function getCurrentUserIdentifier(): ?UserIdentifier;

    abstract protected function getNodeAggregateCommandHandler(): NodeAggregateCommandHandler;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    /**
     * @Given /^the command ChangeNodeAggregateType was published with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandChangeNodeAggregateTypeIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $contentStreamIdentifier = isset($commandArguments['contentStreamIdentifier'])
            ? ContentStreamIdentifier::fromString($commandArguments['contentStreamIdentifier'])
            : $this->getCurrentContentStreamIdentifier();
        $initiatingUserIdentifier = isset($commandArguments['initiatingUserIdentifier'])
            ? UserIdentifier::fromString($commandArguments['initiatingUserIdentifier'])
            : $this->getCurrentUserIdentifier();
        $tetheredDescendantNodeAggregateIdentifiers = isset($commandArguments['tetheredDescendantNodeAggregateIdentifiers'])
            ? NodeAggregateIdentifiersByNodePaths::fromArray($commandArguments['tetheredDescendantNodeAggregateIdentifiers'])
            : null;

        $command = new ChangeNodeAggregateType(
            $contentStreamIdentifier,
            NodeAggregateIdentifier::fromString($commandArguments['nodeAggregateIdentifier']),
            NodeTypeName::fromString($commandArguments['newNodeTypeName']),
            NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::from($commandArguments['strategy']),
            $initiatingUserIdentifier,
            $tetheredDescendantNodeAggregateIdentifiers
        );

        $this->lastCommandOrEventResult = $this->getNodeAggregateCommandHandler()
            ->handleChangeNodeAggregateType($command);
    }

    /**
     * @Given /^the command ChangeNodeAggregateType was published with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandChangeNodeAggregateTypeIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable)
    {
        try {
            $this->theCommandChangeNodeAggregateTypeIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }
}
