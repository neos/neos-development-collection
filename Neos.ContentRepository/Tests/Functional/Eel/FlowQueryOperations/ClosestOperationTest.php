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
 * Functional test case which tests FlowQuery ClosestOperation
 */
class ClosestOperationTest extends AbstractNodeTest
{
    /**
     * @return array
     */
    public function closestOperationDataProvider()
    {
        return [
            [
                'currentNodePath' => '/b/b1/b1a',
                'nodeTypeFilter' => '[instanceof Neos.ContentRepository.Testing:NodeType]',
                'expectedNodePath' => '/b/b1'
            ],
            [
                'currentNodePath' => '/b/b1/b1a',
                'nodeTypeFilter' => 'InvalidFilter',
                'expectedNodePath' => null
            ],
            [
                'currentNodePath' => '/b/b3/b3b',
                'nodeTypeFilter' => '[instanceof Neos.ContentRepository.Testing:NodeTypeWithSubnodes]',
                'expectedNodePath' => '/b/b3'
            ],
            [
                'currentNodePath' => '/b/b1/b1a',
                'nodeTypeFilter' => '[instanceof Neos.ContentRepository.Testing:NodeTypeWithSubnodes]',
                'expectedNodePath' => null
            ],
            [
                'currentNodePath' => '/b/b1',
                'nodeTypeFilter' => '[instanceof Neos.ContentRepository.Testing:NodeTypeWithSubnodes]',
                'expectedNodePath' => null
            ],
            [
                'currentNodePath' => '/b/b3/b3a',
                'nodeTypeFilter' => '[instanceof Neos.ContentRepository.Testing:NodeType]',
                'expectedNodePath' => '/b/b3/b3a'
            ]
        ];
    }

    /**
     * Tests on a tree:
     *
     * a
     *   a1
     *   a2
     * b (TestingNodeType)
     *   b1 (TestingNodeType)
     *     b1a
     *   b2
     *   b3 (TestingNodeTypeWithSubnodes)
     *     b3a (TestingNodeType)
     *     b3b
     *
     * @test
     * @dataProvider closestOperationDataProvider
     */
    public function closestOperationTests($currentNodePath, $nodeTypeFilter, $expectedNodePath)
    {
        $nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $testNodeType1 = $nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeType');
        $testNodeType2 = $nodeTypeManager->getNodeType('Neos.ContentRepository.Testing:NodeTypeWithSubnodes');

        $rootNode = $this->node->getNode('/');
        $nodeA = $rootNode->createNode('a');
        $nodeA->createNode('a1');
        $nodeA->createNode('a2');
        $nodeB = $rootNode->createNode('b', $testNodeType1);
        $nodeB1 = $nodeB->createNode('b1', $testNodeType1);
        $nodeB1->createNode('b1a');
        $nodeB->createNode('b2');
        $nodeB3 = $nodeB->createNode('b3', $testNodeType2);
        $nodeB3->createNode('b3a', $testNodeType1);
        $nodeB3->createNode('b3b');

        $currentNode = $rootNode->getNode($currentNodePath);
        $q = new FlowQuery([$currentNode]);
        $actualNode = $q->closest($nodeTypeFilter)->get(0);

        if ($expectedNodePath === null) {
            if ($actualNode !== null) {
                $this->fail('Expected resulting node to be NULL');
            }
            self::assertNull($actualNode);
        } else {
            self::assertSame($expectedNodePath, $actualNode->getPath());
        }
    }
}
