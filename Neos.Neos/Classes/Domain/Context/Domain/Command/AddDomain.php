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

use Neos\Neos\Domain\ValueObject\NodeName;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\ValueObject\DomainPort;
use Neos\Neos\Domain\ValueObject\HostName;
use Neos\Neos\Domain\ValueObject\UriScheme;

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
     * @param HostName $domainHostname
     * @param UriScheme $scheme
     * @param DomainPort $port
     */
    public function __construct(NodeName $siteNodeName, HostName $domainHostname, UriScheme $scheme = null, DomainPort $port = null)
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
     * @return HostName
     */
    public function getDomainHostname(): HostName
    {
        return $this->domainHostname;
    }

    /**
     * @return UriScheme (nullable)
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @return DomainPort (nullable)
     */
    public function getPort()
    {
        return $this->port;
    }
}
