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
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Tests\Functional\AbstractNodeTest;

/**
 * Functional test case which tests FlowQuery FindOperation
 */
class FindOperationTest extends AbstractNodeTest
{
    /**
     * @test
     * @expectedException \Neos\Eel\FlowQuery\FlowQueryException
     */
    public function findByNodeIdentifierThrowsExceptionOnInvalidIdentifier()
    {
        $q = new FlowQuery(array($this->node));
        $q->find('#_test')->get(0);
    }

    /**
     * @return array
     */
    public function identifierFilterExamples()
    {
        return [
            'Single identifier' => ['#30e893c1-caef-0ca5-b53d-e5699bb8e506', ['/sites/example/home/about-us']],
            'Multiple identifiers' => ['#30e893c1-caef-0ca5-b53d-e5699bb8e506, #25eaba22-b8ed-11e3-a8b5-c82a1441d728', ['/sites/example/home/about-us', '/sites/example/home/products']],
            'Identifier with attribute filter' => ['#30e893c1-caef-0ca5-b53d-e5699bb8e506[title *= "Test"], #25eaba22-b8ed-11e3-a8b5-c82a1441d728[title *= "Test"]', ['/sites/example/home/about-us']]
        ];
    }

    /**
     * @test
     * @dataProvider identifierFilterExamples

     * @param string $filter
     * @param array $expectedNodePaths
     */
    public function identifierFilterIsSupported($filter, array $expectedNodePaths)
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->find($filter)->get();
        $foundNodePaths = array_map(function (NodeInterface $node) {
            return $node->getPath();
        }, $foundNodes);
        $this->assertSame($expectedNodePaths, $foundNodePaths);
    }

    /**
     * @return array
     */
    public function pathAndPropertyNameFilterExamples()
    {
        return [
            'Absolute path' => ['/sites/example/home', ['/sites/example/home']],
            'Absolute path with attribute filter' => ['/sites/example/home/about-us[title *= "Test"], /sites/example/home/products[title *= "Test"]', ['/sites/example/home/about-us']],
            'Property name' => ['about-us', ['/sites/example/home/about-us']],
            'Multiple property names' => ['about-us, products', ['/sites/example/home/about-us', '/sites/example/home/products']],
            'Property name with attribute filter' => ['about-us[title *= "Test"], products[title *= "Test"]', ['/sites/example/home/about-us']],
        ];
    }

    /**
     * @test
     * @dataProvider pathAndPropertyNameFilterExamples

     * @param string $filter
     * @param array $expectedNodePaths
     */
    public function pathAndPropertyNameFilterIsSupported($filter, array $expectedNodePaths)
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->find($filter)->get();
        $foundNodePaths = array_map(function (NodeInterface $node) {
            return $node->getPath();
        }, $foundNodes);
        $this->assertSame($expectedNodePaths, $foundNodePaths);
    }

    /**
     * @return array
     */
    public function attributeFilterExamples()
    {
        return [
            'Single instanceof' => [
                '[instanceof Neos.ContentRepository.Testing:Headline]',
                [
                    '/sites/example/home/main/dummy44/column1/dummy42a',
                    '/sites/example/home/teaser/dummy42a',
                    '/sites/example/home/sidebar/dummy42a',
                    '/sites/example/home/main/dummy42a',
                    '/sites/example/home/main/dummy44/column0/dummy42a'
                ]
            ],
            'Multiple instanceof' => [
                '[instanceof Neos.ContentRepository.Testing:ThreeColumn], [instanceof Neos.ContentRepository.Testing:Html]',
                [
                    '/sites/example/home/main/dummy44/column0/dummy43',
                    '/sites/example/home/sidebar/dummy43',
                    '/sites/example/home/main/dummy43',
                    '/sites/example/home/main/dummy44'
                ]
            ],
            'Instanceof with attribute filter' => [
                '[instanceof Neos.ContentRepository.Testing:Headline][title *= "Welcome"]',
                [
                    '/sites/example/home/teaser/dummy42a'
                ]
            ]
        ];
    }

    /**
     * @test
     * @expectedException \Neos\Eel\FlowQuery\FlowQueryException
     */
    public function findWithNonInstanceofAttributeFilterAsFirstPartThrowsException()
    {
        $q = new FlowQuery(array($this->node));
        $q->find('[title *= "Welcome"][instanceof Neos.ContentRepository.Testing:Headline]')->get(0);
    }

    /**
     * @test
     * @dataProvider attributeFilterExamples

     * @param string $filter
     * @param array $expectedNodePaths
     */
    public function attributeFilterIsSupported($filter, array $expectedNodePaths)
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->find($filter)->get();
        $foundNodePaths = array_map(function (NodeInterface $node) {
            return $node->getPath();
        }, $foundNodes);
        $this->assertSame($expectedNodePaths, $foundNodePaths);
    }

    /**
     * @test
     */
    public function findByNodeIdentifierReturnsCorrectNodeInContext()
    {
        $q = new FlowQuery(array($this->node));
        $foundNode = $q->find('#30e893c1-caef-0ca5-b53d-e5699bb8e506')->get(0);
        $this->assertSame($this->node->getNode('about-us'), $foundNode);

        $testContext = $this->contextFactory->create(array('workspaceName' => 'test'));

        $testNode = $testContext->getNode('/sites/example/home');
        $testQ = new FlowQuery(array($testNode));
        $testFoundNode = $testQ->find('#30e893c1-caef-0ca5-b53d-e5699bb8e506')->get(0);
        $this->assertSame($testNode->getNode('about-us'), $testFoundNode);

        $this->assertNotSame($foundNode, $testFoundNode);
    }

    /**
     * @test
     */
    public function findByNodeWithInstanceofFilterReturnsMatchingNodesRecursively()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->find('[instanceof Neos.ContentRepository.Testing:Text]')->get();
        $this->assertGreaterThan(0, count($foundNodes));
        foreach ($foundNodes as $foundNode) {
            $this->assertSame($foundNode->getNodeType()->getName(), 'Neos.ContentRepository.Testing:Text');
        }
    }

    /**
     * @test
     */
    public function findByNodeWithMultipleInstanceofFilterReturnsMatchingNodesRecursively()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->find('[instanceof Neos.ContentRepository.Testing:Text],[instanceof Neos.ContentRepository.Testing:Page]')->get();
        $this->assertGreaterThan(0, count($foundNodes));
        $foundNodeTypes = array();
        foreach ($foundNodes as $foundNode) {
            $nodeType = $foundNode->getNodeType()->getName();
            if (!in_array($nodeType, $foundNodeTypes)) {
                $foundNodeTypes[] = $nodeType;
            }
        }
        sort($foundNodeTypes);
        $this->assertSame($foundNodeTypes, array('Neos.ContentRepository.Testing:Page', 'Neos.ContentRepository.Testing:Text'));
    }

    /**
     * @test
     */
    public function findByNodeWithAbsolutePathReturnsCorrectNode()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->find('/sites/example/home/main/dummy42a')->get();
        $this->assertEquals(1, count($foundNodes));
        $foundNode = $foundNodes[0];
        $this->assertSame('b1e0e78d-04f3-8fc3-e3d1-e2399f831312', $foundNode->getIdentifier());
    }

    /**
     * @test
     */
    public function findByNodeWithPathReturnsEmptyArrayIfNotFound()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->find('/sites/example/home/main/limbo')->get();
        $this->assertEmpty($foundNodes);
    }

    /**
     * @test
     */
    public function findOperationEvaluatesWithEmptyContext()
    {
        $q = new FlowQuery(array());
        $foundNodes = $q->find('/sites/example/home/main/limbo')->get();
        $this->assertEmpty($foundNodes);
    }

    /**
     * @test
     * @expectedException \Neos\Eel\FlowQuery\FlowQueryException
     */
    public function findOperationThrowsExceptionOnAtLeastOneInvalidContext()
    {
        $q = new FlowQuery(array($this->node, '1'));
        $q->find('/sites/example/home/main/limbo')->get();
    }

    /**
     * @test
     */
    public function findByNodeWithNodeNameReturnsCorrectNode()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->find('main')->get();
        $this->assertEquals(1, count($foundNodes));
        $foundNode = $foundNodes[0];
        $this->assertSame('f66b3871-515f-7f54-fb1d-1c108040b2c0', $foundNode->getIdentifier());
    }

    /**
     * @test
     */
    public function findByNodeWithRelativePathReturnsCorrectNode()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->find('main/dummy42a')->get();
        $this->assertEquals(1, count($foundNodes));
        $foundNode = $foundNodes[0];
        $this->assertSame('b1e0e78d-04f3-8fc3-e3d1-e2399f831312', $foundNode->getIdentifier());
    }

    /**
     * @test
     */
    public function findByMultipleNodesReturnsMatchingNodesForAllNodes()
    {
        $testContext = $this->contextFactory->create(array('workspaceName' => 'test'));
        $testNodeA = $testContext->getNode('/sites/example/home/main/dummy44');
        $testNodeB = $testContext->getNode('/sites/example/home/main/dummy45');
        $q = new FlowQuery(array($testNodeA, $testNodeB));

        $foundNodes = $q->find('[instanceof Neos.ContentRepository.Testing:Headline],[instanceof Neos.ContentRepository.Testing:ListItem]')->get();
        $this->assertGreaterThan(0, count($foundNodes));
        $foundChildrenOfA = false;
        $foundChildrenOfB = false;

        foreach ($foundNodes as $foundNode) {
            if (strpos($foundNode->getPath(), $testNodeA->getPath()) === 0 && $foundNode->getNodeType()->getName() === 'Neos.ContentRepository.Testing:Headline') {
                $foundChildrenOfA = true;
            } elseif (strpos($foundNode->getPath(), $testNodeB->getPath()) === 0 && $foundNode->getNodeType()->getName() === 'Neos.ContentRepository.Testing:ListItem') {
                $foundChildrenOfB = true;
            }
        }

        $this->assertTrue($foundChildrenOfA);
        $this->assertTrue($foundChildrenOfB);
    }

    /**
     * @test
     */
    public function findByNodeWithInstanceofFilterAppliesAdditionalAttributeFilter()
    {
        $q = new FlowQuery(array($this->node));
        $foundNodes = $q->find('[instanceof Neos.ContentRepository.Testing:Text][text*="Twitter"]')->get();
        $this->assertCount(1, $foundNodes);
    }
}
