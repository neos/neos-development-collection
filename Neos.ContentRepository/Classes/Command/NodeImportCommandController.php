<?php

namespace Neos\ContentRepository\Command;

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
use Neos\EventSourcing\Event\EventTypeResolver;
use Neos\EventSourcing\EventStore\EventStoreManager;
use Neos\EventSourcing\Property\AllowAllPropertiesPropertyMappingConfiguration;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\DescriptionAwareCommandControllerInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Reflection\ReflectionService;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 *
 *
 * @Flow\Scope("singleton")
 */
class NodeImportCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var EventTypeResolver
     */
    protected $eventTypeResolver;


    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @Flow\Inject
     * @var EventPublisher
     */
    protected $eventPublisher;

    /**
     * @param string $dumpFile
     */
    public function runCommand($dumpFile)
    {
        $configuration = new AllowAllPropertiesPropertyMappingConfiguration();

        $handle = fopen($dumpFile, "r");
        while (($line = fgets($handle)) !== false) {

            $parsed = json_decode($line, true);
            $stream = $parsed['stream'];
            $type = $parsed['type'];
            $payload = $parsed['payload'];
            $eventClassName = $this->eventTypeResolver->getEventClassNameByType($type);
            $event = $this->propertyMapper->convert($payload, $eventClassName, $configuration);

            $this->eventPublisher->publish($stream, $event);
        }

        fclose($handle);
    }
}
