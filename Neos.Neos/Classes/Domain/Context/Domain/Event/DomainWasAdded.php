<?php
namespace Neos\Neos\Domain\Context\Domain\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


use Neos\Neos\Domain\ValueObject\NodeName;
use Neos\EventSourcing\Event\EventInterface;
use Neos\Neos\Domain\ValueObject\DomainPort;
use Neos\Neos\Domain\ValueObject\HostName;
use Neos\Neos\Domain\ValueObject\UriScheme;

class DomainWasAdded implements EventInterface
{
    /**
     * @var NodeName
     */
    private $siteNodeName;

    /**
     * @var HostName
     */
    private $domainHostName;

    /**
     * @var UriScheme
     */
    private $scheme;

    /**
     * @var DomainPort
     */
    private $port;

    /**
     * CreateDomain constructor.
     * @param NodeName $siteNodeName
     * @param HostName $domainHostName
     * @param UriScheme $scheme
     * @param DomainPort $port
     */
    public function __construct(NodeName $siteNodeName, HostName $domainHostName, UriScheme $scheme = null, DomainPort $port = null)
    {
        $this->siteNodeName = $siteNodeName;
        $this->domainHostName = $domainHostName;
        $this->scheme = $scheme;
        $this->port = $port;
    }

    /**
     * @return NodeName
     */
    public function getSiteNodeName(): NodeName
    {
        return $this->siteNodeName;
    }

    /**
     * @return HostName
     */
    public function getDomainHostName(): HostName
    {
        return $this->domainHostName;
    }

    /**
     * @return UriScheme
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @return DomainPort
     */
    public function getPort()
    {
        return $this->port;
    }
}
