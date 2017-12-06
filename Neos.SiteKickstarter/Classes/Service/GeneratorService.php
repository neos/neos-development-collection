<?php
namespace Neos\SiteKickstarter\Service;

/*
 * This file is part of the Neos.Kickstarterer package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Utility\Files;
use Neos\ContentRepository\Domain\Repository\ContentDimensionRepository;
use Neos\ContentRepository\Utility;

/**
 * Service to generate site packages
 */
class GeneratorService extends \Neos\Kickstarter\Service\GeneratorService
{
    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var ContentDimensionRepository
     */
    protected $contentDimensionRepository;

    /**
     * Generate a site package and fill it with boilerplate data.
     *
     * @param string $packageKey
     * @param string $siteName
     * @return array
     */
    public function generateSitePackage($packageKey, $siteName)
    {
        $this->packageManager->createPackage($packageKey, [
            'type' => 'neos-site',
            "require" => [
                "neos/neos" => "*",
                "neos/nodetypes" => "*"
            ],
            "suggest" => [
                "neos/seo" => "*"
            ]
        ]);

        $this->generateSitesXml($packageKey, $siteName);
        $this->generateSitesFusion($packageKey, $siteName);
        $this->generateDefaultTemplate($packageKey, $siteName);
        $this->generateNodeTypesConfiguration($packageKey);
        $this->generateAdditionalFolders($packageKey);

        return $this->generatedFiles;
    }

    /**
     * Generate a "Sites.xml" for the given package and name.
     *
     * @param string $packageKey
     * @param string $siteName
     * @return void
     */
    protected function generateSitesXml($packageKey, $siteName)
    {
        $templatePathAndFilename = 'resource://Neos.SiteKickstarter/Private/Generator/Content/Sites.xml';

        $contextVariables = [
            'packageKey' => $packageKey,
            'siteName' => htmlspecialchars($siteName),
            'siteNodeName' => $this->generateSiteNodeName($packageKey),
            'dimensions' => $this->contentDimensionRepository->findAll()
        ];

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $sitesXmlPathAndFilename = $this->packageManager->getPackage($packageKey)->getResourcesPath() . 'Private/Content/Sites.xml';
        $this->generateFile($sitesXmlPathAndFilename, $fileContent);
    }

    /**
     * Generate basic Fusion files.
     *
     * @param string $packageKey
     * @param string $siteName
     * @return void
     */
    protected function generateSitesFusion($packageKey, $siteName)
    {
        $templatePathAndFilename = 'resource://Neos.SiteKickstarter/Private/Generator/Fusion/Root.fusion';

        $contextVariables = [
            'packageKey' => $packageKey,
            'siteName' => $siteName,
            'siteNodeName' => $this->generateSiteNodeName($packageKey)
        ];

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $sitesFusionPathAndFilename = $this->packageManager->getPackage($packageKey)->getResourcesPath() . 'Private/Fusion/Root.fusion';
        $this->generateFile($sitesFusionPathAndFilename, $fileContent);
    }

    /**
     * Generate basic template file.
     *
     * @param string $packageKey
     * @param string $siteName
     * @return void
     */
    protected function generateDefaultTemplate($packageKey, $siteName)
    {
        $templatePathAndFilename = 'resource://Neos.SiteKickstarter/Private/Generator/Template/SiteTemplate.html';

        $contextVariables = [
            'siteName' => $siteName,
            'neosViewHelper' => '{namespace neos=Neos\Neos\ViewHelpers}',
            'fusionViewHelper' => '{namespace fusion=Neos\Fusion\ViewHelpers}',
            'siteNodeName' => $this->generateSiteNodeName($packageKey)
        ];

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $defaultTemplatePathAndFilename = $this->packageManager->getPackage($packageKey)->getResourcesPath() . 'Private/Templates/Page/Default.html';
        $this->generateFile($defaultTemplatePathAndFilename, $fileContent);
    }

    /**
     * Generate site node name based on the given package key
     *
     * @param string $packageKey
     * @return string
     */
    protected function generateSiteNodeName($packageKey)
    {
        return Utility::renderValidNodeName($packageKey);
    }

    /**
     * Generate a example NodeTypes.yaml
     *
     * @param string $packageKey
     * @return void
     */
    protected function generateNodeTypesConfiguration($packageKey)
    {
        $templatePathAndFilename = 'resource://Neos.SiteKickstarter/Private/Generator/Configuration/NodeTypes.yaml';

        $fileContent = file_get_contents($templatePathAndFilename);

        $sitesNodeTypesPathAndFilename = $this->packageManager->getPackage($packageKey)->getConfigurationPath() . 'NodeTypes.yaml';
        $this->generateFile($sitesNodeTypesPathAndFilename, $fileContent);
    }

    /**
     * Generate additional folders for site packages.
     *
     * @param string $packageKey
     */
    protected function generateAdditionalFolders($packageKey)
    {
        $resourcesPath = $this->packageManager->getPackage($packageKey)->getResourcesPath();
        $publicResourcesPath = Files::concatenatePaths(array($resourcesPath, 'Public'));

        foreach (array('Images', 'JavaScript', 'Styles') as $publicResourceFolder) {
            Files::createDirectoryRecursively(Files::concatenatePaths(array($publicResourcesPath, $publicResourceFolder)));
        }
    }
}
