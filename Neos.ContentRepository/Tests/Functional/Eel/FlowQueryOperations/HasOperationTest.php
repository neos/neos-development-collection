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
 * Functional test case which tests FlowQuery HasOperation
 */
class HasOperationTest extends AbstractNodeTest
{
    /**
     * @return array
     */
    public function hasOperationDataProvider()
    {
        return array(
            array(
                'currentNodePaths' => array('/a', '/b'),
                'subject' => 'a1',
                'expectedNodePaths' => array('/a')
            ),
            array(
                'currentNodePaths' => array('/a'),
                'subject' => 'b1',
                'expectedNodePaths' => array()
            ),
            array(
                'currentNodePaths' => array('/b'),
                'subject' => '[instanceof Neos.ContentRepository.Testing:NodeType]',
                'expectedNodePaths' => array('/b')
            ),
            array(
                'currentNodePaths' => array('/b'),
                'subject' => 'b1[instanceof Neos.ContentRepository.Testing:NodeType]',
                'expectedNodePaths' => array('/b')
            ),
            array(
                'currentNodePaths' => array('/b'),
                'subject' => '',
                'expectedNodePaths' => array()
            ),
            array(
                'currentNodePaths' => array('/a', '/b'),
                'subject' => array('/a/a1'),
                'expectedNodePaths' => array('/a')
            ),
            array(
                'currentNodePaths' => array('/b'),
                'subject' => array('/b/b1/b1a'),
                'expectedNodePaths' => array('/b')
            ),
            array(
                'currentNodePaths' => array('/a', '/b'),
                'subject' => array('/c'),
                'expectedNodePaths' => array()
            ),
            array(
                'currentNodePaths' => array(),
                'subject' => array('/c'),
                'expectedNodePaths' => array()
            )
        );
    }

    /**
     * Tests on a tree:
     *
     * a
     *   a1
     * b
     *   b1 (TestingNodeType)
     *     b1a
     * c
     *
     * @test
     * @dataProvider hasOperationDataProvider()
     */
    public function hasOperationTests(array $currentNodePaths, $subject, array $expectedNodePaths)
    {
        $nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $testNodeType1 = $nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeType');

        $rootNode = $this->node->getNode('/');
        $nodeA = $rootNode->createNode('a');
        $nodeA->createNode('a1');
        $nodeB = $rootNode->createNode('b');
        $nodeB1 = $nodeB->createNode('b1', $testNodeType1);
        $nodeB1->createNode('b1a');
        $rootNode->createNode('c');

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
        $result = $q->has($subject)->get();

        if ($expectedNodePaths === array()) {
            $this->assertEmpty($result);
        } else {
            foreach ($expectedNodePaths as $expectedNodePath) {
                $expectedNode = $rootNode->getNode($expectedNodePath);
                if (!in_array($expectedNode, $result)) {
                    $this->fail(sprintf('Expected result to contain node "%s"', $expectedNodePath));
                }
            }
        }
    }
}
