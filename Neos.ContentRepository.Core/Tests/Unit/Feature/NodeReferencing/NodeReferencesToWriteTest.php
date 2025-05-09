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

namespace Neos\ContentRepository\Core\Tests\Unit\Feature\NodeReferencing;

use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferencesForName;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferencesToWrite;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferenceToWrite;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use PHPUnit\Framework\TestCase;

class NodeReferencesToWriteTest extends TestCase
{
    public function testMultipleNamesAreNotAllowedInConstructor(): void
    {
        self::expectException(\InvalidArgumentException::class);

        NodeReferencesToWrite::create(
            NodeReferencesForName::createEmpty(ReferenceName::fromString('foo')),
            NodeReferencesForName::fromTargets(ReferenceName::fromString('bar'), NodeAggregateIds::fromArray(['fooo'])),
            NodeReferencesForName::fromTargets(ReferenceName::fromString('foo'), NodeAggregateIds::fromArray(['abc'])),
        );
    }

    public function testMergeOverridesPrevious(): void
    {
        $a = NodeReferencesToWrite::create(
            NodeReferencesForName::fromTargets(ReferenceName::fromString('foo'), NodeAggregateIds::fromArray(['abc'])),
            NodeReferencesForName::fromTargets(ReferenceName::fromString('bar'), NodeAggregateIds::fromArray(['fooo'])),
        );

        $b = NodeReferencesToWrite::create(
            NodeReferencesForName::fromTargets(ReferenceName::fromString('new'), NodeAggregateIds::fromArray(['la-li-lu'])),
            NodeReferencesForName::createEmpty(ReferenceName::fromString('foo')),
        );

        $c = $a->merge($b);

        self::assertEquals(
            iterator_to_array(NodeReferencesToWrite::create(
                NodeReferencesForName::createEmpty(ReferenceName::fromString('foo')),
                NodeReferencesForName::fromTargets(ReferenceName::fromString('bar'), NodeAggregateIds::fromArray(['fooo'])),
                NodeReferencesForName::fromTargets(ReferenceName::fromString('new'), NodeAggregateIds::fromArray(['la-li-lu'])),
            )),
            iterator_to_array($c)
        );
    }

    public function testAppendOverridesPrevious(): void
    {
        $a = NodeReferencesToWrite::create(
            NodeReferencesForName::fromTargets(ReferenceName::fromString('foo'), NodeAggregateIds::fromArray(['abc'])),
            NodeReferencesForName::fromTargets(ReferenceName::fromString('bar'), NodeAggregateIds::fromArray(['fooo'])),
        );

        $b = NodeReferencesForName::fromTargets(ReferenceName::fromString('bar'), NodeAggregateIds::fromArray(['la-li-lu', 'second']));

        $c = $a->withReference($b);

        self::assertEquals(
            iterator_to_array(NodeReferencesToWrite::create(
                NodeReferencesForName::fromTargets(ReferenceName::fromString('foo'), NodeAggregateIds::fromArray(['abc'])),
                NodeReferencesForName::fromTargets(ReferenceName::fromString('bar'), NodeAggregateIds::fromArray(['la-li-lu', 'second']))
            )),
            iterator_to_array($c)
        );
    }

    public function testSameTargetsAreNotAllowedMultipleTimes(): void
    {
        self::expectException(\InvalidArgumentException::class);

        NodeReferencesForName::fromReferences(ReferenceName::fromString('bar'), [
            NodeReferenceToWrite::fromTarget(NodeAggregateId::fromString('node1')),
            NodeReferenceToWrite::fromTarget(NodeAggregateId::fromString('node-other')),
            NodeReferenceToWrite::fromTarget(NodeAggregateId::fromString('node1')),
        ]);
    }
}
