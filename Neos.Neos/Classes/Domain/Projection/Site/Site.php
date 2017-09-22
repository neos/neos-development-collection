<?php
namespace Neos\Neos\Domain\Projection\Site;

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
use Neos\Neos\Domain\Context\Domain\Event\DomainWasAdded;

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
}
