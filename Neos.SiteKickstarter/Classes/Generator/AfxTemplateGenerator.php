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
use Neos\Flow\Package\FlowPackageInterface;
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
     * @return list<string>
     * @throws \Neos\Flow\Composer\Exception\InvalidConfigurationException
     * @throws \Neos\Flow\Package\Exception
     * @throws \Neos\Flow\Package\Exception\CorruptPackageException
     * @throws \Neos\Flow\Package\Exception\InvalidPackageKeyException
     * @throws \Neos\Flow\Package\Exception\PackageKeyAlreadyExistsException
     * @throws \Neos\Flow\Package\Exception\UnknownPackageException
     * @throws \Neos\FluidAdaptor\Core\Exception
     * @throws \Neos\Utility\Exception\FilesException
     */
    public function generateSitePackage(string $packageKey) : array
    {
        $package = $this->packageManager->createPackage($packageKey, [
            'type' => 'neos-site',
            'require' => [
                'neos/neos' => '*'
            ]
        ]);

        if (!$package instanceof FlowPackageInterface) {
            throw new \RuntimeException('Expected to generate flow site package for "' . $packageKey . '" but got ' . get_class($package));
        }

        $this->generateSitesFusionDirectory($package);
        $this->generateNodeTypesConfiguration($package);
        $this->generateAdditionalFolders($package);

        return $this->generatedFiles;
    }

    /**
     * Render the whole directory of the fusion part
     */
    protected function generateSitesFusionDirectory(FlowPackageInterface $package) : void
    {
        $contextVariables = [
            'packageKey' => $package->getPackageKey(),
        ];

        $fusionRecursiveDirectoryRenderer = new FusionRecursiveDirectoryRenderer();
        $fusionRecursiveDirectoryRenderer->renderDirectory(
            $this->getTemplateFolder() . 'Fusion',
            $package->getResourcesPath() . 'Private/Fusion',
            $contextVariables
        );
    }

    /**
     * Generate a example NodeTypes.yaml
     */
    protected function generateNodeTypesConfiguration(FlowPackageInterface $package) : void
    {
        $templateFolder = $this->getTemplateFolder() . 'NodeTypes';
        $targetFolder = $package->getPackagePath() . 'NodeTypes';

        $contextVariables = [
            'packageKey' => $package->getPackageKey(),
        ];

        foreach (Files::readDirectoryRecursively($templateFolder, '.yaml') as $templatePathAndFilename) {
            $fileContent = $this->simpleTemplateRenderer->render($templatePathAndFilename, $contextVariables);
            $targetPathAndFilename = str_replace($templateFolder, $targetFolder, $templatePathAndFilename);
            $this->generateFile($targetPathAndFilename, $fileContent);
        }
    }

    /**
     * Generate additional folders for site packages.
     */
    protected function generateAdditionalFolders(FlowPackageInterface $package) : void
    {
        $resourcesPath = $package->getResourcesPath();
        $publicResourcesPath = Files::concatenatePaths([$resourcesPath, 'Public']);

        foreach (['Images', 'JavaScript', 'Styles'] as $publicResourceFolder) {
            Files::createDirectoryRecursively(Files::concatenatePaths([$publicResourcesPath, $publicResourceFolder]));
        }
    }

    protected function getTemplateFolder(): string
    {
        $currentPackage = $this->packageManager->getPackage('Neos.SiteKickstarter');
        assert($currentPackage instanceof FlowPackageInterface);
        return $currentPackage->getResourcesPath() . 'Private/AfxGenerator/';
    }

    public function getGeneratorName(): string
    {
        return 'Afx Basic';
    }
}
