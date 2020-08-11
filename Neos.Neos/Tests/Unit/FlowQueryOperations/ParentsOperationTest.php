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
     * @test
     * @throws \ReflectionException
     * @throws \Neos\Eel\Exception
     */
    public function parentsWillReturnTheSiteNodeAsRootLevelParent()
    {
        $rootNode = $this->createMock(TraversableNodeInterface::class);
        $sitesNode = $this->createMock(TraversableNodeInterface::class);
        $siteNode = $this->createMock(TraversableNodeInterface::class);
        $firstLevelNode = $this->createMock(TraversableNodeInterface::class);
        $secondLevelNode = $this->createMock(TraversableNodeInterface::class);

        $rootNode->expects($this->any())->method('findNodePath')->will($this->returnValue(NodePath::fromString('/')));
        $rootNode->expects($this->any())->method('findParentNode')->will($this->throwException(new NodeException('No parent')));
        $sitesNode->expects($this->any())->method('findNodePath')->will($this->returnValue(NodePath::fromString('/sites')));
        $sitesNode->expects($this->any())->method('findParentNode')->will($this->returnValue($rootNode));
        $siteNode->expects($this->any())->method('findNodePath')->will($this->returnValue(NodePath::fromString('/sites/site')));
        $siteNode->expects($this->any())->method('findParentNode')->will($this->returnValue($sitesNode));
        $firstLevelNode->expects($this->any())->method('findParentNode')->will($this->returnValue($siteNode));
        $firstLevelNode->expects($this->any())->method('findNodePath')->will($this->returnValue(NodePath::fromString('/sites/site/first')));
        $secondLevelNode->expects($this->any())->method('findParentNode')->will($this->returnValue($firstLevelNode));
        $secondLevelNode->expects($this->any())->method('findNodePath')->will($this->returnValue(NodePath::fromString('/sites/site/first/second')));

        $context = [$secondLevelNode];
        $q = new FlowQuery($context);

        $operation = new ParentsOperation();
        $operation->evaluate($q, []);

        $ancestors = $q->getContext();
        $this->assertEquals([$siteNode, $firstLevelNode], $ancestors);
    }
}
