<?php
namespace Neos\EventSourcedNeosAdjustments\Domain\Projection\Domain;

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
use Neos\EventSourcedNeosAdjustments\Domain\Projection\Site\Site;

/**
 * Domain Read Model
 *
 * @Flow\Entity
 * @CQRS\ReadModel
 * @ORM\Table(name="neos_neos_projection_domain_v1")
 */
class Domain
{
    /**
     * @var Site
     * @ORM\ManyToOne
     */
    public $site;

    /**
     * @var string
     */
    public $hostName;

    /**
     * @var boolean
     */
    public $active;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    public $uriScheme;

    /**
     * @var integer
     * @ORM\Column(nullable=true)
     */
    public $domainPort;
}
