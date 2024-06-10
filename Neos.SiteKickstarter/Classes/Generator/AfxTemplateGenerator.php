<?php
declare(strict_types=1);

namespace Neos\SiteKickstarter\Generator;

/*
 * This file is part of the Neos.SiteKickstarter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageManager;
use Neos\Kickstarter\Service\GeneratorService;
use Neos\SiteKickstarter\Service\FusionRecursiveDirectoryRenderer;
use Neos\SiteKickstarter\Service\SimpleTemplateRenderer;
use Neos\Utility\Files;

/**
 * Service to generate site packages
 *
 */
class AfxTemplateGenerator extends GeneratorService implements SitePackageGeneratorInterface
{
    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var SimpleTemplateRenderer
     */
    protected $simpleTemplateRenderer;

    /**
     * Generate a site package and fill it with boilerplate data.
     *
     * @param string $packageKey
     * @param string $siteName
     * @return array
     * @throws \Neos\Flow\Composer\Exception\InvalidConfigurationException
     * @throws \Neos\Flow\Package\Exception
     * @throws \Neos\Flow\Package\Exception\CorruptPackageException
     * @throws \Neos\Flow\Package\Exception\InvalidPackageKeyException
     * @throws \Neos\Flow\Package\Exception\PackageKeyAlreadyExistsException
     * @throws \Neos\Flow\Package\Exception\UnknownPackageException
     * @throws \Neos\FluidAdaptor\Core\Exception
     * @throws \Neos\Utility\Exception\FilesException
     */
    public function generateSitePackage(string $packageKey, string $siteName) : array
    {
        $this->packageManager->createPackage($packageKey, [
            'type' => 'neos-site',
            "require" => [
                "neos/neos" => "*"
            ]
        ]);

        $this->generateSitesFusionDirectory($packageKey, $siteName);
        $this->generateNodeTypesConfiguration($packageKey);
        $this->generateAdditionalFolders($packageKey);

        return $this->generatedFiles;
    }

    /**
     * Render the whole directory of the fusion part
     *
     * @param $packageKey
     * @param $siteName
     * @throws \Neos\Flow\Package\Exception\UnknownPackageException
     */
    protected function generateSitesFusionDirectory(string $packageKey, string $siteName) : void
    {
        $contextVariables = [];
        $contextVariables['packageKey'] = $packageKey;
        $contextVariables['siteName'] = $siteName;
        $packageKeyDomainPart = substr(strrchr($packageKey, '.'), 1) ?: $packageKey;
        $contextVariables['siteNodeName'] = $packageKeyDomainPart;

        $fusionRecursiveDirectoryRenderer = new FusionRecursiveDirectoryRenderer();
        $fusionRecursiveDirectoryRenderer->renderDirectory(
            $this->getTemplateFolder() . 'Fusion',
            $this->packageManager->getPackage($packageKey)->getResourcesPath() . 'Private/Fusion',
            $contextVariables
        );
    }

    /**
     * Generate a example NodeTypes.yaml
     *
     * @param string $packageKey
     * @throws \Neos\Flow\Package\Exception\UnknownPackageException
     */
    protected function generateNodeTypesConfiguration(string $packageKey) : void
    {
        $templateFolder = $this->getTemplateFolder() . 'NodeTypes';
        $targetFolder = $this->packageManager->getPackage($packageKey)->getPackagePath() . 'NodeTypes';

        $contextVariables = [
            'packageKey' => $packageKey
        ];

        foreach (Files::readDirectoryRecursively($templateFolder, '.yaml') as $templatePathAndFilename) {
            $fileContent = $this->simpleTemplateRenderer->render($templatePathAndFilename, $contextVariables);
            $targetPathAndFilename = str_replace($templateFolder, $targetFolder, $templatePathAndFilename);
            $this->generateFile($targetPathAndFilename, $fileContent);
        }
    }

    /**
     * Generate additional folders for site packages.
     *
     * @param string $packageKey
     * @throws \Neos\Flow\Package\Exception\UnknownPackageException
     * @throws \Neos\Utility\Exception\FilesException
     */
    protected function generateAdditionalFolders(string $packageKey) : void
    {
        $resourcesPath = $this->packageManager->getPackage($packageKey)->getResourcesPath();
        $publicResourcesPath = Files::concatenatePaths([$resourcesPath, 'Public']);

        foreach (['Images', 'JavaScript', 'Styles'] as $publicResourceFolder) {
            Files::createDirectoryRecursively(Files::concatenatePaths([$publicResourcesPath, $publicResourceFolder]));
        }
    }

    protected function getTemplateFolder(): string
    {
        return $this->packageManager->getPackage('Neos.SiteKickstarter')->getResourcesPath() . 'Private/AfxGenerator/';
    }

    public function getGeneratorName(): string
    {
        return 'Afx Basic';
    }
}
