<?php

namespace Neos\Neos\Routing;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\ContentContext;

/**
 * @deprecated Pass detected content context to the route part handler instead
 * @Flow\Scope("singleton")
 */
final class ContentContextContainer
{
    /**
     * @var ContentContext
     */
    protected $contentContext;

    /**
     * @var bool
     */
    protected $uriPathSegmentUsed;

    public function setContentContext(ContentContext $context)
    {
        $this->contentContext = $context;
    }

    public function getContentContext()
    {
        return $this->contentContext;
    }

    public function setUriPathSegmentUsed(bool $uriPathSegmentUsed)
    {
        $this->uriPathSegmentUsed = $uriPathSegmentUsed;
    }

    public function isUriPathSegmentUsed()
    {
        return $this->uriPathSegmentUsed;
    }
}
