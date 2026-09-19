<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeAccess\Tests\Unit\FlowQueryOperations;

use Neos\ContentRepository\NodeAccess\FlowQueryOperations\SortOperation;
use Neos\Eel\FlowQuery\FlowQueryException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * SortOperation test
 */
class SortOperationTest extends TestCase
{
    #[Test]
    public function callWithoutArgumentsCausesException()
    {
        $this->expectException(FlowQueryException::class);
        $flowQuery = new \Neos\Eel\FlowQuery\FlowQuery([]);
        $operation = new SortOperation();
        $operation->evaluate($flowQuery, []);
    }

    #[Test]
    public function invalidSortDirectionCausesException()
    {
        $this->expectException(FlowQueryException::class);
        $flowQuery = new \Neos\Eel\FlowQuery\FlowQuery([]);
        $operation = new SortOperation();
        $operation->evaluate($flowQuery, ['title', 'FOO']);
    }

    #[Test]
    public function invalidSortOptionCausesException()
    {
        $this->expectException(FlowQueryException::class);
        $flowQuery = new \Neos\Eel\FlowQuery\FlowQuery([]);
        $operation = new SortOperation();
        $operation->evaluate($flowQuery, ['title', 'ASC', 'SORT_BAR']);
    }
}
