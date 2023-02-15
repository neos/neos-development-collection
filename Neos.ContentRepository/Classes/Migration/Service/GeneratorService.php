<?php
namespace Neos\ContentRepository\Migration\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageManager;
use Neos\Utility\Files;

/**
 * Service for the Migration generator
 *
 */
class GeneratorService
{

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected PackageManager $packageManager;

    /**
     * @var array
     */
    protected array $generatedFiles = [];

    /**
     * Generate a node migration for the given $packageKey
     *
     * @param string $packageKey the package key
     * @return array
     */
    public function generateNodeMigration(string $packageKey): array
    {
         $templatePath = 'resource://Neos.ContentRepository/Private/Generator/Migrations/ContentRepository/NodeMigrationTemplate.yaml.tmpl';
         $nodeMigrationPath = Files::concatenatePaths([$this->packageManager->getPackage($packageKey)->getPackagePath(), 'Migrations/ContentRepository']) . '/';


        $timeStamp = (new \DateTimeImmutable())->format('YmdHis');
        $nodeMigrationFileName = 'Version' . $timeStamp . '.yaml';

        $targetPathAndFilename = $nodeMigrationPath . $nodeMigrationFileName;

        // @TODO: Implement logic for create, and saving the file.
    }
}
