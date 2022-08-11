<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Tests\Behavior\Features\Bootstrap;

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
use Neos\ContentRepository\Factory\ContentRepositoryIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Feature\Common\NodeTypeNotFoundException;
use Neos\ContentRepository\StructureAdjustment\Adjustment\StructureAdjustment;
use Neos\ContentRepository\StructureAdjustment\StructureAdjustmentService;
use Neos\ContentRepository\StructureAdjustment\StructureAdjustmentServiceFactory;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use PHPUnit\Framework\Assert;

/**
 * Custom context trait for "Structure Adjustments" related concerns
 */
trait StructureAdjustmentsTrait
{
    /**
     * @var StructureAdjustmentService
     */
    protected $structureAdjustmentService;

    abstract protected function getContentRepositoryIdentifier(): ContentRepositoryIdentifier;
    abstract protected function getContentRepositoryRegistry(): ContentRepositoryRegistry;

    protected function setupStructureAdjustmentTrait(): void
    {
        $this->structureAdjustmentService = $this->getContentRepositoryRegistry()->getService($this->getContentRepositoryIdentifier(), new StructureAdjustmentServiceFactory());
    }

    /**
     * @When /^I adjust the node structure for node type "([^"]*)"$/
     * @param string $nodeTypeName
     * @param string $tetheredNodeName
     * @throws NodeTypeNotFoundException
     */
    public function iAdjustTheNodeStructureForNodeType(string $nodeTypeName): void
    {
        $errors = $this->structureAdjustmentService->findAdjustmentsForNodeType(NodeTypeName::fromString($nodeTypeName));
        foreach ($errors as $error) {
            $this->structureAdjustmentService->fixError($error);
        }
    }

    /**
     * @Then I expect no needed structure adjustments for type :nodeTypeName
     * @param string $nodeTypeName
     * @throws NodeTypeNotFoundException
     */
    public function iExpectNoStructureAdjustmentsForType(string $nodeTypeName): void
    {
        $errors = $this->structureAdjustmentService->findAdjustmentsForNodeType(NodeTypeName::fromString($nodeTypeName));
        $errors = iterator_to_array($errors);
        Assert::assertEmpty($errors, implode(', ', array_map(fn (StructureAdjustment $adjustment) => $adjustment->render(), $errors)));
    }

    /**
     * @Then /^I expect the following structure adjustments for type "([^"]*)":$/
     * @param string $nodeTypeName
     * @param TableNode $expectedAdjustments
     * @throws NodeTypeNotFoundException
     */
    public function iExpectTheFollowingStructureAdjustmentsForType(string $nodeTypeName, TableNode $expectedAdjustments): void
    {
        $actualAdjustments = $this->structureAdjustmentService->findAdjustmentsForNodeType(NodeTypeName::fromString($nodeTypeName));
        $actualAdjustments = iterator_to_array($actualAdjustments);

        $this->assertEqualStructureAdjustments($expectedAdjustments, $actualAdjustments);
    }

    protected function assertEqualStructureAdjustments(TableNode $expectedAdjustments, array $actualAdjustments): void
    {
        $convertedViolations = [];
        Assert::assertCount(count($expectedAdjustments->getHash()), $actualAdjustments, 'Number of adjustments must match.');

        foreach ($expectedAdjustments->getHash() as $i => $row) {
            if (!isset($row['Type']) || !isset($row['nodeAggregateIdentifier'])) {
                Assert::fail('Type and nodeAggregateIdentifier must be specified in assertion!');
            }
            $adjustment = $this->findAdjustmentsBasedOnTypeAndNodeAggregateIdentifier($actualAdjustments, $row['Type'], $row['nodeAggregateIdentifier']);
            foreach ($row as $k => $v) {
                if (in_array($k, ['Type', 'nodeAggregateIdentifier'])) {
                    continue;
                }

                Assert::assertEquals($v, $adjustment->getArguments()[$k], '"' . $k . '" did not match in line ' . $i);
            }
        }
    }

    private function findAdjustmentsBasedOnTypeAndNodeAggregateIdentifier(array $actualAdjustments, string $type, string $nodeAggregateIdentifier): StructureAdjustment
    {
        foreach ($actualAdjustments as $adjustment) {
            assert($adjustment instanceof StructureAdjustment);
            if ($adjustment->getType() === $type && $adjustment->getArguments()['nodeAggregateIdentifier'] === $nodeAggregateIdentifier) {
                return $adjustment;
            }
        }
        Assert::fail('Adjustment not found for type "' . $type . '" and node aggregate identifier "' . $nodeAggregateIdentifier . '"');
    }
}
