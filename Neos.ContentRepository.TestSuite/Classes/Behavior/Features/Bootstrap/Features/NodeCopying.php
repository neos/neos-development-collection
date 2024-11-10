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
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Command\CopyNodesRecursively;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Dto\NodeAggregateIdMapping;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRTestSuiteRuntimeVariables;

/**
 * The node copying trait for behavioral tests
 */
trait NodeCopying
{
    use CRTestSuiteRuntimeVariables;

    abstract protected function readPayloadTable(TableNode $payloadTable): array;

    /**
     * @When /^the command CopyNodesRecursively is executed with payload:$/
     */
    public function theCommandCopyNodesRecursivelyIsExecutedWithPayload(TableNode $payloadTable): void
    {
        $commandArguments = $this->readPayloadTable($payloadTable);

        $workspaceName = isset($commandArguments['workspaceName'])
            ? WorkspaceName::fromString($commandArguments['workspaceName'])
            : $this->currentWorkspaceName;

        // "virtual" command arguments that do not exist YET
        $sourceNodeAggregateId = NodeAggregateId::fromString($commandArguments['sourceNodeAggregateId']);
        $sourceDimensionSpacePoint = isset($commandArguments['sourceDimensionSpacePoint'])
            ? DimensionSpacePoint::fromArray($commandArguments['sourceDimensionSpacePoint'])
            : $this->currentDimensionSpacePoint;

        $subgraphToCopy = $this->currentContentRepository->getContentGraph($workspaceName)->getSubgraph(
            $sourceDimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );
        $nodeToCopy = $subgraphToCopy->findNodeById($sourceNodeAggregateId);

        $targetDimensionSpacePoint = isset($commandArguments['targetDimensionSpacePoint'])
            ? OriginDimensionSpacePoint::fromArray($commandArguments['targetDimensionSpacePoint'])
            : OriginDimensionSpacePoint::fromDimensionSpacePoint($this->currentDimensionSpacePoint);

        $targetSucceedingSiblingNodeAggregateId = isset($commandArguments['targetSucceedingSiblingNodeAggregateId'])
            ? NodeAggregateId::fromString($commandArguments['targetSucceedingSiblingNodeAggregateId'])
            : null;

        $command = CopyNodesRecursively::createFromSubgraphAndStartNode(
            $subgraphToCopy,
            $workspaceName,
            $nodeToCopy,
            $targetDimensionSpacePoint,
            NodeAggregateId::fromString($commandArguments['targetParentNodeAggregateId']),
            $targetSucceedingSiblingNodeAggregateId
        );
        if (isset($commandArguments['targetNodeName'])) {
            $command = $command->withTargetNodeName(NodeName::fromString($commandArguments['targetNodeName']));
        }
        $command = $command->withNodeAggregateIdMapping(NodeAggregateIdMapping::fromArray($commandArguments['nodeAggregateIdMapping']));

        $this->currentContentRepository->handle($command);
    }

    /**
     * @Given /^the command CopyNodesRecursively is executed with payload and exceptions are caught:$/
     * @param TableNode $payloadTable
     * @throws \Exception
     */
    public function theCommandCopyNodesRecursivelyIsExecutedWithPayloadAndExceptionsAreCaught(TableNode $payloadTable)
    {
        try {
            $this->theCommandCopyNodesRecursivelyIsExecutedWithPayload($payloadTable);
        } catch (\Exception $exception) {
            $this->lastCommandException = $exception;
        }
    }
}
