<?php
namespace Neos\ContentRepository\Tests\Unit\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Eel\FlowQueryOperations\CacheLifetimeOperation;
use Neos\Eel\FlowQuery\FlowQuery;

/**
 * Testcase for the ContentRepository FlowQuery CacheLifetimeOperation
 */
class CacheLifetimeOperationTest extends AbstractQueryOperationsTest
{
    /**
     * @var CacheLifetimeOperation
     */
    protected $operation;

    /**
     * @var \DateTime
     */
    protected $now;

    /**
     * @var array
     */
    protected $dateFixtures;

    public function setUp(): void
    {
        $this->operation = new CacheLifetimeOperation();
        $this->now = new \DateTime();
        $this->inject($this->operation, 'now', $this->now);

        $inOneDay = clone $this->now;
        $inOneDay->add(new \DateInterval('P1D'));
        $inTwoDays = clone $this->now;
        $inTwoDays->add(new \DateInterval('P2D'));
        $oneDayAgo = clone $this->now;
        $oneDayAgo->sub(new \DateInterval('P1D'));

        $this->dateFixtures = [
            'now' => $this->now,
            '+1D' => $inOneDay,
            '+2D' => $inTwoDays,
            '-1D' => $oneDayAgo
        ];
    }

    /**
     * @test
     */
    public function canEvaluateReturnsTrueIfNodeIsInContext()
    {
        $mockNode = $this->mockNode('node');

        $result = $this->operation->canEvaluate([$mockNode]);
        self::assertTrue($result);
    }

    public function nodePropertiesAndLifetime()
    {
        return [
            'Minimum in hiddenBeforeDateTime' => [
                [
                    ['hiddenBeforeDateTime' => '+1D', 'hiddenAfterDateTime' => null],
                    ['hiddenBeforeDateTime' => null, 'hiddenAfterDateTime' => '+2D']
                ],
                86400
            ],
            'Minimum in hiddenAfterDateTime' => [
                [
                    ['hiddenBeforeDateTime' => '+2D', 'hiddenAfterDateTime' => null],
                    ['hiddenBeforeDateTime' => null, 'hiddenAfterDateTime' => '+1D']
                ],
                86400
            ],
            'Past hiddenBeforeDateTime' => [
                [
                    ['hiddenBeforeDateTime' => '-1D', 'hiddenAfterDateTime' => null]
                ],
                null
            ],
            'Past hiddenBeforeDateTime and future hiddenBeforeDateTime' => [
                [
                    ['hiddenBeforeDateTime' => '-1D', 'hiddenAfterDateTime' => null],
                    ['hiddenBeforeDateTime' => '+2D', 'hiddenAfterDateTime' => null]
                ],
                2*86400
            ],
            'Hidden just now' => [
                [
                    ['hiddenBeforeDateTime' => 'now', 'hiddenAfterDateTime' => null]
                ],
                null
            ],
            'Hidden just now with hiddenAfterDateTime' => [
                [
                    ['hiddenBeforeDateTime' => 'now', 'hiddenAfterDateTime' => null],
                    ['hiddenBeforeDateTime' => null, 'hiddenAfterDateTime' => '+1D']
                ],
                86400
            ],
            'No dates set' => [
                [
                    ['hiddenBeforeDateTime' => null, 'hiddenAfterDateTime' => null],
                    ['hiddenBeforeDateTime' => null, 'hiddenAfterDateTime' => null]
                ],
                null
            ],
            'Empty array of nodes' => [
                [],
                null
            ]
        ];
    }

    /**
     * @test
     * @dataProvider nodePropertiesAndLifetime
     */
    public function evaluateReturnsMinimumOfFutureHiddenDates($nodes, $expectedLifetime)
    {
        $mockFlowQuery = $this->buildFlowQueryWithNodesInContext($nodes);
        $lifetime = $this->operation->evaluate($mockFlowQuery, []);

        if ($expectedLifetime === null) {
            self::assertNull($lifetime);
        } else {
            self::assertEqualsWithDelta($expectedLifetime, $lifetime, 1, 'Lifetime did not match expected value +/- 1');
        }
    }

    /**
     * @param array $nodes Array of nodes with properties for the FlowQuery context
     * @return FlowQuery
     */
    protected function buildFlowQueryWithNodesInContext($nodes)
    {
        $contextValues = [];
        foreach ($nodes as $nodeProperties) {
            $mockNode = $this->createMock(NodeInterface::class);
            $mockNode->expects(self::any())->method('getHiddenBeforeDateTime')->will(self::returnValue($nodeProperties['hiddenBeforeDateTime'] !== null ? $this->dateFixtures[$nodeProperties['hiddenBeforeDateTime']] : null));
            $mockNode->expects(self::any())->method('getHiddenAfterDateTime')->will(self::returnValue($nodeProperties['hiddenAfterDateTime'] !== null ? $this->dateFixtures[$nodeProperties['hiddenAfterDateTime']] : null));

            $contextValues[] = $mockNode;
        }

        $mockFlowQuery = $this->getMockBuilder(FlowQuery::class)->disableOriginalConstructor()->getMock();
        $mockFlowQuery->expects(self::any())->method('getContext')->will(self::returnValue($contextValues));
        return $mockFlowQuery;
    }
}
