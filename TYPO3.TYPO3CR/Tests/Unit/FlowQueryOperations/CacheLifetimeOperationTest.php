<?php
namespace TYPO3\TYPO3CR\Tests\Unit\FlowQueryOperations;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
