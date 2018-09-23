<?php
namespace Neos\EventSourcedNeosAdjustments\Domain\Projection\Site;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\EventSourcing\Annotations as CQRS;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedNeosAdjustments\Domain\Context\Domain\Event\DomainWasAdded;
use Neos\EventSourcedNeosAdjustments\Domain\Projection\Domain\DomainFinder;

/**
 * Domain Read Model
 *
 * @Flow\Entity
 * @CQRS\ReadModel
 * @ORM\Table(name="neos_neos_projection_site_v1")
 */
class Site
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $nodeName;

    /**
     * @var bool
     */
    public $active;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    public $siteResourcesPackageKey;

    /**
     * @Flow\Inject
     * @var DomainFinder
     */
    protected $domainFinder;

    /**
     * @return boolean TRUE if the site has at least one active domain assigned
     * @api
     */
    public function hasActiveDomains()
    {
        return $this->domainFinder->findActiveBySite($this)->count() > 0;
    }

    public function isOnline()
    {
        // TODO: figure out what to do here
        return true;
    }
}
