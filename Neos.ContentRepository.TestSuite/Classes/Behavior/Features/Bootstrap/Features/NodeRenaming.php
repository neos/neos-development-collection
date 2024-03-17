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
use Neos\ContentRepository\Core\Feature\NodeRenaming\Command\ChangeNodeAggregateName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;
use PHPUnit\Framework\Assert;

/**
 * The node renaming trait for behavioral tests
 */
trait NodeRenaming
{
    use CRTestSuiteRuntimeVariables;

    /**
     * @Given /^the command ChangeNodeAggregateName is executed with payload:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandChangeNodeAggregateNameIsExecutedWithPayload(TableNode $payloadTable)
    {
        $commandArguments = $this->readPayloadTable($payloadTable);
        $workspaceName = isset($commandArguments['workspaceName'])
            ? WorkspaceName::fromString($commandArguments['workspaceName'])
            : $this->currentWorkspaceName;

        $command = ChangeNodeAggregateName::create(
            $workspaceName,
            NodeAggregateId::fromString($commandArguments['nodeAggregateId']),
            NodeName::fromString($commandArguments['newNodeName']),
        );

        $this->lastCommandOrEventResult = $this->currentContentRepository->handle($command);
    }

    /**
     * @Given /^the command ChangeNodeAggregateName is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandChangeNodeAggregateNameIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable)
    {
        try {
            $this->theCommandChangeNodeAggregateNameIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }

    /**
     * @Then /^I expect the node "([^"]*)" to have the name "([^"]*)"$/
     * @param string $nodeAggregateId
     * @param string $nodeName
     */
    public function iExpectTheNodeToHaveTheName(string $nodeAggregateId, string $nodeName)
    {
        $node = $this->getCurrentSubgraph()->findNodeById(NodeAggregateId::fromString($nodeAggregateId));
        Assert::assertEquals($nodeName, $node->nodeName->value, 'Node Names do not match');
    }
}
