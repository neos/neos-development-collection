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

declare(strict_types=1);

namespace Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap;

use PHPUnit\Framework\Assert;

/**
 * The feature trait to test projected nodes
 */
trait CurrentSubgraphTrait
{
    use CRTestSuiteRuntimeVariables;

    /**
     * @Then /^I expect the subgraph projection to consist of exactly (\d+) node(?:s)?$/
     * @param int $expectedNumberOfNodes
     */
    public function iExpectTheSubgraphProjectionToConsistOfExactlyNodes(int $expectedNumberOfNodes): void
    {
        $actualNumberOfNodes = $this->getCurrentSubgraph()->countNodes();
        Assert::assertSame($expectedNumberOfNodes, $actualNumberOfNodes, 'Content subgraph consists of ' . $actualNumberOfNodes . ' nodes, expected were ' . $expectedNumberOfNodes . '.');
    }
}
