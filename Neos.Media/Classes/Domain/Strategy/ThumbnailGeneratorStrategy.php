<?php
namespace Neos\Media\Domain\Strategy;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Utility\PositionalArraySorter;
use Neos\Media\Domain\Model\Thumbnail;
use Neos\Media\Domain\Model\ThumbnailGenerator\ThumbnailGeneratorInterface;

/**
 * A strategy to detect the correct thumbnail generator
 *
 * @Flow\Scope("singleton")
 */
class ThumbnailGeneratorStrategy
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Refresh the given thumbnail
     *
     * @param Thumbnail $thumbnail
     * @return void
     */
    public function refresh(Thumbnail $thumbnail)
    {
        $generatorClassNames = static::getThumbnailGeneratorClassNames($this->objectManager);
        foreach ($generatorClassNames as $generator) {
            $generator = $this->objectManager->get($generator['className']);
            if (!$generator->canRefresh($thumbnail)) {
                continue;
            }
            $generator->refresh($thumbnail);
            return;
        }
    }

    /**
     * Returns all class names implementing the ThumbnailGeneratorInterface.
     *
     * @Flow\CompileStatic
     * @param ObjectManagerInterface $objectManager
     * @return ThumbnailGeneratorInterface[]
     */
    public static function getThumbnailGeneratorClassNames($objectManager)
    {
        /** @var ReflectionService $reflectionService */
        $reflectionService = $objectManager->get(ReflectionService::class);
        $generatorClassNames = $reflectionService->getAllImplementationClassNamesForInterface(ThumbnailGeneratorInterface::class);
        $configurationManager = $objectManager->get(ConfigurationManager::class);
        $generatorOptions = $configurationManager->getConfiguration('Settings', 'Neos.Media.thumbnailGenerators');
        $generators = [];
        foreach ($generatorClassNames as $generatorClassName) {
            if (isset($generatorOptions[$generatorClassName]['disable']) && $generatorOptions[$generatorClassName]['disable'] === true) {
                continue;
            }
            if (isset($generatorOptions[$generatorClassName]['priority'])) {
                $priority = $generatorOptions[$generatorClassName]['priority'];
            } else {
                $priority = $generatorClassName::getPriority();
            }
            $generators[] = [
                'priority' => (integer)$priority,
                'className' => $generatorClassName
            ];
        }

        $sorter = new PositionalArraySorter($generators, 'priority');
        return array_reverse($sorter->toArray());
    }
}
