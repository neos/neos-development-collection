<?php
namespace Neos\ContentRepository\Tests\Functional\Eel\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Tests\Functional\AbstractNodeTest;

/**
 * Functional test case which tests FlowQuery NextUntilOperation
 */
class NextUntilOperationTest extends AbstractNodeTest
{
    /**
     * @return array
     */
    public function nextUntilOperationDataProvider()
    {
        return array(
            array(
                'currentNodePaths' => array('/a/a1'),
                'subject' => '[instanceof Neos.ContentRepository.Testing:NodeType]',
                'expectedNodePaths' => array('/a/a2'),
                'unexpectedNodePaths' => array('/a/a1','/a/a3','/a/a4')
            ),
            array(
                'currentNodePaths' => array('/a/a3'),
                'subject' => '[instanceof Neos.ContentRepository.Testing:NodeType]',
                'expectedNodePaths' => array('/a/a4'),
                'unexpectedNodePaths' => array('/b')
            ),
            array(
                'currentNodePaths' => array('/a/a4'),
                'subject' => '[instanceof Neos.ContentRepository.Testing:NodeType]',
                'expectedNodePaths' => array(),
                'unexpectedNodePaths' => array('/a/a5')
            ),
            array(
                'currentNodePaths' => array('/b/b1'),
                'subject' => 'b3[instanceof Neos.ContentRepository.Testing:NodeType]',
                'expectedNodePaths' => array('/b/b2'),
                'unexpectedNodePaths' => array('/b/b4')
            ),
            array(
                'currentNodePaths' => array('/a/a1'),
                'subject' => '',
                'expectedNodePaths' => array('/a/a2','/a/a3','/a/a4','/a/a5'),
                'unexpectedNodePaths' => array('/a/a1','/b')
            )
        );
    }

    /**
     * Tests on a tree:
     *
     * a
     *   a1
     *   a2
     *   a3 (testNodeType)
     *   a4
     *   a5 (testNodeType)
     * b
     *   b1
     *   b2
     *   b3 (testNodeType3)
     *   b4
     *
     * @test
     * @dataProvider nextUntilOperationDataProvider()
     */
    public function nextUntilOperationTests(array $currentNodePaths, $subject, array $expectedNodePaths, array $unexpectedNodePaths)
    {
        $nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $testNodeType = $nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeType');


        $rootNode = $this->node->getNode('/');
        $nodeA = $rootNode->createNode('a');
        $nodeA->createNode('a1');
        $nodeA->createNode('a2');
        $nodeA->createNode('a3', $testNodeType);
        $nodeA->createNode('a4');
        $nodeA->createNode('a5', $testNodeType);
        $nodeB = $rootNode->createNode('b');
        $nodeB->createNode('b1');
        $nodeB->createNode('b2');
        $nodeB->createNode('b3', $testNodeType);
        $nodeB->createNode('b4');


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
        $result = $q->nextUntil($subject)->get();

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
