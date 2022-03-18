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

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Model\Workspace;
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
        $contentCacheFlusher = $this->getMockBuilder(ContentCacheFlusher::class)->setMethods(['resolveWorkspaceChain', 'registerChangeOnNodeIdentifier', 'registerChangeOnNodeType'])->disableOriginalConstructor()->getMock();
        $contentCacheFlusher->expects(self::never())->method('resolveWorkspaceChain');

        // Assume 2 calls as we still register all legacy tags as well
        $contentCacheFlusher->expects($this->once())->method('registerChangeOnNodeIdentifier');
        $contentCacheFlusher->expects($this->once())->method('registerChangeOnNodeType');

        $this->inject($contentCacheFlusher, 'workspacesToFlush', ['live' => ['some-hash']]);

        $workspace = new Workspace('live');

        $nodeType = new NodeType('Some.Node:Type', [], []);

        $nodeMock = $this->getMockBuilder(NodeInterface::class)->disableOriginalConstructor()->getMock();
        $nodeMock->expects(self::any())->method('getWorkspace')->willReturn($workspace);
        $nodeMock->expects(self::any())->method('getNodeType')->willReturn($nodeType);
        $nodeMock->expects(self::any())->method('getIdentifier')->willReturn('some-node-identifier');

        $contentCacheFlusher->registerNodeChange($nodeMock);
    }
}
