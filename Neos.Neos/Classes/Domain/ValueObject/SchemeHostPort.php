<?php
namespace Neos\Neos\Domain\ValueObject;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class SchemeHostPort implements \JsonSerializable
{
    /**
     * @var UriScheme
     */
    private $uriScheme;

    /**
     * @var HostName
     */
    private $hostName;

    /**
     * @var DomainPort
     */
    private $domainPort;

    /**
     * SchemeHostPort constructor.
     * @param UriScheme $uriScheme
     * @param HostName $hostName
     * @param DomainPort $domainPort
     */
    public function __construct(?UriScheme $uriScheme, HostName $hostName, ?DomainPort $domainPort)
    {
        $this->uriScheme = $uriScheme;
        $this->hostName = $hostName;
        $this->domainPort = $domainPort;
    }

    /**
     * @param string|null $uriScheme
     * @param string $hostName
     * @param string|null $domainPort
     * @return SchemeHostPort
     */
    public static function fromStrings(string $uriScheme = null, string $hostName, string $domainPort = null): SchemeHostPort
    {
        return new static($uriScheme ? new UriScheme($uriScheme) : null, new HostName($hostName), $domainPort ? new DomainPort($domainPort) : null);
    }

    /**
     * @return UriScheme
     */
    public function getUriScheme(): ?UriScheme
    {
        return $this->uriScheme;
    }

    /**
     * @return HostName
     */
    public function getHostName(): HostName
    {
        return $this->hostName;
    }

    /**
     * @return DomainPort
     */
    public function getDomainPort(): ?DomainPort
    {
        return $this->domainPort;
    }

    /**
     * @param UriScheme $uriScheme
     */
    public function setUriScheme(?UriScheme $uriScheme)
    {
        // TODO: add validation if needed
        $this->uriScheme = $uriScheme;
    }

    /**
     * @param HostName $hostName
     */
    public function setHostName(HostName $hostName)
    {
        // TODO: add validation if needed
        $this->hostName = $hostName;
    }

    /**
     * @param DomainPort $domainPort
     */
    public function setDomainPort(?DomainPort $domainPort)
    {
        // TODO: add validation if needed
        $this->domainPort = $domainPort;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $uriSchemeOrEmpty = $this->uriScheme ? (string)$this->uriScheme . '://' : "";
        $hostName = (string)$this->hostName;
        $portOrEmpty = $this->domainPort ? ':' . (string)$this->domainPort : "";

        return $uriSchemeOrEmpty . $hostName . $portOrEmpty;
    }

    /**
     * @return array
     */
    public function jsonSerialize() : array
    {
        return [
            'uriScheme' => $this->uriScheme,
            'hostName' => $this->hostName,
            'domainPort' => $this->domainPort
        ];
    }
}
