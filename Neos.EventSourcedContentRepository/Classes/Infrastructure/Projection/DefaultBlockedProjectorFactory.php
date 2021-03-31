<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Infrastructure\Projection;

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
    private array $projectorClassNames;

    private ObjectManagerInterface $objectManager;

    public function __construct(
        array $projectorClassNames,
        ObjectManagerInterface $objectManager
    ) {
        $this->projectorClassNames = $projectorClassNames;
        $this->objectManager = $objectManager;
    }

    public function create(): array
    {
        $projectors = [];
        foreach ($this->projectorClassNames as $projectorClassName => $isToBeBlocked) {
            if ($isToBeBlocked) {
                $projectors[] = $this->objectManager->get($projectorClassName);
            }
        }

        return $projectors;
    }
}
