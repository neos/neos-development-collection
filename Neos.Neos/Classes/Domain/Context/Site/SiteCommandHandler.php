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
use Neos\Neos\Domain\Context\Site\Command\ActivateSite;
use Neos\Neos\Domain\Context\Site\Command\CreateSite;
use Neos\Neos\Domain\Context\Site\Command\DeactivateSite;
use Neos\Neos\Domain\Context\Site\Event\SiteWasActivated;
use Neos\Neos\Domain\Context\Site\Event\SiteWasCreated;
use Neos\Neos\Domain\Context\Site\Event\SiteWasDeactivated;
use Neos\Neos\Domain\Context\Site\Exception\SiteAlreadyExists;
use Neos\Neos\Domain\Projection\Site\SiteFinder;

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
     * @Flow\Inject
     * @var SiteFinder
     */
    protected $siteFinder;

    /**
     * @param CreateSite $command
     */
    public function handleCreateSite(CreateSite $command)
    {
        $this->validateSiteMustNotExistConstraint($command);

        $this->eventPublisher->publish(
            'Neos.Neos:Site:' . $command->getSiteName(),
            new SiteWasCreated(
                $command->getSiteName(),
                $command->getSiteResourcesPackageKey(),
                $command->getNodeType(),
                $command->getNodeName(),
                $command->getSiteActive()
            )
        );
    }

    /**
     * @param ActivateSite $command
     */
    public function handleActivateSite(ActivateSite $command)
    {
        $this->eventPublisher->publish(
            'Neos.Neos:Site:' . $command->getNodeName(),
            new SiteWasActivated(
                $command->getNodeName()
            )
        );
    }

    /**
     * @param DeactivateSite $command
     */
    public function handleDeactivateSite(DeactivateSite $command)
    {
        $this->eventPublisher->publish(
            'Neos.Neos:Site:' . $command->getNodeName(),
            new SiteWasDeactivated(
                $command->getNodeName()
            )
        );
    }

    /**
     * @param CreateSite $command
     * @throws SiteAlreadyExists
     */
    private function validateSiteMustNotExistConstraint(CreateSite $command): void
    {
        $site = $this->siteFinder->findOneByNodeName($command->getNodeName());
        if ($site !== null) {
            throw new SiteAlreadyExists($command->getNodeName(), 1505997113974);
        }
    }
}
