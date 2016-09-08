<?php
namespace TYPO3\Neos\Tests\Unit\FlowQueryOperations;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\Eel\FlowQueryOperations\ParentsOperation;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\Context;

/**
 * Testcase for the FlowQuery ParentsOperation
 */
class ParentsOperationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function parentsWillReturnTheSiteNodeAsRootLevelParent()
    {
        $siteNode = $this->createMock(NodeInterface::class);
        $firstLevelNode = $this->createMock(NodeInterface::class);
        $secondLevelNode = $this->createMock(NodeInterface::class);

        $siteNode->expects($this->any())->method('getPath')->will($this->returnValue('/site'));
        $mockContext = $this->getMockBuilder('TYPO3\Neos\Domain\Service\ContentContext')->disableOriginalConstructor()->getMock();
        $mockContext->expects($this->any())->method('getCurrentSiteNode')->will($this->returnValue($siteNode));
        $firstLevelNode->expects($this->any())->method('getParent')->will($this->returnValue($siteNode));
        $firstLevelNode->expects($this->any())->method('getPath')->will($this->returnValue('/site/first'));
        $secondLevelNode->expects($this->any())->method('getContext')->will($this->returnValue($mockContext));
        $secondLevelNode->expects($this->any())->method('getParent')->will($this->returnValue($firstLevelNode));
        $secondLevelNode->expects($this->any())->method('getPath')->will($this->returnValue('/site/first/second'));

        $context = array($secondLevelNode);
        $q = new FlowQuery($context);

        $operation = new ParentsOperation();
        $operation->evaluate($q, array());

        $output = $q->getContext();
        $this->assertEquals(array($siteNode, $firstLevelNode), $output);
    }

    /**
     * @test
     */
    public function canEvaluateChecksForContentContext()
    {
        $operation = new ParentsOperation();

        $mockNode = $this->createMock(NodeInterface::class);
        $mockContext = $this->getMockBuilder(ContentContext::class)->disableOriginalConstructor()->getMock();
        $mockNode->expects($this->any())->method('getContext')->will($this->returnValue($mockContext));
        $context = array($mockNode);

        $this->assertTrue($operation->canEvaluate($context), 'Must accept ContentContext');

        $mockNode = $this->createMock(NodeInterface::class);
        $mockContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $mockNode->expects($this->any())->method('getContext')->will($this->returnValue($mockContext));
        $context = array($mockNode);
        $this->assertFalse($operation->canEvaluate($context), 'Must not accept Context');
    }
}
