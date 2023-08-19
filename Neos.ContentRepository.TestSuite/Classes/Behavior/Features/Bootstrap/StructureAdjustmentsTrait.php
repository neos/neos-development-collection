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

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap;

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\StructureAdjustment\Adjustment\StructureAdjustment;
use Neos\ContentRepository\StructureAdjustment\StructureAdjustmentService;
use Neos\ContentRepository\StructureAdjustment\StructureAdjustmentServiceFactory;
use PHPUnit\Framework\Assert;

/**
 * Custom context trait for "Structure Adjustments" related concerns
 */
trait StructureAdjustmentsTrait
{
    use CRTestSuiteRuntimeVariables;

    /**
     * @When /^I adjust the node structure for node type "([^"]*)"$/
     * @throws NodeTypeNotFoundException
     */
    public function iAdjustTheNodeStructureForNodeType(string $nodeTypeName): void
    {
        /** @var StructureAdjustmentService $structureAdjustmentService */
        $structureAdjustmentService = $this->getContentRepositoryService(new StructureAdjustmentServiceFactory());
        $errors = $structureAdjustmentService->findAdjustmentsForNodeType(NodeTypeName::fromString($nodeTypeName));
        foreach ($errors as $error) {
            $structureAdjustmentService->fixError($error);
        }
    }

    /**
     * @Then I expect no needed structure adjustments for type :nodeTypeName
     * @throws NodeTypeNotFoundException
     */
    public function iExpectNoStructureAdjustmentsForType(string $nodeTypeName): void
    {
        /** @var StructureAdjustmentService $structureAdjustmentService */
        $structureAdjustmentService = $this->getContentRepositoryService(new StructureAdjustmentServiceFactory());
        $errors = $structureAdjustmentService->findAdjustmentsForNodeType(NodeTypeName::fromString($nodeTypeName));
        $errors = iterator_to_array($errors);
        Assert::assertEmpty($errors, implode(', ', array_map(fn (StructureAdjustment $adjustment) => $adjustment->render(), $errors)));
    }

    /**
     * @Then /^I expect the following structure adjustments for type "([^"]*)":$/
     * @throws NodeTypeNotFoundException
     */
    public function iExpectTheFollowingStructureAdjustmentsForType(string $nodeTypeName, TableNode $expectedAdjustments): void
    {
        /** @var StructureAdjustmentService $structureAdjustmentService */
        $structureAdjustmentService = $this->getContentRepositoryService(new StructureAdjustmentServiceFactory());
        $actualAdjustments = $structureAdjustmentService->findAdjustmentsForNodeType(NodeTypeName::fromString($nodeTypeName));
        $actualAdjustments = iterator_to_array($actualAdjustments);

        $this->assertEqualStructureAdjustments($expectedAdjustments, $actualAdjustments);
    }

    protected function assertEqualStructureAdjustments(TableNode $expectedAdjustments, array $actualAdjustments): void
    {
        Assert::assertCount(count($expectedAdjustments->getHash()), $actualAdjustments, 'Number of adjustments must match.');

        foreach ($expectedAdjustments->getHash() as $i => $row) {
            if (!isset($row['Type']) || !isset($row['nodeAggregateId'])) {
                Assert::fail('Type and nodeAggregateId must be specified in assertion!');
            }
            $adjustment = $this->findAdjustmentsBasedOnTypeAndNodeAggregateId($actualAdjustments, $row['Type'], $row['nodeAggregateId']);
            foreach ($row as $k => $v) {
                if (in_array($k, ['Type', 'nodeAggregateId'])) {
                    continue;
                }

                Assert::assertEquals($v, $adjustment->getArguments()[$k], '"' . $k . '" did not match in line ' . $i);
            }
        }
    }

    private function findAdjustmentsBasedOnTypeAndNodeAggregateId(array $actualAdjustments, string $type, string $nodeAggregateId): StructureAdjustment
    {
        foreach ($actualAdjustments as $adjustment) {
            assert($adjustment instanceof StructureAdjustment);
            if ($adjustment->getType() === $type && $adjustment->getArguments()['nodeAggregateId'] === $nodeAggregateId) {
                return $adjustment;
            }
        }
        Assert::fail('Adjustment not found for type "' . $type . '" and node aggregate id "' . $nodeAggregateId . '"');
    }
}
