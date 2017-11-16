<?php

namespace Neos\Neos\Http;

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
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http;

/**
 * The interface for content subgraph URI processors
 */
interface ContentSubgraphUriProcessorInterface
{
    /**
     * @param Http\Uri $currentBaseUri
     * @param NodeInterface $node
     * @return Http\Uri The adjusted URI
     */
    public function resolveDimensionBaseUri(Http\Uri $currentBaseUri, NodeInterface $node): Http\Uri;
}
