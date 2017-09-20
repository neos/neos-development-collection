<?php

namespace Neos\Neos\Domain\Context\Site;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Event\EventPublisher;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Context\Site\Command\CreateSite;
use Neos\Neos\Domain\Context\Site\Event\SiteWasCreated;

/**
 * SiteCommandHandler
 */
final class SiteCommandHandler
{
    /**
     * @Flow\Inject
     * @var EventPublisher
     */
    protected $eventPublisher;

    /**
     * @param CreateSite $command
     */
    public function handleCreateSite(CreateSite $command)
    {
        $this->eventPublisher->publish(
            'Neos.Neos:Site:' . $command->getSiteName(),
            new SiteWasCreated(
                $command->getSiteName(),
                $command->getPackageKey(),
                $command->getNodeType(),
                $command->getNodeName(),
                $command->getSiteActive()
            )
        );
    }
}
