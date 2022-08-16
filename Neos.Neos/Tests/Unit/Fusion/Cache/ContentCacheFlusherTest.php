<?php
namespace Neos\Neos\Tests\Unit\Fusion\Cache;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Neos\Fusion\Cache\ContentCacheFlusher;

/**
 * Tests the CachingHelper
 */
class ContentCacheFlusherTest extends UnitTestCase
{
    /**
     * @test
     */
    public function theWorkspaceChainWillOnlyEvaluatedIfNeeded()
    {
        $this->markTestSkipped('TODO - Update with Neos 9.0');
        $contentCacheFlusher = $this->getMockBuilder(ContentCacheFlusher::class)->setMethods(['resolveWorkspaceChain', 'registerChangeOnNodeIdentifier', 'registerChangeOnNodeType'])->disableOriginalConstructor()->getMock();
        $contentCacheFlusher->expects(self::never())->method('resolveWorkspaceChain');

        $contentCacheFlusher->expects($this->once())->method('registerChangeOnNodeIdentifier');
        $contentCacheFlusher->expects($this->once())->method('registerChangeOnNodeType');

        $this->inject($contentCacheFlusher, 'workspacesToFlush', ['live' => ['some-hash']]);

        $workspace = new Workspace('live');

        $nodeType = new NodeType('Some.Node:Type', [], []);

        $nodeMock = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
        $nodeMock->expects(self::any())->method('getWorkspace')->willReturn($workspace);
        $nodeMock->expects(self::any())->method('getNodeType')->willReturn($nodeType);
        $nodeMock->expects(self::any())->method('getIdentifier')->willReturn('some-node-identifier');

        $contentCacheFlusher->registerNodeChange($nodeMock);
    }
}
