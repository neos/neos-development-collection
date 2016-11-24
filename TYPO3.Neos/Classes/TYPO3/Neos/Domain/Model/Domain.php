<?php
namespace TYPO3\Neos\Domain\Model;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\CacheAwareInterface;
use TYPO3\Neos\Domain\Model\Site;

/**
 * Domain Model of a Domain.
 *
 * It is used to connect a site root node to a specific hostname.
 *
 * @Flow\Entity
 * @Flow\Scope("prototype")
 */
class Domain implements CacheAwareInterface
{
    /**
     * @var string
     * @Flow\Identity
     * @Flow\Validate(type="NotEmpty")
     * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=255 })
     * @Flow\Validate(type="\TYPO3\Neos\Validation\Validator\HostnameValidator", options={"ignoredHostnames"="localhost"})
     */
    protected $hostname;

    /**
     * @var string
     * @Flow\Validate(type="RegularExpression", options={ "regularExpression"="/^(http|https)$/" })
     * @ORM\Column(nullable=true)
     */
    protected $scheme;

    /**
     * @var integer
     * @Flow\Validate(type="NumberRange", options={ "minimum"=0, "maximum"=49151 })
     * @ORM\Column(nullable=true)
     */
    protected $port;

    /**
     * @var Site
     * @ORM\ManyToOne(inversedBy="domains")
     * @Flow\Validate(type="NotEmpty")
     */
    protected $site;

    /**
     * If domain is active
     *
     * @var boolean
     */
    protected $active = true;

    /**
     * Sets the hostname
     *
     * @param string $hostname
     * @return void
     * @api
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
    }

    /**
     * Sets the hostname
     *
     * @param string $hostPattern
     * @return void
     * @api
     * @deprecated after 3.0, use setHostname() instead
     */
    public function setHostPattern($hostPattern)
    {
        $this->hostname = $hostPattern;
    }

    /**
     * Returns the hostname
     *
     * @return string
     * @api
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * Returns the hostname
     *
     * @return string The name
     * @api
     * @deprecated after 3.0, use getHostname() instead
     */
    public function getHostPattern()
    {
        return $this->hostname;
    }

    /**
     * Sets the scheme for the domain
     *
     * @param string $scheme Scheme for the domain
     * @return void
     * @api
     */
    public function setScheme($scheme = null)
    {
        $this->scheme = $scheme;
    }

    /**
     * Returns the scheme for this domain
     *
     * @return string The scheme
     * @api
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Sets the port for the domain
     *
     * @param integer $port Port for the domain
     * @return void
     * @api
     */
    public function setPort($port = null)
    {
        $this->port = $port;
    }

    /**
     * Returns the port for this domain
     *
     * @return integer The port
     * @api
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Sets the site this domain is pointing to
     *
     * @param Site $site The site
     * @return void
     * @api
     */
    public function setSite(Site $site)
    {
        $this->site = $site;
    }

    /**
     * Returns the site this domain is pointing to
     *
     * @return Site
     * @api
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * Sets if the domain is active
     *
     * @param boolean $active If the domain is active
     * @return void
     * @api
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Returns if the domain is active
     *
     * @return boolean If active or not
     * @api
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Internal event handler to forward domain changes to the "siteChanged" signal
     *
     * @ORM\PostPersist
     * @ORM\PostUpdate
     * @ORM\PostRemove
     * @return void
     */
    public function onPostFlush()
    {
        $this->site->emitSiteChanged();
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheEntryIdentifier()
    {
        return $this->hostname;
    }

    /**
     * Returns a URI string representation of this domain
     *
     * @return string This domain as a URI string
     */
    public function __toString()
    {
        $domain = '';
        $domain .= $this->scheme ? $this->scheme . '://' : '';
        $domain .= $this->hostname;
        if ($this->port !== null) {
            switch ($this->scheme) {
                case 'http':
                    $domain .= ($this->port !== 80 ? ':' . $this->port : '');
                    break;
                case 'https':
                    $domain .= ($this->port !== 443 ? ':' . $this->port : '');
                    break;
                default:
                    $domain .= (isset($this->port) ? ':' . $this->port : '');
            }
        }
        return $domain;
    }
}
