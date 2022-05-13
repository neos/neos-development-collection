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
use Neos\ContentRepository\SharedModel\Node\NodePath;
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
        $this->markTestSkipped('TODO - Update with Neos 9.0');

        $rootNode = $this->createMock(TraversableNodeInterface::class);
        $sitesNode = $this->createMock(TraversableNodeInterface::class);
        $siteNode = $this->createMock(TraversableNodeInterface::class);
        $firstLevelNode = $this->createMock(TraversableNodeInterface::class);
        $secondLevelNode = $this->createMock(TraversableNodeInterface::class);

        $rootNode->expects(self::any())->method('findNodePath')->will(self::returnValue(NodePath::fromString('/')));
        $rootNode->expects(self::any())->method('findParentNode')->will(self::throwException(new NodeException('No parent')));
        $sitesNode->expects(self::any())->method('findNodePath')->will(self::returnValue(NodePath::fromString('/sites')));
        $sitesNode->expects(self::any())->method('findParentNode')->will(self::returnValue($rootNode));
        $siteNode->expects(self::any())->method('findNodePath')->will(self::returnValue(NodePath::fromString('/sites/site')));
        $siteNode->expects(self::any())->method('findParentNode')->will(self::returnValue($sitesNode));
        $firstLevelNode->expects(self::any())->method('findParentNode')->will(self::returnValue($siteNode));
        $firstLevelNode->expects(self::any())->method('findNodePath')->will(self::returnValue(NodePath::fromString('/sites/site/first')));
        $secondLevelNode->expects(self::any())->method('findParentNode')->will(self::returnValue($firstLevelNode));
        $secondLevelNode->expects(self::any())->method('findNodePath')->will(self::returnValue(NodePath::fromString('/sites/site/first/second')));

        $context = [$secondLevelNode];
        $q = new FlowQuery($context);

        $operation = new ParentsOperation();
        $operation->evaluate($q, []);

        $ancestors = $q->getContext();
        self::assertEquals([$siteNode, $firstLevelNode], $ancestors);
    }
}
