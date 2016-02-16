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
        $siteNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
        $firstLevelNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');
        $secondLevelNode = $this->getMock('TYPO3\TYPO3CR\Domain\Model\NodeInterface');

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
}
