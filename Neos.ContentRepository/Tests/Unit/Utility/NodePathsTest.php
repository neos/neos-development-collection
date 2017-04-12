<?php
namespace Neos\ContentRepository\Tests\Unit\Domain\Utility;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Utility\NodePaths;

/**
 * Testcase for the NodeService
 *
 */
class NodePathsTest extends \Neos\Flow\Tests\UnitTestCase
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
