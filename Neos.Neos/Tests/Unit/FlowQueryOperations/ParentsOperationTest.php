<?php
namespace Neos\Neos\Tests\Unit\FlowQueryOperations;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use PHPUnit\Framework\Attributes\Test;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Eel\FlowQueryOperations\ParentsOperation;

/**
 * Testcase for the FlowQuery ParentsOperation
 */
class ParentsOperationTest extends UnitTestCase
{
    /**
     * @throws \ReflectionException
     * @throws \Neos\Eel\Exception
     */
    #[Test]
    public function parentsWillReturnTheSiteNodeAsRootLevelParent()
    {
        $rootNode = $this->createMock(TraversableNodeInterface::class);
        $sitesNode = $this->createMock(TraversableNodeInterface::class);
        $siteNode = $this->createMock(TraversableNodeInterface::class);
        $firstLevelNode = $this->createMock(TraversableNodeInterface::class);
        $secondLevelNode = $this->createMock(TraversableNodeInterface::class);

        $rootNode->expects(self::any())->method('findNodePath')->willReturn(NodePath::fromString('/'));
        $rootNode->expects(self::any())->method('findParentNode')->willThrowException(new NodeException('No parent'));
        $sitesNode->expects(self::any())->method('findNodePath')->willReturn(NodePath::fromString('/sites'));
        $sitesNode->expects(self::any())->method('findParentNode')->willReturn($rootNode);
        $siteNode->expects(self::any())->method('findNodePath')->willReturn(NodePath::fromString('/sites/site'));
        $siteNode->expects(self::any())->method('findParentNode')->willReturn($sitesNode);
        $firstLevelNode->expects(self::any())->method('findParentNode')->willReturn($siteNode);
        $firstLevelNode->expects(self::any())->method('findNodePath')->willReturn(NodePath::fromString('/sites/site/first'));
        $secondLevelNode->expects(self::any())->method('findParentNode')->willReturn($firstLevelNode);
        $secondLevelNode->expects(self::any())->method('findNodePath')->willReturn(NodePath::fromString('/sites/site/first/second'));

        $context = [$secondLevelNode];
        $q = new FlowQuery($context);

        $operation = new ParentsOperation();
        $operation->evaluate($q, []);

        $ancestors = $q->getContext();
        self::assertEquals([$siteNode, $firstLevelNode], $ancestors);
    }
}
