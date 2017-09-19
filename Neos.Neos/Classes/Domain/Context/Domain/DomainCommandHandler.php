<?php
namespace Neos\Neos\Domain\Context\Domain;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\Workspace\Command\CreateWorkspace;
use Neos\ContentRepository\Domain\Context\Workspace\Event\WorkspaceHasBeenCreated;
use Neos\EventSourcing\Event\EventPublisher;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Context\Domain\Command\AddDomain;
use Neos\Neos\Domain\Context\Domain\Event\DomainHasBeenAdded;

/**
 * WorkspaceCommandHandler
 */
final class DomainCommandHandler
{
    /**
     * @Flow\Inject
     * @var EventPublisher
     */
    protected $eventPublisher;

    /**
     * @param AddDomain $command
     */
    public function handleAddDomain(AddDomain $command)
    {
        // TODO: Necessary checks

        $this->eventPublisher->publish(
            'Neos.Neos:Domain:' . $command->getDomainHostname(),
            new DomainHasBeenAdded(
                $command->getSiteNodeName(),
                $command->getDomainHostname(),
                $command->getScheme(),
                $command->getPort()
            )
        );
    }

    /**
     * @param ActivateDomain $command
     */
    public function handleActivateDomain(ActivateDomain $command)
    {

        $this->eventPublisher->publish(
            'Neos.Neos:Domain:' . $command->getHostName(),
            new DomainHasBeenActivated(
                $command->getHostName()
            )
        );
    }
}
