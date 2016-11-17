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

/**
 * Testcase for the FlowQuery ParentsOperation
 */
class ParentsOperationTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function parentsWillReturnTheSiteNodeAsRootLevelParent()
    {
        $siteNode = $this->createMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
        $firstLevelNode = $this->createMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
        $secondLevelNode = $this->createMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

        $siteNode->expects($this->any())->method('getPath')->will($this->returnValue('/site'));
        $mockContext = $this->getMockBuilder('TYPO3\Neos\Domain\Service\ContentContext')->disableOriginalConstructor()->getMock();
        $mockContext->expects($this->any())->method('getCurrentSiteNode')->will($this->returnValue($siteNode));
        $firstLevelNode->expects($this->any())->method('getParent')->will($this->returnValue($siteNode));
        $firstLevelNode->expects($this->any())->method('getPath')->will($this->returnValue('/site/first'));
        $secondLevelNode->expects($this->any())->method('getContext')->will($this->returnValue($mockContext));
        $secondLevelNode->expects($this->any())->method('getParent')->will($this->returnValue($firstLevelNode));
        $secondLevelNode->expects($this->any())->method('getPath')->will($this->returnValue('/site/first/second'));

        $context = array($secondLevelNode);
        $q = new \TYPO3\Eel\FlowQuery\FlowQuery($context);

        $operation = new \TYPO3\Neos\Eel\FlowQueryOperations\ParentsOperation();
        $operation->evaluate($q, array());

        $output = $q->getContext();
        $this->assertEquals(array($siteNode, $firstLevelNode), $output);
    }

    /**
     * @test
     */
    public function canEvaluateChecksForContentContext()
    {
        $operation = new \TYPO3\Neos\Eel\FlowQueryOperations\ParentsOperation();

        $mockNode = $this->createMock(\TYPO3\TYPO3CR\Domain\Model\NodeInterface::class);
        $mockContext = $this->getMockBuilder(\TYPO3\Neos\Domain\Service\ContentContext::class)->disableOriginalConstructor()->getMock();
        $mockNode->expects($this->any())->method('getContext')->will($this->returnValue($mockContext));
        $context = array($mockNode);

        $this->assertTrue($operation->canEvaluate($context), 'Must accept ContentContext');

        $mockNode = $this->createMock(\TYPO3\TYPO3CR\Domain\Model\NodeInterface::class);
        $mockContext = $this->getMockBuilder(\TYPO3\TYPO3CR\Domain\Service\Context::class)->disableOriginalConstructor()->getMock();
        $mockNode->expects($this->any())->method('getContext')->will($this->returnValue($mockContext));
        $context = array($mockNode);
        $this->assertFalse($operation->canEvaluate($context), 'Must not accept Context');
    }
}
