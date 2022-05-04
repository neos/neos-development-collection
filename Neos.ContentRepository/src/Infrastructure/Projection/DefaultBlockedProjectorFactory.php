<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Infrastructure\Projection;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\ObjectManagerInterface;

final class DefaultBlockedProjectorFactory
{
    /**
     * @var array<class-string,bool>
     */
    private array $projectorClassNames;

    private ObjectManagerInterface $objectManager;

    /**
     * @param array<class-string,bool> $projectorClassNames
     */
    public function __construct(
        array $projectorClassNames,
        ObjectManagerInterface $objectManager
    ) {
        $this->projectorClassNames = $projectorClassNames;
        $this->objectManager = $objectManager;
    }

    public function create(): ProcessedEventsAwareProjectorCollection
    {
        $projectors = [];
        foreach ($this->projectorClassNames as $projectorClassName => $isToBeBlocked) {
            if ($isToBeBlocked) {
                $projector = $this->objectManager->get($projectorClassName);
                if (!$projector instanceof ProcessedEventsAwareProjectorInterface) {
                    throw new \InvalidArgumentException(
                        'ProcessedEventsAwareProjectorCollection can only consist of '
                        . ProcessedEventsAwareProjectorInterface::class . ' objects.',
                        1616950763
                    );
                }
                $projectors[] = $projector;
            }
        }

        return new ProcessedEventsAwareProjectorCollection($projectors);
    }
}
