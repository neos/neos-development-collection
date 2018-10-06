<?php
namespace Neos\EventSourcedNeosAdjustments\Domain\Context\Site\Event;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Event\EventInterface;
use Neos\ContentRepository\Domain\ValueObject\NodeName;

final class SiteWasActivated implements EventInterface
{
    /**
     * @var NodeName
     */
    private $nodeName;

    /**
     * CreateSite constructor.
     * @param NodeName $nodeName
     */
    public function __construct(NodeName $nodeName) {
        $this->nodeName = $nodeName;
    }

    /**
     * @return NodeName
     */
    public function getNodeName(): NodeName
    {
        return $this->nodeName;
    }
}
