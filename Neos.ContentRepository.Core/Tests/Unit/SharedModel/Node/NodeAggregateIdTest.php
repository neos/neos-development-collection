<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\SharedModel\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use PHPUnit\Framework\TestCase;

class NodeAggregateIdTest extends TestCase
{
    /**
     * @test
     */
    public function nodeAggregateIdForTetheredNodesCanBeCalculatedDeterministic(): void
    {
        $nodeAggregateId = NodeAggregateId::fromString('b2e41ac5-671f-82ed-639d-3c8226631a74');

        $childNodeAggregateId = NodeAggregateId::fromParentNodeAggregateIdAndNodeName($nodeAggregateId, NodeName::fromString('main'));

        self::assertEquals(
            NodeAggregateId::fromString('ada70507-eb1e-a6aa-ebc6-4f7eddd441ac'),
            $childNodeAggregateId
        );
    }
}
