<?php
namespace TYPO3\TYPO3CR\Tests\Unit\Domain\Utility;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Utility\NodePaths;

/**
 * Testcase for the NodeService
 *
 */
class NodePathsTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function generateRandomNodeNameReturnsValidNodeName()
    {
        for ($i=0; $i<25; $i++) {
            $generatedName = NodePaths::generateRandomNodeName();
            self::assertSame(1, preg_match(NodeInterface::MATCH_PATTERN_NAME, $generatedName));
        }
    }
}
