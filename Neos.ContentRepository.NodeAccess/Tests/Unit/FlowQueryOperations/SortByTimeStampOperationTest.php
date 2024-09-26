<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeAccess\Tests\Unit\FlowQueryOperations;

use Neos\ContentRepository\NodeAccess\FlowQueryOperations\SortByTimeStampOperation;
use Neos\Eel\FlowQuery\FlowQueryException;
use PHPUnit\Framework\TestCase;

/**
 * SortOperation test
 */
class SortByTimeStampOperationTest extends TestCase
{
    /**
     * @test+
     */
    public function callWithoutArgumentsCausesException()
    {
        $this->expectException(FlowQueryException::class);
        $flowQuery = new \Neos\Eel\FlowQuery\FlowQuery([]);
        $operation = new SortByTimeStampOperation();
        $operation->evaluate($flowQuery, []);
    }

    /**
     * @test+
     */
    public function callWithoutWrongTimeStampArgumentsCausesException()
    {
        $this->expectException(FlowQueryException::class);
        $flowQuery = new \Neos\Eel\FlowQuery\FlowQuery([]);
        $operation = new SortByTimeStampOperation();
        $operation->evaluate($flowQuery, ['erstellt']);
    }

    /**
     * @test
     */
    public function invalidSortDirectionCausesException()
    {
        $this->expectException(FlowQueryException::class);
        $flowQuery = new \Neos\Eel\FlowQuery\FlowQuery([]);
        $operation = new SortByTimeStampOperation();
        $operation->evaluate($flowQuery, ['created', 'FOO']);
    }
}
