<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\Service;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\Processors;
use Neos\ContentRepository\Export\Severity;
use Neos\Flow\Package\PackageManager;
use Neos\Utility\Files;

final readonly class SiteImportService implements ContentRepositoryServiceInterface
{
    public function __construct(
        private Processors $processors,
        private PackageManager $packageManager,
    ) {
    }

    /**
     * @param \Closure(string): void $onProcessor Callback that is invoked for each {@see ProcessorInterface} that is processed
     * @param \Closure(Severity, string): void $onMessage Callback that is invoked whenever a {@see ProcessorInterface} dispatches a message
     */
    public function importFromPackage(string $packageKey, \Closure $onProcessor, \Closure $onMessage): void
    {
        $package = $this->packageManager->getPackage($packageKey);
        $path = Files::concatenatePaths([$package->getPackagePath(), 'Resources/Private/Content']);
        if (!is_dir($path)) {
            throw new \InvalidArgumentException(sprintf('No contents for package "%s" at path "%s"', $packageKey, $path), 1728912269);
        }
        $filesystem = new Filesystem(new LocalFilesystemAdapter($path));
        $context = new ProcessingContext($filesystem, $onMessage);
        foreach ($this->processors as $processorLabel => $processor) {
            ($onProcessor)($processorLabel);
            $processor->run($context);
        }
    }
}
