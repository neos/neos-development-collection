<?php

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection;

use Neos\Flow\Annotations as Flow;

/**
 * The collection of node records
 *
 * @implements \ArrayIterator<int,NodeRecord>
 */
#[Flow\Proxy(false)]
final class NodeRecords implements \IteratorAggregate
{
    /**
     * @var array<int,NodeRecord>
     */
    private array $nodeRecords;

    public function __construct(NodeRecord ...$nodeRecords)
    {
        $this->nodeRecords = $nodeRecords;
    }

    /**
     * @param array<int,array<string,mixed>> $databaseRows
     */
    public static function fromDatabaseRows(array $databaseRows): self
    {
        return new self(
            ...array_map(
                fn (array $databaseRow): NodeRecord => NodeRecord::fromDatabaseRow($databaseRow),
                $databaseRows
            )
        );
    }

    /**
     * @return \ArrayIterator<int,NodeRecord>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->nodeRecords);
    }
}
