<?php
namespace Neos\Neos\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;

/**
 * The content element wrapping service adds the necessary markup around
 * a content element such that it can be edited using the Content Module
 * of the Neos Backend.
 */
interface ContentElementWrappingServiceInterface
{


    /**
     * Wrap the $content identified by $node with the needed markup for the backend.
     *
     * @param NodeInterface $node
     * @param ContentSubgraphInterface $subgraph
     * @param string $content
     * @param string $fusionPath
     * @return string
     */
    public function wrapContentObject(NodeInterface $node, ContentSubgraphInterface $subgraph, $content, $fusionPath);
}
