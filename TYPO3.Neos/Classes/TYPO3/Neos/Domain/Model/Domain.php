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

/**
 * Domain Model of a Domain
 *
 * @Flow\Entity
 * @Flow\Scope("prototype")
 */
class Domain implements \TYPO3\Flow\Cache\CacheAwareInterface
{
    /**
     * @var string
     * @Flow\Identity
     * @Flow\Validate(type="NotEmpty")
     * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=255 })
     * @Flow\Validate(type="\TYPO3\Neos\Validation\Validator\HostnameValidator", options={"ignoredHostnames"="localhost"})
     */
    protected $hostPattern = '*';

    /**
     * @var \TYPO3\Neos\Domain\Model\Site
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
     * Sets the pattern for the host of the domain
     *
     * @param string $hostPattern Pattern for the host
     * @return void
     * @api
     */
    public function setHostPattern($hostPattern)
    {
        $this->hostPattern = $hostPattern;
    }

    /**
     * Returns the host pattern for this domain
     *
     * @return string The host pattern
     * @api
     */
    public function getHostPattern()
    {
        return $this->hostPattern;
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
     * @return \TYPO3\Neos\Domain\Model\Site
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
        return $this->hostPattern;
    }
}
