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
use Neos\Eel\FlowQuery\FlowQueryException;

/**
 * Functional test case which tests FlowQuery FindOperation
 */
class FindOperationTest extends AbstractNodeTest
{
    /**
     * @test
     */
    public function findByNodeIdentifierThrowsExceptionOnInvalidIdentifier()
    {
        $this->expectException(FlowQueryException::class);
        $q = new FlowQuery([$this->node]);
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
        $q = new FlowQuery([$this->node]);
        $foundNodes = $q->find($filter)->get();
        $foundNodePaths = array_map(function (NodeInterface $node) {
            return $node->getPath();
        }, $foundNodes);
        self::assertSame($expectedNodePaths, $foundNodePaths);
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
        $q = new FlowQuery([$this->node]);
        $foundNodes = $q->find($filter)->get();
        $foundNodePaths = array_map(function (NodeInterface $node) {
            return $node->getPath();
        }, $foundNodes);
        self::assertSame($expectedNodePaths, $foundNodePaths);
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
     */
    public function findWithNonInstanceofAttributeFilterAsFirstPartThrowsException()
    {
        $this->expectException(FlowQueryException::class);
        $q = new FlowQuery([$this->node]);
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
        $q = new FlowQuery([$this->node]);
        $foundNodes = $q->find($filter)->get();
        $foundNodePaths = array_map(function (NodeInterface $node) {
            return $node->getPath();
        }, $foundNodes);
        self::assertSame($expectedNodePaths, $foundNodePaths);
    }

    /**
     * @test
     */
    public function findByNodeIdentifierReturnsCorrectNodeInContext()
    {
        $this->authenticateRoles(['Neos.ContentRepository:TestingAdministrator']);
        $q = new FlowQuery([$this->node]);
        $foundNode = $q->find('#30e893c1-caef-0ca5-b53d-e5699bb8e506')->get(0);
        self::assertSame($this->node->getNode('about-us'), $foundNode);

        $testContext = $this->contextFactory->create(['workspaceName' => 'test']);

        $testNode = $testContext->getNode('/sites/example/home');
        $testQ = new FlowQuery([$testNode]);
        $testFoundNode = $testQ->find('#30e893c1-caef-0ca5-b53d-e5699bb8e506')->get(0);
        self::assertSame($testNode->getNode('about-us'), $testFoundNode);

        self::assertNotSame($foundNode, $testFoundNode);
    }

    /**
     * @test
     */
    public function findByNodeWithInstanceofFilterReturnsMatchingNodesRecursively()
    {
        $q = new FlowQuery([$this->node]);
        $foundNodes = $q->find('[instanceof Neos.ContentRepository.Testing:Text]')->get();
        $this->assertGreaterThan(0, count($foundNodes));
        foreach ($foundNodes as $foundNode) {
            self::assertSame($foundNode->getNodeType()->getName(), 'Neos.ContentRepository.Testing:Text');
        }
    }

    /**
     * @test
     */
    public function findByNodeWithInstanceofFilterExcludeNodesWithADisabledCorrespondingSuperType()
    {
        $q = new FlowQuery([$this->node]);
        $foundNodes = $q->find('[instanceof Neos.ContentRepository.Testing:ContentMixin]')->get();
        $foundNodeTypeNames = array_map(function (NodeInterface $node) {
            return $node->getNodeType()->getName();
        }, $foundNodes);
        self::assertNotContains('Neos.ContentRepository.Testing:ThreeColumn', $foundNodeTypeNames);
    }

    /**
     * @test
     */
    public function findByNodeWithMultipleInstanceofFilterReturnsMatchingNodesRecursively()
    {
        $q = new FlowQuery([$this->node]);
        $foundNodes = $q->find('[instanceof Neos.ContentRepository.Testing:Text],[instanceof Neos.ContentRepository.Testing:Page]')->get();
        $this->assertGreaterThan(0, count($foundNodes));
        $foundNodeTypes = [];
        foreach ($foundNodes as $foundNode) {
            $nodeType = $foundNode->getNodeType()->getName();
            if (!in_array($nodeType, $foundNodeTypes)) {
                $foundNodeTypes[] = $nodeType;
            }
        }
        sort($foundNodeTypes);
        self::assertSame($foundNodeTypes, ['Neos.ContentRepository.Testing:Page', 'Neos.ContentRepository.Testing:Text']);
    }

    /**
     * @test
     */
    public function findByNodeWithAbsolutePathReturnsCorrectNode()
    {
        $q = new FlowQuery([$this->node]);
        $foundNodes = $q->find('/sites/example/home/main/dummy42a')->get();
        self::assertEquals(1, count($foundNodes));
        $foundNode = $foundNodes[0];
        self::assertSame('b1e0e78d-04f3-8fc3-e3d1-e2399f831312', $foundNode->getIdentifier());
    }

    /**
     * @test
     */
    public function findByNodeWithPathReturnsEmptyArrayIfNotFound()
    {
        $q = new FlowQuery([$this->node]);
        $foundNodes = $q->find('/sites/example/home/main/limbo')->get();
        self::assertEmpty($foundNodes);
    }

    /**
     * @test
     */
    public function findOperationEvaluatesWithEmptyContext()
    {
        $q = new FlowQuery([]);
        $foundNodes = $q->find('/sites/example/home/main/limbo')->get();
        self::assertEmpty($foundNodes);
    }

    /**
     * @test
     */
    public function findOperationThrowsExceptionOnAtLeastOneInvalidContext()
    {
        $this->expectException(FlowQueryException::class);
        $q = new FlowQuery([$this->node, '1']);
        $q->find('/sites/example/home/main/limbo')->get();
    }

    /**
     * @test
     */
    public function findByNodeWithNodeNameReturnsCorrectNode()
    {
        $q = new FlowQuery([$this->node]);
        $foundNodes = $q->find('main')->get();
        self::assertEquals(1, count($foundNodes));
        $foundNode = $foundNodes[0];
        self::assertSame('f66b3871-515f-7f54-fb1d-1c108040b2c0', $foundNode->getIdentifier());
    }

    /**
     * @test
     */
    public function findByNodeWithRelativePathReturnsCorrectNode()
    {
        $q = new FlowQuery([$this->node]);
        $foundNodes = $q->find('main/dummy42a')->get();
        self::assertEquals(1, count($foundNodes));
        $foundNode = $foundNodes[0];
        self::assertSame('b1e0e78d-04f3-8fc3-e3d1-e2399f831312', $foundNode->getIdentifier());
    }

    /**
     * @test
     */
    public function findByMultipleNodesReturnsMatchingNodesForAllNodes()
    {
        $this->authenticateRoles(['Neos.ContentRepository:TestingAdministrator']);
        $testContext = $this->contextFactory->create(['workspaceName' => 'test']);
        $testNodeA = $testContext->getNode('/sites/example/home/main/dummy44');
        $testNodeB = $testContext->getNode('/sites/example/home/main/dummy45');
        $q = new FlowQuery([$testNodeA, $testNodeB]);

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

        self::assertTrue($foundChildrenOfA);
        self::assertTrue($foundChildrenOfB);
    }

    /**
     * @test
     */
    public function findByNodeWithInstanceofFilterAppliesAdditionalAttributeFilter()
    {
        $q = new FlowQuery([$this->node]);
        $foundNodes = $q->find('[instanceof Neos.ContentRepository.Testing:Text][text*="Twitter"]')->get();
        self::assertCount(1, $foundNodes);
    }
}
