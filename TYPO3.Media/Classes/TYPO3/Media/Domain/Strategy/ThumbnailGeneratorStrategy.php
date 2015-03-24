<?php
namespace TYPO3\Media\Domain\Strategy;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Utility\PositionalArraySorter;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Domain\Model\ThumbnailGenerator\ThumbnailGeneratorInterface;

/**
 * A strategy to detect the correct thumbnail generator
 *
 * @Flow\Scope("singleton")
 */
class ThumbnailGeneratorStrategy
{
    /**
     * @var ObjectManagerInterface
     * @Flow\Inject
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
     * @param ObjectManagerInterface $objectManager
     * @return ThumbnailGeneratorInterface[]
     * @Flow\CompileStatic
     */
    protected static function getThumbnailGeneratorClassNames($objectManager)
    {
        /** @var ReflectionService $reflectionService */
        $reflectionService = $objectManager->get('TYPO3\Flow\Reflection\ReflectionService');
        $generatorClassNames = $reflectionService->getAllImplementationClassNamesForInterface('TYPO3\Media\Domain\Model\ThumbnailGenerator\ThumbnailGeneratorInterface');
        $generators = array();
        foreach ($generatorClassNames as $generatorClassName) {
            /** @var ThumbnailGeneratorInterface $generator */
            $generator = $objectManager->get($generatorClassName);
            $generators[] = array(
                'priority' => (integer)$generator->getPriority(),
                'className' => $generatorClassName
            );
        }

        $sorter = new PositionalArraySorter($generators, 'priority');
        return array_reverse($sorter->toArray());
    }
}
