<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Eel\FlowQueryOperations;

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
use TYPO3\TYPO3CR\Tests\Functional\AbstractNodeTest;

/**
 * Functional test case which tests FlowQuery ParentsUntilOperation
 */
class ParentsUntilOperationTest extends AbstractNodeTest
{
    /**
     * @return array
     */
    public function parentsUntilOperationDataProvider()
    {
        return array(
            array(
                'currentNodePaths' => array('/b/b3'),
                'subject' => '',
                'expectedNodePaths' => array('/b'),
                'unexpectedNodePaths' => array('/a','/a/a5','/a/a3','/a/a2,')
            ),
            array(
                'currentNodePaths' => array('/b/b3/b3b'),
                'subject' => '',
                'expectedNodePaths' => array('/b/b3','/b'),
                'unexpectedNodePaths' => array('/b2','/b3/b3a','/a/a2,','/a')
            ),
            array(
                'currentNodePaths' => array('/b/b3/b3b'),
                'subject' => 'b',
                'expectedNodePaths' => array('/b/b3'),
                'unexpectedNodePaths' => array('/b2','/b3/b3a','/a/a2,','/a','/b')
            ),
            array(
                'currentNodePaths' => array('/b/b3/b3b'),
                'subject' => '[instanceof TYPO3.TYPO3CR.Testing:NodeType]',
                'expectedNodePaths' => array('/b/b3'),
                'unexpectedNodePaths' => array('/a/a5','/a/a3','/a/a2,','/b')
            ),
            array(
                'currentNodePaths' => array('/a/a4'),
                'subject' => '',
                'expectedNodePaths' => array('/a'),
                'unexpectedNodePaths' => array('/a/a5','/a/a3','/a/a2,')
            ),
            array(
                'currentNodePaths' => array('/b/b4/b4b/b4bb/b4bba'),
                'subject' => '[instanceof TYPO3.TYPO3CR.Testing:NodeType]',
                'expectedNodePaths' => array('/b/b4/b4b/b4bb'),
                'unexpectedNodePaths' => array('b/b4','b/b4/b4b','/b/b3','/b')
            ),
        );
    }

    /**
     * Tests on a tree:
     *
     * a (Testing:NodeType)
     *   a1 (Testing:NodeType)
     *   a2
     *   a3 (Testing:NodeType)
     *   a4
     *   a5
     * b (Testing:NodeType)
     *   b1
     *   b2 (Testing:NodeType)
     *   b3
     *     b3a
     *     b3b
     *   b4 (Testing:NodeType)
     *     b4a
     *     b4b (Testing:NodeType)
     *       b4ba (Testing:NodeType)
     *       b4bb
     *         b4bba
     *
     * @test
     * @dataProvider parentsUntilOperationDataProvider()
     */
    public function parentsUntilOperationTests(array $currentNodePaths, $subject, array $expectedNodePaths, array $unexpectedNodePaths)
    {
        $nodeTypeManager = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
        $testNodeType = $nodeTypeManager->getNodeType('TYPO3.TYPO3CR.Testing:NodeType');


        $rootNode = $this->node->getNode('/');
        $nodeA = $rootNode->createNode('a', $testNodeType);
        $nodeA->createNode('a1', $testNodeType);
        $nodeA->createNode('a2');
        $nodeA->createNode('a3', $testNodeType);
        $nodeA->createNode('a4');
        $nodeA->createNode('a5');
        $nodeB = $rootNode->createNode('b', $testNodeType);
        $nodeB->createNode('b1');
        $nodeB->createNode('b2', $testNodeType);
        $nodeB3 = $nodeB->createNode('b3');
        $nodeB3->createNode('b3a');
        $nodeB3->createNode('b3b');
        $nodeB4 = $nodeB->createNode('b4', $testNodeType);
        $nodeB4->createNode('b4a');
        $nodeB4B = $nodeB4->createNode('b4b', $testNodeType);
        $nodeB4B->createNode('b4ba', $testNodeType);
        $nodeB4BB = $nodeB4B->createNode('b4bb');
        $nodeB4BB->createNode('b4bba');


        $currentNodes = array();
        foreach ($currentNodePaths as $currentNodePath) {
            $currentNodes[] = $rootNode->getNode($currentNodePath);
        }

        if (is_array($subject)) {
            $subjectNodes = array();
            foreach ($subject as $subjectNodePath) {
                $subjectNodes[] = $rootNode->getNode($subjectNodePath);
            }
            $subject = $subjectNodes;
        }

        $q = new FlowQuery($currentNodes);
        $result = $q->parentsUntil($subject)->get();

        if ($expectedNodePaths === array() && $unexpectedNodePaths === array()) {
            $this->assertEmpty($result);
        } else {
            foreach ($expectedNodePaths as $expectedNodePath) {
                $expectedNode = $rootNode->getNode($expectedNodePath);
                if (!in_array($expectedNode, $result)) {
                    $this->fail(sprintf('Expected result to contain node "%s"', $expectedNodePath));
                }
            }
            foreach ($unexpectedNodePaths as $unexpectedNodePath) {
                $unexpectedNode = $rootNode->getNode($unexpectedNodePath);
                if (in_array($unexpectedNode, $result)) {
                    $this->fail(sprintf('Expected result not to contain node "%s"', $unexpectedNodePath));
                }
            }
        }
    }
}
