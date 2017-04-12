<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Service;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the NodeService
 *
 */
class NodeNameGeneratorTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function generatePossibleNodeNameReturnsValidNodeName()
    {
        for ($i=0; $i<25; $i++) {
            $nodeNameGenerator = $this->getAccessibleMock(\TYPO3\Neos\Service\NodeNameGenerator::class, array('dummy'));
            $generatedName = $nodeNameGenerator->_call('generatePossibleNodeName');
            self::assertSame(1, preg_match(\TYPO3\TYPO3CR\Domain\Model\NodeInterface::MATCH_PATTERN_NAME, $generatedName));
        }
    }
}
