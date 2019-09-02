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
        return [
            [
                'currentNodePaths' => ['/a', '/b'],
                'subject' => 'a1',
                'expectedNodePaths' => ['/a']
            ],
            [
                'currentNodePaths' => ['/a'],
                'subject' => 'b1',
                'expectedNodePaths' => []
            ],
            [
                'currentNodePaths' => ['/b'],
                'subject' => '[instanceof Neos.ContentRepository.Testing:NodeType]',
                'expectedNodePaths' => ['/b']
            ],
            [
                'currentNodePaths' => ['/b'],
                'subject' => 'b1[instanceof Neos.ContentRepository.Testing:NodeType]',
                'expectedNodePaths' => ['/b']
            ],
            [
                'currentNodePaths' => ['/b'],
                'subject' => '',
                'expectedNodePaths' => []
            ],
            [
                'currentNodePaths' => ['/a', '/b'],
                'subject' => ['/a/a1'],
                'expectedNodePaths' => ['/a']
            ],
            [
                'currentNodePaths' => ['/b'],
                'subject' => ['/b/b1/b1a'],
                'expectedNodePaths' => ['/b']
            ],
            [
                'currentNodePaths' => ['/a', '/b'],
                'subject' => ['/c'],
                'expectedNodePaths' => []
            ],
            [
                'currentNodePaths' => [],
                'subject' => ['/c'],
                'expectedNodePaths' => []
            ]
        ];
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

        $currentNodes = [];
        foreach ($currentNodePaths as $currentNodePath) {
            $currentNodes[] = $rootNode->getNode($currentNodePath);
        }

        if (is_array($subject)) {
            $subjectNodes = [];
            foreach ($subject as $subjectNodePath) {
                $subjectNodes[] = $rootNode->getNode($subjectNodePath);
            }
            $subject = $subjectNodes;
        }

        $q = new FlowQuery($currentNodes);
        $result = $q->has($subject)->get();

        if ($expectedNodePaths === []) {
            self::assertEmpty($result);
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
