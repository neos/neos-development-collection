<?php

namespace Neos\ContentRepositoryRegistry\Service;

/*
 * This file is part of the Neos.ContentRepositoryRegistry package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Package\Exception\UnknownPackageException;
use Neos\Flow\Package\PackageManager;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;

/**
 * Service for the Node Migration generator
 *
 */
class NodeMigrationGeneratorService
{

    public function __construct(
        private readonly PackageManager $packageManager
    ) {
    }

    /**
     * Creates a node migration for the given $packageKey
     *
     * @param string $packageKey the package key
     * @return string
     * @throws UnknownPackageException
     * @throws FilesException
     */
    public function generateBoilerplateMigrationFileInPackage(string $packageKey): string
    {
        $templatePath = 'resource://Neos.ContentRepositoryRegistry/Private/Generator/Migrations/ContentRepository/NodeMigrationTemplate.yaml.tmpl';
        $nodeMigrationPath = Files::concatenatePaths([$this->packageManager->getPackage($packageKey)->getPackagePath(), 'Migrations/ContentRepository']) . '/';

        $timeStamp = (new \DateTimeImmutable())->format('YmdHis');
        $nodeMigrationFileName = 'Version' . $timeStamp . '.yaml';

        $targetPathAndFilename = $nodeMigrationPath . $nodeMigrationFileName;
        $fileContent = file_get_contents($templatePath);

        if (!is_dir(dirname($targetPathAndFilename))) {
            Files::createDirectoryRecursively(dirname($targetPathAndFilename));
        }

        file_put_contents($targetPathAndFilename, $fileContent);

        return $packageKey . '/Migrations/ContentRepository/' . $nodeMigrationFileName;
    }
}
