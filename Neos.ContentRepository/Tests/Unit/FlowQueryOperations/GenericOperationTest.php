<?php

namespace Neos\ContentRepository\Tests\Unit\FlowQueryOperations;

use Neos\ContentRepository\Eel\FlowQueryOperations\CacheLifetimeOperation;
use Neos\ContentRepository\Eel\FlowQueryOperations\ChildrenOperation;
use Neos\ContentRepository\Eel\FlowQueryOperations\ClosestOperation;
use Neos\ContentRepository\Eel\FlowQueryOperations\ContextOperation;
use Neos\ContentRepository\Eel\FlowQueryOperations\FilterOperation;
use Neos\ContentRepository\Eel\FlowQueryOperations\HasOperation;
use Neos\ContentRepository\Eel\FlowQueryOperations\NextAllOperation;
use Neos\ContentRepository\Eel\FlowQueryOperations\NextOperation;
use Neos\ContentRepository\Eel\FlowQueryOperations\NextUntilOperation;
use Neos\ContentRepository\Eel\FlowQueryOperations\ParentOperation;
use Neos\ContentRepository\Eel\FlowQueryOperations\ParentsOperation;
use Neos\ContentRepository\Eel\FlowQueryOperations\ParentsUntilOperation;
use Neos\ContentRepository\Eel\FlowQueryOperations\PrevUntilOperation;
use Neos\ContentRepository\Eel\FlowQueryOperations\PropertyOperation;
use Neos\ContentRepository\Eel\FlowQueryOperations\SiblingsOperation;

class GenericOperationTest extends AbstractQueryOperationsTest
{
    public function contextDataProvider(): array
    {
        $firstNodeInLevel = $this->mockNode('node1');
        $secondNodeInLevel = $this->mockNode('node2');

        $operationClasses = [
            CacheLifetimeOperation::class,
            ChildrenOperation::class,
            ClosestOperation::class,
            ContextOperation::class,
            FilterOperation::class,
            HasOperation::class,
            NextAllOperation::class,
            NextOperation::class,
            NextUntilOperation::class,
            ParentOperation::class,
            ParentsOperation::class,
            ParentsUntilOperation::class,
            PrevUntilOperation::class,
            PropertyOperation::class,
            SiblingsOperation::class,
        ];

        $testCases = [
            'noNodeInArray' => [
                'context' => ['noNode'],
                'expected' => false,
            ],
            'arrayWithIntegerKeysStartingOnZero' => [
                'context' => [$firstNodeInLevel, $secondNodeInLevel],
                'expected' => true,
            ],
            'arrayStartsWithIntegerOne' => [
                'context' => [1 => $secondNodeInLevel],
                'expected' => true,
            ],
            'traversableNoNode' => [
                'context' => new \ArrayIterator(['noNode']),
                'expected' => false,
            ],
            'traversableNode' => [
                'context' =>  new \ArrayIterator([$firstNodeInLevel, $secondNodeInLevel]),
                'expected' => true,
            ],
            'noArray' => [
                'context' => 'noArray',
                'expected' => false,
            ],
        ];

        $testData = [];

        foreach ($operationClasses as $operationClass) {
            foreach ($testCases as $testCaseName => $testCase) {
                $testData[$testCaseName . 'For' . $operationClass] = [
                    'operationClass' => $operationClass,
                    'context' => $testCase['context'],
                    'expected' => $testCase['expected'],
                ];
            }
        }

        return $testData;
    }

    /**
     * @test
     * @dataProvider contextDataProvider
     */
    public function checkIfFirstElementInCanEvaluateIsANode(string $operationClass, $context, bool $expected): void
    {
        $operation = new $operationClass();
        self::assertEquals($expected, $operation->canEvaluate($context), 'For Operation ' . $operationClass);
    }
}
