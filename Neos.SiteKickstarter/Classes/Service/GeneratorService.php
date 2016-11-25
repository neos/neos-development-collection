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
        $this->generateSitesTypoScript($packageKey, $siteName);
        $this->generateSitesTemplate($packageKey, $siteName);
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

        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;
        $contextVariables['siteName'] = htmlspecialchars($siteName);
        $packageKeyDomainPart = substr(strrchr($packageKey, '.'), 1) ?: $packageKey;
        $contextVariables['siteNodeName'] = strtolower($packageKeyDomainPart);
        $contextVariables['dimensions'] = $this->contentDimensionRepository->findAll();

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $sitesXmlPathAndFilename = $this->packageManager->getPackage($packageKey)->getResourcesPath() . 'Private/Content/Sites.xml';
        $this->generateFile($sitesXmlPathAndFilename, $fileContent);
    }

    /**
     * Generate basic TypoScript files.
     *
     * @param string $packageKey
     * @param string $siteName
     * @return void
     */
    protected function generateSitesTypoScript($packageKey, $siteName)
    {
        $templatePathAndFilename = 'resource://Neos.SiteKickstarter/Private/Generator/TypoScript/Root.ts2';

        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;
        $contextVariables['siteName'] = $siteName;
        $packageKeyDomainPart = substr(strrchr($packageKey, '.'), 1) ?: $packageKey;
        $contextVariables['siteNodeName'] = $packageKeyDomainPart;

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $sitesTypoScriptPathAndFilename = $this->packageManager->getPackage($packageKey)->getResourcesPath() . 'Private/TypoScript/Root.ts2';
        $this->generateFile($sitesTypoScriptPathAndFilename, $fileContent);
    }

    /**
     * Generate basic template file.
     *
     * @param string $packageKey
     * @param string $siteName
     * @return void
     */
    protected function generateSitesTemplate($packageKey, $siteName)
    {
        $templatePathAndFilename = 'resource://Neos.SiteKickstarter/Private/Generator/Template/SiteTemplate.html';

        $contextVariables = array();
        $contextVariables['siteName'] = $siteName;
        $contextVariables['neosViewHelper'] = '{namespace neos=Neos\Neos\ViewHelpers}';
        $contextVariables['typoScriptViewHelper'] = '{namespace ts=Neos\Fusion\ViewHelpers}';
        $packageKeyDomainPart = substr(strrchr($packageKey, '.'), 1) ?: $packageKey;
        $contextVariables['siteNodeName'] = lcfirst($packageKeyDomainPart);

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $sitesTypoScriptPathAndFilename = $this->packageManager->getPackage($packageKey)->getResourcesPath() . 'Private/Templates/Page/Default.html';
        $this->generateFile($sitesTypoScriptPathAndFilename, $fileContent);
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

        $sitesTypoScriptPathAndFilename = $this->packageManager->getPackage($packageKey)->getConfigurationPath() . 'NodeTypes.yaml';
        $this->generateFile($sitesTypoScriptPathAndFilename, $fileContent);
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
