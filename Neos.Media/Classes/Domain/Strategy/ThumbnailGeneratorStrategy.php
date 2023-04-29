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
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Media\Domain\Model\Thumbnail;
use Neos\Media\Domain\Model\ThumbnailGenerator\ThumbnailGeneratorInterface;
use Neos\Media\Exception\NoThumbnailAvailableException;
use Neos\Utility\PositionalArraySorter;
use Psr\Log\LoggerInterface;

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
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @Flow\Inject
     * @var ThrowableStorageInterface
     */
    protected $throwableStorage;

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
            $className = $generator['className'];
            $generator = $this->objectManager->get($className);
            if (!$generator->canRefresh($thumbnail)) {
                continue;
            }
            try {
                $generator->refresh($thumbnail);
                return;
            } catch (NoThumbnailAvailableException $exception) {
                $message = $this->throwableStorage->logThrowable($exception);
                $this->logger->error(sprintf('%s.refresh() failed, trying next generator. %s', $className, $message), LogEnvironment::fromMethodName(__METHOD__));
            }
        }

        $this->logger->error(sprintf('All thumbnail generators failed to generate a thumbnail for asset %s (%s)', $thumbnail->getOriginalAsset()->getResource()->getSha1(), $thumbnail->getOriginalAsset()->getResource()->getFilename()), LogEnvironment::fromMethodName(__METHOD__));
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
