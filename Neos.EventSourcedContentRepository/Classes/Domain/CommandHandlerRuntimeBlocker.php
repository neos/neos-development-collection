<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\ProcessedEventsAwareProjectorCollection;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\CoreRuntimeBlocker as CoreRuntimeBlocker;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * @Flow\Scope("singleton")
 */
class CommandHandlerRuntimeBlocker
{
    /**
     * @Flow\InjectConfiguration(path="projection.projectorsToBeBlocked")
     * @var array|string[]
     */
    protected array $blockedProjectorClassNames = [];

    protected ObjectManagerInterface $objectManager;

    private CoreRuntimeBlocker $coreRuntimeBlocker;

    private ProcessedEventsAwareProjectorCollection $projectorsToBeBlocked;

    public function __construct(CoreRuntimeBlocker $coreRuntimeBlocker, ObjectManagerInterface $objectManager)
    {
        $this->coreRuntimeBlocker = $coreRuntimeBlocker;
        $this->objectManager = $objectManager;
        $this->projectorsToBeBlocked = new ProcessedEventsAwareProjectorCollection([]);
    }

    public function initializeObject(): void
    {
        $projectors = [];
        foreach ($this->blockedProjectorClassNames as $projectorClassName => $isBlocked) {
            if ($isBlocked) {
                $projector = $this->objectManager->get($projectorClassName);
                $projectors[] = $projector;
            }
        }
        $this->projectorsToBeBlocked = new ProcessedEventsAwareProjectorCollection($projectors);
    }

    public function blockUntilProjectionsAreUpToDate(CommandResult $commandResult): void
    {
        $this->coreRuntimeBlocker->blockUntilProjectionsAreUpToDate($commandResult, $this->projectorsToBeBlocked);
    }
}
