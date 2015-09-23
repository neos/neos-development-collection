<?php
namespace TYPO3\TYPO3CR\Tests\Unit\FlowQueryOperations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.TYPO3CR".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\TYPO3CR\Eel\FlowQueryOperations\CacheLifetimeOperation;

/**
 * Testcase for the TYPO3CR FlowQuery CacheLifetimeOperation
 */
class CacheLifetimeOperationTest extends \TYPO3\Flow\Tests\UnitTestCase
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

    public function setUp()
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

        $this->dateFixtures = array(
            'now' => $this->now,
            '+1D' => $inOneDay,
            '+2D' => $inTwoDays,
            '-1D' => $oneDayAgo
        );
    }

    /**
     * @test
     */
    public function canEvaluateReturnsTrueIfNodeIsInContext()
    {
        $mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

        $result = $this->operation->canEvaluate(array($mockNode));
        $this->assertTrue($result);
    }

    public function nodePropertiesAndLifetime()
    {
        return array(
            'Minimum in hiddenBeforeDateTime' => array(
                array(
                    array('hiddenBeforeDateTime' => '+1D', 'hiddenAfterDateTime' => null),
                    array('hiddenBeforeDateTime' => null, 'hiddenAfterDateTime' => '+2D')
                ),
                86400
            ),
            'Minimum in hiddenAfterDateTime' => array(
                array(
                    array('hiddenBeforeDateTime' => '+2D', 'hiddenAfterDateTime' => null),
                    array('hiddenBeforeDateTime' => null, 'hiddenAfterDateTime' => '+1D')
                ),
                86400
            ),
            'Past hiddenBeforeDateTime' => array(
                array(
                    array('hiddenBeforeDateTime' => '-1D', 'hiddenAfterDateTime' => null)
                ),
                null
            ),
            'Past hiddenBeforeDateTime and future hiddenBeforeDateTime' => array(
                array(
                    array('hiddenBeforeDateTime' => '-1D', 'hiddenAfterDateTime' => null),
                    array('hiddenBeforeDateTime' => '+2D', 'hiddenAfterDateTime' => null)
                ),
                2*86400
            ),
            'Hidden just now' => array(
                array(
                    array('hiddenBeforeDateTime' => 'now', 'hiddenAfterDateTime' => null)
                ),
                null
            ),
            'Hidden just now with hiddenAfterDateTime' => array(
                array(
                    array('hiddenBeforeDateTime' => 'now', 'hiddenAfterDateTime' => null),
                    array('hiddenBeforeDateTime' => null, 'hiddenAfterDateTime' => '+1D')
                ),
                86400
            ),
            'No dates set' => array(
                array(
                    array('hiddenBeforeDateTime' => null, 'hiddenAfterDateTime' => null),
                    array('hiddenBeforeDateTime' => null, 'hiddenAfterDateTime' => null)
                ),
                null
            ),
            'Empty array of nodes' => array(
                array(),
                null
            )
        );
    }

    /**
     * @test
     * @dataProvider nodePropertiesAndLifetime
     */
    public function evaluateReturnsMinimumOfFutureHiddenDates($nodes, $expectedLifetime)
    {
        $mockFlowQuery = $this->buildFlowQueryWithNodesInContext($nodes);
        $lifetime = $this->operation->evaluate($mockFlowQuery, array());

        if ($expectedLifetime === null) {
            $this->assertNull($lifetime);
        } else {
            $this->assertEquals($expectedLifetime, $lifetime, 'Lifetime did not match expected value +/- 1', 1);
        }
    }

    /**
     * @param array $nodes Array of nodes with properties for the FlowQuery context
     * @return FlowQuery
     */
    protected function buildFlowQueryWithNodesInContext($nodes)
    {
        $contextValues = array();
        foreach ($nodes as $nodeProperties) {
            $mockNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
            $mockNode->expects($this->any())->method('getHiddenBeforeDateTime')->will($this->returnValue($nodeProperties['hiddenBeforeDateTime'] !== null ? $this->dateFixtures[$nodeProperties['hiddenBeforeDateTime']] : null));
            $mockNode->expects($this->any())->method('getHiddenAfterDateTime')->will($this->returnValue($nodeProperties['hiddenAfterDateTime'] !== null ? $this->dateFixtures[$nodeProperties['hiddenAfterDateTime']] : null));

            $contextValues[] = $mockNode;
        }

        $mockFlowQuery = $this->getMockBuilder('TYPO3\Eel\FlowQuery\FlowQuery')->disableOriginalConstructor()->getMock();
        $mockFlowQuery->expects($this->any())->method('getContext')->will($this->returnValue($contextValues));
        return $mockFlowQuery;
    }
}
