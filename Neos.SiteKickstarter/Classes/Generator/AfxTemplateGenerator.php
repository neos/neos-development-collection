<?php

namespace Neos\SiteKickstarter\Generator;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageManager;
use Neos\Utility\Files;
use Neos\ContentRepository\Domain\Repository\ContentDimensionRepository;
use Neos\ContentRepository\Utility;
use Neos\SiteKickstarter\Annotation as SiteKickstarter;
use Neos\SiteKickstarter\Service\FusionRecursiveDirectoryRenderer;

/**
 * Service to generate site packages
 *
 * @SiteKickstarter\SitePackageGenerator("Afx Basic")
 */
class AfxTemplateGenerator extends AbstractSitePackageGenerator
{
    /**
     * @Flow\Inject
     * @var PackageManager
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
        $this->generateSitesFusionDirectory($packageKey, $siteName);
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
        $templatePathAndFilename = $this->getResourcePathForFile('Content/Sites.xml');

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
     * Generate basic root Fusion file.
     *
     * @param string $packageKey
     * @param string $siteName
     * @return void
     */
    protected function generateSitesRootFusion($packageKey, $siteName)
    {
        $templatePathAndFilename = $this->getResourcePathForFile('Fusion/Root.fusion');

        $contextVariables = [
            'packageKey' => $packageKey,
            'siteName' => $siteName,
            'siteNodeName' => $this->generateSiteNodeName($packageKey)
        ];

        $fileContent = $this->renderSimpleTemplate($templatePathAndFilename, $contextVariables);

        $sitesRootFusionPathAndFilename = $this->packageManager->getPackage($packageKey)->getResourcesPath() . 'Private/Fusion/Root.fusion';
        $this->generateFile($sitesRootFusionPathAndFilename, $fileContent);
    }

    /**
     * Render the whole directory of the fusion part
     *
     * @param $packageKey
     * @param $siteName
     * @throws \Neos\Flow\Package\Exception\UnknownPackageException
     */
    protected function generateSitesFusionDirectory($packageKey, $siteName)
    {
        $contextVariables = [];
        $contextVariables['packageKey'] = $packageKey;
        $contextVariables['siteName'] = $siteName;
        $packageKeyDomainPart = substr(strrchr($packageKey, '.'), 1) ?: $packageKey;
        $contextVariables['siteNodeName'] = $packageKeyDomainPart;

        $fusionRecursiveDirectoryRenderer = new FusionRecursiveDirectoryRenderer();

        $packageDirectory = $this->packageManager->getPackage('Neos.SiteKickstarter')->getResourcesPath();

        $fusionRecursiveDirectoryRenderer->renderDirectory(
            $packageDirectory . 'Private/AfxGenerator/Fusion',
            $this->packageManager->getPackage($packageKey)->getResourcesPath() . 'Private/Fusion',
            $contextVariables
        );
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
     * @throws \Neos\FluidAdaptor\Core\Exception
     */
    protected function generateNodeTypesConfiguration($packageKey)
    {
        $templatePathAndFilename = $this->getResourcePathForFile('Configuration/NodeTypes.Document.Page.yaml');

        $contextVariables = [
            'packageKey' => $packageKey
        ];

        $fileContent = $this->renderSimpleTemplate($templatePathAndFilename, $contextVariables);

        $sitesNodeTypesPathAndFilename = $this->packageManager->getPackage($packageKey)->getConfigurationPath() . 'NodeTypes.Document.Page.yaml';
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
        $publicResourcesPath = Files::concatenatePaths([$resourcesPath, 'Public']);

        foreach (['Images', 'JavaScript', 'Styles'] as $publicResourceFolder) {
            Files::createDirectoryRecursively(Files::concatenatePaths([$publicResourcesPath, $publicResourceFolder]));
        }
    }

    /**
     * Simplified template rendering
     *
     * @param string $templatePathAndFilename
     * @param array $contextVariables
     * @return string
     */
    protected function renderSimpleTemplate($templatePathAndFilename, array $contextVariables)
    {
        $content = file_get_contents($templatePathAndFilename);
        foreach ($contextVariables as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        return $content;
    }

    /**
     * returns resource path for the generator
     *
     * @param $pathToFile
     * @return string
     */
    protected function getResourcePathForFile($pathToFile)
    {
        return 'resource://Neos.SiteKickstarter/Private/AfxGenerator/' . $pathToFile;
    }
}
