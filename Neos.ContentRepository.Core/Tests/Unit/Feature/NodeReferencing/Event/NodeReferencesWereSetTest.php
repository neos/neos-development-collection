<?php

/*
 * This file is part of the Neos.ContentRepository.Core package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Unit\Feature\NodeReferencing\Event;

use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use PHPUnit\Framework\TestCase;

class NodeReferencesWereSetTest extends TestCase
{
    /**
     * @param array<string,mixed> $payload
     * @dataProvider eventPayloadProviderWithLegacySourceNodeAggregateIdFormat
     */
    public function testFromArray(array $payload, NodeReferencesWereSet $expectedEvent): void
    {
        self::assertEquals($expectedEvent, NodeReferencesWereSet::fromArray($payload));
    }

    /**
     * The property sourceNodeAggregateId was renamed to nodeAggregateId.
     * A runtime migration was added to {@see NodeReferencesWereSet::fromArray()} to handle the legacy format.
     *
     * Via #5153: https://github.com/neos/neos-development-collection/pull/5153
     *
     * Included in Juni 2024
     *
     * @return iterable<mixed>
     */
    public static function eventPayloadProviderWithLegacySourceNodeAggregateIdFormat(): iterable
    {
        yield 'legacy coveredDimensionSpacePoints succeedingNodeAggregateId fields are transformed' => [
            [
                'contentStreamId' => 'e542b7d2-a7c1-4bd8-b02a-3e8450829965',
                'sourceNodeAggregateId' => '3b4ecdc0-8115-41ed-805d-fb98ced2276a',
                'affectedSourceOriginDimensionSpacePoints' =>
                    [
                        [
                            'language' => 'en_US',
                        ],
                    ],
                'referenceName' => 'blogs',
                'references' => [],
                'workspaceName' => 'user-admin',
            ],
            new NodeReferencesWereSet(
                WorkspaceName::fromString('user-admin'),
                ContentStreamId::fromString('e542b7d2-a7c1-4bd8-b02a-3e8450829965'),
                NodeAggregateId::fromString('3b4ecdc0-8115-41ed-805d-fb98ced2276a'),
                OriginDimensionSpacePointSet::fromArray([
                    [
                        'language' => 'en_US',
                    ],
                ]),
                ReferenceName::fromString('blogs'),
                SerializedNodeReferences::fromArray([])
            )
        ];
    }
}
