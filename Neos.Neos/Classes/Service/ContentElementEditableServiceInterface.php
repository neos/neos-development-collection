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
use Neos\Flow\Annotations as Flow;

/**
 * The content element editable service adds the necessary markup around
 * a content element such that it can be edited using the inline editing
 * of the Neos Backend.
 */
interface ContentElementEditableServiceInterface
{

    /**
     * Wrap the $content identified by $node with the needed markup for the backend.
     *
     * @param NodeInterface $node
     * @param ContentSubgraphInterface $subgraph
     * @param string $property
     * @param string $content
     * @return string
     */
    public function wrapContentProperty(NodeInterface $node, ContentSubgraphInterface $subgraph, $property, $content);
}
