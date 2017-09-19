<?php
namespace Neos\Neos\Domain\Context\Domain\Command;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Context\Domain\DomainPort;
use Neos\Neos\Domain\Context\Domain\HostName;
use Neos\Neos\Domain\Context\Domain\HttpScheme;


class AddDomain
{
    /**
     * @var NodeName
     */
    private $siteNodeName;

    /**
     * @var HostName
     */
    private $domainHostname;

    /**
     * @var HttpScheme
     */
    private $scheme;

    /**
     * @var DomainPort
     */
    private $port;

    /**
     * CreateDomain constructor.
     * @param NodeName $siteNodeName
     * @param HostName $domainHostname
     * @param HttpScheme $scheme
     * @param DomainPort $port
     */
    public function __construct(NodeName $siteNodeName, HostName $domainHostname, HttpScheme $scheme, DomainPort $port)
    {
        $this->siteNodeName = $siteNodeName;
        $this->domainHostname = $domainHostname;
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
     * @param NodeName $siteNodeName
     */
    public function setSiteNodeName(NodeName $siteNodeName)
    {
        $this->siteNodeName = $siteNodeName;
    }

    /**
     * @return HostName
     */
    public function getDomainHostname(): HostName
    {
        return $this->domainHostname;
    }

    /**
     * @param HostName $domainHostname
     */
    public function setDomainHostname(HostName $domainHostname)
    {
        $this->domainHostname = $domainHostname;
    }

    /**
     * @return HttpScheme
     */
    public function getScheme(): HttpScheme
    {
        return $this->scheme;
    }

    /**
     * @param HttpScheme $scheme
     */
    public function setScheme(HttpScheme $scheme)
    {
        $this->scheme = $scheme;
    }

    /**
     * @return DomainPort
     */
    public function getPort(): DomainPort
    {
        return $this->port;
    }

    /**
     * @param DomainPort $port
     */
    public function setPort(DomainPort $port)
    {
        $this->port = $port;
    }


}
