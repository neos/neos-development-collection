<?php
namespace Neos\Neos\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
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
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Exception as NeosException;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\ImportExport\NodeExportService;

/**
 * The Site Export Service
 *
 * @Flow\Scope("singleton")
 */
class SiteExportService
{
    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     *
     * @var NodeExportService
     */
    protected $nodeExportService;

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * Absolute path to exported resources, or NULL if resources should be inlined in the exported XML
     *
     * @var string
     */
    protected $resourcesPath = null;

    /**
     * The XMLWriter that is used to construct the export.
     *
     * @var \XMLWriter
     */
    protected $xmlWriter;

    /**
     * Fetches the site with the given name and exports it into XML.
     *
     * @param array<Site> $sites
     * @param boolean $tidy Whether to export formatted XML
     * @param string $nodeTypeFilter Filter the node type of the nodes, allows complex expressions (e.g. "Neos.Neos:Page", "!Neos.Neos:Page,Neos.Neos:Text")
     * @return string
     */
    public function export(array $sites, $tidy = false, $nodeTypeFilter = null)
    {
        $this->xmlWriter = new \XMLWriter();
        $this->xmlWriter->openMemory();
        $this->xmlWriter->setIndent($tidy);

        $this->exportSites($sites, $nodeTypeFilter);

        return $this->xmlWriter->outputMemory(true);
    }

    /**
     * Fetches the site with the given name and exports it into XML in the given package.
     *
     * @param array<Site> $sites
     * @param boolean $tidy Whether to export formatted XML
     * @param string $packageKey Package key where the export output should be saved to
     * @param string $nodeTypeFilter Filter the node type of the nodes, allows complex expressions (e.g. "Neos.Neos:Page", "!Neos.Neos:Page,Neos.Neos:Text")
     * @return void
     * @throws NeosException
     */
    public function exportToPackage(array $sites, $tidy = false, $packageKey, $nodeTypeFilter = null)
    {
        if (!$this->packageManager->isPackageAvailable($packageKey)) {
            throw new NeosException(sprintf('Error: Package "%s" is not active.', $packageKey), 1404375719);
        }
        $contentPathAndFilename = sprintf('resource://%s/Private/Content/Sites.xml', $packageKey);

        $this->resourcesPath = Files::concatenatePaths([dirname($contentPathAndFilename), 'Resources']);
        Files::createDirectoryRecursively($this->resourcesPath);

        $this->xmlWriter = new \XMLWriter();
        $this->xmlWriter->openUri($contentPathAndFilename);
        $this->xmlWriter->setIndent($tidy);

        $this->exportSites($sites, $nodeTypeFilter);

        $this->xmlWriter->flush();
    }

    /**
     * Fetches the site with the given name and exports it as XML into the given file.
     *
     * @param array<Site> $sites
     * @param boolean $tidy Whether to export formatted XML
     * @param string $pathAndFilename Path to where the export output should be saved to
     * @param string $nodeTypeFilter Filter the node type of the nodes, allows complex expressions (e.g. "Neos.Neos:Page", "!Neos.Neos:Page,Neos.Neos:Text")
     * @return void
     */
    public function exportToFile(array $sites, $tidy = false, $pathAndFilename, $nodeTypeFilter = null)
    {
        $this->resourcesPath = Files::concatenatePaths([dirname($pathAndFilename), 'Resources']);
        Files::createDirectoryRecursively($this->resourcesPath);

        $this->xmlWriter = new \XMLWriter();
        $this->xmlWriter->openUri($pathAndFilename);
        $this->xmlWriter->setIndent($tidy);

        $this->exportSites($sites, $nodeTypeFilter);

        $this->xmlWriter->flush();
    }

    /**
     * Exports the given sites to the XMLWriter
     *
     * @param array<Site> $sites
     * @param string $nodeTypeFilter
     * @return void
     */
    protected function exportSites(array $sites, $nodeTypeFilter)
    {
        $this->xmlWriter->startDocument('1.0', 'UTF-8');
        $this->xmlWriter->startElement('root');

        foreach ($sites as $site) {
            $this->exportSite($site, $nodeTypeFilter);
        }

        $this->xmlWriter->endElement();
        $this->xmlWriter->endDocument();
    }

    /**
     * Export the given $site to the XMLWriter
     *
     * @param Site $site
     * @param string $nodeTypeFilter
     * @return void
     */
    protected function exportSite(Site $site, $nodeTypeFilter)
    {
        /** @var ContentContext $contentContext */
        $contentContext = $this->contextFactory->create([
            'currentSite' => $site,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true
        ]);

        $siteNode = $contentContext->getCurrentSiteNode();

        $this->xmlWriter->startElement('site');
        $this->xmlWriter->writeAttribute('name', $site->getName());
        $this->xmlWriter->writeAttribute('state', $site->getState());
        $this->xmlWriter->writeAttribute('siteResourcesPackageKey', $site->getSiteResourcesPackageKey());
        $this->xmlWriter->writeAttribute('siteNodeName', $siteNode->getName());

        $this->nodeExportService->export($siteNode->getPath(), $contentContext->getWorkspaceName(), $this->xmlWriter, false, false, $this->resourcesPath, $nodeTypeFilter);

        $this->xmlWriter->endElement();
    }
}
