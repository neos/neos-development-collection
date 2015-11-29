<?php
namespace TYPO3\Media\Domain\Strategy;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
        $configurationManager = $objectManager->get(\TYPO3\Flow\Configuration\ConfigurationManager::class);
        $generatorOptions = $configurationManager->getConfiguration('Settings', 'TYPO3.Media.thumbnailGenerators');
        $generators = array();
        foreach ($generatorClassNames as $generatorClassName) {
            /** @var ThumbnailGeneratorInterface $generator */
            $generator = $objectManager->get($generatorClassName);
            if (isset($generatorOptions[$generatorClassName]['priority'])) {
                $priority = $generatorOptions[$generatorClassName]['priority'];
            } else {
                $priority = $generatorClassName::getPriority();
            }
            $generators[] = array(
                'priority' => (integer)$priority,
                'className' => $generatorClassName
            );
        }

        $sorter = new PositionalArraySorter($generators, 'priority');
        return array_reverse($sorter->toArray());
    }
}
