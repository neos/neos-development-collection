<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\AssetCollection;

/**
 * Domain model of a site
 *
 * @Flow\Entity
 * @api
 */
class Site
{
    /**
     * Site states
     */
    public const STATE_ONLINE = 1;
    public const STATE_OFFLINE = 2;

    /**
     * @Flow\InjectConfiguration(path="sites")
     * @var array
     * @phpstan-var array<string,array<string,mixed>>
     */
    protected $sitesConfiguration = [];

    /**
     * Name of the site
     *
     * @var string
     * @Flow\Validate(type="Label")
     * @Flow\Validate(type="NotEmpty")
     * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=250 })
     */
    protected $name = 'Untitled Site';

    /**
     * Node name of this site in the content repository.
     *
     * The first level of nodes of a site can be reached via a path like
     * "/Sites/MySite/" where "MySite" is the nodeName.
     *
     * @var string
     * @Flow\Identity
     * @Flow\Validate(type="NotEmpty")
     * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=250 })
     * @Flow\Validate(type="\Neos\Neos\Validation\Validator\NodeNameValidator")
     */
    protected $nodeName;

    /**
     * @var Collection<Domain>
     * @phpstan-var Collection<int,Domain>
     * @ORM\OneToMany(mappedBy="site")
     * @Flow\Lazy
     */
    protected $domains;

    /**
     * @var Domain
     * @phpstan-var ?Domain
     * @ORM\ManyToOne
     * @ORM\Column(nullable=true)
     */
    protected $primaryDomain;

    /**
     * The site's state
     *
     * @var integer
     * @Flow\Validate(type="NumberRange", options={ "minimum"=1, "maximum"=2 })
     */
    protected $state = self::STATE_OFFLINE;

    /**
     * @var string
     * @Flow\Validate(type="NotEmpty")
     */
    protected $siteResourcesPackageKey;

    /**
     * @var AssetCollection
     * @phpstan-var ?AssetCollection
     * @ORM\ManyToOne
     */
    protected $assetCollection;

    /**
     * Constructs this Site object
     *
     * @param string $nodeName Node name of this site in the content repository
     */
    public function __construct($nodeName)
    {
        $this->nodeName = $nodeName;
        $this->domains = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getNodeName()->value;
    }

    /**
     * Sets the name for this site
     *
     * @param string $name The site name
     * @return void
     * @api
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of this site
     *
     * @return string The name
     * @api
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the node name of this site
     *
     * If you need to fetch the root node for this site, use the content
     * context, do not use the NodeDataRepository!
     *
     * @return SiteNodeName The node name
     * @api
     */
    public function getNodeName(): SiteNodeName
    {
        return SiteNodeName::fromString($this->nodeName);
    }

    /**
     * Sets the node name for this site
     *
     * @param string $nodeName The site node name
     * @return void
     * @api
     */
    public function setNodeName(string|SiteNodeName $nodeName)
    {
        if ($nodeName instanceof SiteNodeName) {
            $nodeName = $nodeName->value;
        }
        $this->nodeName = $nodeName;
    }

    /**
     * Sets the state for this site
     *
     * @param integer $state The site's state, must be one of the STATUS_* constants
     * @return void
     * @api
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * Returns the state of this site
     *
     * @return integer The state - one of the STATUS_* constant's values
     * @api
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Sets the key of a package containing the static resources for this site.
     *
     * @param string $packageKey The package key
     * @return void
     * @api
     */
    public function setSiteResourcesPackageKey($packageKey)
    {
        $this->siteResourcesPackageKey = $packageKey;
    }

    /**
     * Returns the key of a package containing the static resources for this site.
     *
     * @return string The package key
     * @api
     */
    public function getSiteResourcesPackageKey()
    {
        return $this->siteResourcesPackageKey;
    }

    /**
     * @return boolean
     * @api
     */
    public function isOnline()
    {
        return $this->state === self::STATE_ONLINE;
    }

    /**
     * @return boolean
     * @api
     */
    public function isOffline()
    {
        return $this->state === self::STATE_OFFLINE;
    }

    /**
     * @param Collection<int,Domain> $domains
     * @return void
     * @api
     */
    public function setDomains($domains)
    {
        $this->domains = $domains;
        if (!$this->primaryDomain || !$this->domains->contains($this->primaryDomain)) {
            $this->primaryDomain = $this->getFirstActiveDomain();
        }
    }

    /**
     * @return Collection<int,Domain>
     * @api
     */
    public function getDomains()
    {
        return $this->domains;
    }

    /**
     * @return boolean true if the site has at least one active domain assigned
     * @api
     */
    public function hasActiveDomains()
    {
        return $this->domains->exists(function ($index, Domain $domain) {
            return $domain->getActive();
        });
    }

    /**
     * @return Collection<int,Domain>
     * @api
     */
    public function getActiveDomains()
    {
        $activeDomains = $this->domains->filter(function (Domain $domain) {
            return $domain->getActive();
        });
        return $activeDomains;
    }

    /**
     * @return ?Domain
     * @api
     */
    public function getFirstActiveDomain()
    {
        $activeDomains = $this->getActiveDomains();
        return count($activeDomains) > 0 ? ($activeDomains->first() ?: null) : null;
    }

    /**
     * Sets (and adds if necessary) the primary domain of this site.
     *
     * @param Domain|null $domain The domain
     * @return void
     * @api
     */
    public function setPrimaryDomain(Domain $domain = null)
    {
        if ($domain === null) {
            $this->primaryDomain = null;
            return;
        }

        if (!$domain->getActive()) {
            return;
        }

        $this->primaryDomain = $domain;
        if (!$this->domains->contains($domain)) {
            $this->domains->add($domain);
        }
    }

    /**
     * Returns the primary domain, if one has been defined.
     *
     * @return ?Domain The primary domain or NULL
     * @api
     */
    public function getPrimaryDomain(): ?Domain
    {
        return $this->primaryDomain instanceof Domain && $this->primaryDomain->getActive()
            ? $this->primaryDomain
            : $this->getFirstActiveDomain();
    }

    /**
     * @return ?AssetCollection
     */
    public function getAssetCollection()
    {
        return $this->assetCollection;
    }

    /**
     * @param AssetCollection $assetCollection
     * @return void
     */
    public function setAssetCollection(AssetCollection $assetCollection = null)
    {
        $this->assetCollection = $assetCollection;
    }

    /**
     * Internal event handler to forward site changes to the "siteChanged" signal
     *
     * @ORM\PostPersist
     * @ORM\PostUpdate
     * @ORM\PostRemove
     * @return void
     */
    public function onPostFlush()
    {
        $this->emitSiteChanged();
    }

    /**
     * Internal signal
     *
     * @Flow\Signal
     * @return void
     */
    public function emitSiteChanged()
    {
    }

    /**
     * @return array<string,mixed>
     */
    public function getConfiguration(): array
    {
        // without recursive merge
        return $this->sitesConfiguration[$this->nodeName] ?? $this->sitesConfiguration['*'];
    }
}
