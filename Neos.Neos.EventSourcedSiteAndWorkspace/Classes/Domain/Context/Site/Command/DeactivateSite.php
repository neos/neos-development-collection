<?php

namespace Neos\Neos\EventSourcedSiteAndWorkspace\Domain\Context\Site\Command;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeName;

final class DeactivateSite
{
    /**
     * @var NodeName
     */
    private $nodeName;

    /**
     * CreateSite constructor.
     * @param NodeName $siteName
     */
    public function __construct(NodeName $siteName)
    {
        $this->nodeName = $siteName;
    }

    /**
     * @return NodeName
     */
    public function getNodeName(): NodeName
    {
        return $this->nodeName;
    }

}
