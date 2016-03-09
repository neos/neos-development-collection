<?php
namespace TYPO3\Neos\Domain\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Package\Exception\InvalidPackageStateException;
use TYPO3\Flow\Package\Exception\UnknownPackageException;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\EventLog\Domain\Service\EventEmittingService;
use TYPO3\Neos\Exception as NeosException;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Service\ImportExport\NodeImportService;
use TYPO3\TYPO3CR\Domain\Utility\NodePaths;

/**
 * The Site Import Service
 *
 * @Flow\Scope("singleton")
 * @api
 */
class SiteImportService
{
    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var NodeImportService
     */
    protected $nodeImportService;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var LegacySiteImportService
     */
    protected $legacySiteImportService;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var EventEmittingService
     */
    protected $eventEmittingService;

    /**
     * @var string
     */
    protected $resourcesPath = null;

    /**
     * An array that contains all fully qualified class names that extend ImageVariant including ImageVariant itself
     *
     * @var array<string>
     */
    protected $imageVariantClassNames = array();

    /**
     * An array that contains all fully qualified class names that implement AssetInterface
     *
     * @var array<string>
     */
    protected $assetClassNames = array();

    /**
     * An array that contains all fully qualified class names that extend \DateTime including \DateTime itself
     *
     * @var array<string>
     */
    protected $dateTimeClassNames = array();

    /**
     * @return void
     */
    public function initializeObject()
    {
        $this->imageVariantClassNames = $this->reflectionService->getAllSubClassNamesForClass('TYPO3\Media\Domain\Model\ImageVariant');
        array_unshift($this->imageVariantClassNames, 'TYPO3\Media\Domain\Model\ImageVariant');

        $this->assetClassNames = $this->reflectionService->getAllImplementationClassNamesForInterface('TYPO3\Media\Domain\Model\AssetInterface');

        $this->dateTimeClassNames = $this->reflectionService->getAllSubClassNamesForClass('DateTime');
        array_unshift($this->dateTimeClassNames, 'DateTime');
    }

    /**
     * Checks for the presence of Sites.xml in the given package and imports it if found.
     *
     * @param string $packageKey
     * @return Site the imported site
     * @throws NeosException
     */
    public function importFromPackage($packageKey)
    {
        if (!$this->packageManager->isPackageActive($packageKey)) {
            throw new NeosException(sprintf('Error: Package "%s" is not active.', $packageKey), 1384192950);
        }
        $contentPathAndFilename = sprintf('resource://%s/Private/Content/Sites.xml', $packageKey);
        if (!file_exists($contentPathAndFilename)) {
            throw new NeosException(sprintf('Error: No content found in package "%s".', $packageKey), 1384192955);
        }
        try {
            return $this->importFromFile($contentPathAndFilename);
        } catch (\Exception $exception) {
            throw new NeosException(sprintf('Error: During import an exception occurred: "%s".', $exception->getMessage()), 1300360480, $exception);
        }
    }

    /**
     * Imports one or multiple sites from the XML file at $pathAndFilename
     *
     * @param string $pathAndFilename
     * @return Site The imported site
     * @throws UnknownPackageException|InvalidPackageStateException|NeosException
     */
    public function importFromFile($pathAndFilename)
    {
        /** @var Site $importedSite */
        $site = null;
        $xmlReader = new \XMLReader();
        $xmlReader->open($pathAndFilename, null, LIBXML_PARSEHUGE);

        if ($this->workspaceRepository->findOneByName('live') === null) {
            $this->workspaceRepository->add(new Workspace('live'));
            $this->persistenceManager->persistAll();
        }

        while ($xmlReader->read()) {
            if ($xmlReader->nodeType != \XMLReader::ELEMENT || $xmlReader->name !== 'site') {
                continue;
            }
            $isLegacyFormat = $xmlReader->getAttribute('nodeName') !== null && $xmlReader->getAttribute('state') === null && $xmlReader->getAttribute('siteResourcesPackageKey') === null;
            if ($isLegacyFormat) {
                $site = $this->legacySiteImportService->importSitesFromFile($pathAndFilename);
                $this->emitSiteImported($site);
                return $site;
            }

            $site = $this->getSiteByNodeName($xmlReader->getAttribute('siteNodeName'));
            $site->setName($xmlReader->getAttribute('name'));
            $site->setState((integer)$xmlReader->getAttribute('state'));

            $siteResourcesPackageKey = $xmlReader->getAttribute('siteResourcesPackageKey');
            if (!$this->packageManager->isPackageAvailable($siteResourcesPackageKey)) {
                throw new UnknownPackageException(sprintf('Package "%s" specified in the XML as site resources package does not exist.', $siteResourcesPackageKey), 1303891443);
            }
            if (!$this->packageManager->isPackageActive($siteResourcesPackageKey)) {
                throw new InvalidPackageStateException(sprintf('Package "%s" specified in the XML as site resources package is not active.', $siteResourcesPackageKey), 1303898135);
            }
            $site->setSiteResourcesPackageKey($siteResourcesPackageKey);

            $rootNode = $this->contextFactory->create()->getRootNode();
            // We fetch the workspace to be sure it's known to the persistence manager and persist all
            // so the workspace and site node are persisted before we import any nodes to it.
            $rootNode->getContext()->getWorkspace();
            $this->persistenceManager->persistAll();
            $sitesNode = $rootNode->getNode(SiteService::SITES_ROOT_PATH);
            if ($sitesNode === null) {
                $sitesNode = $rootNode->createNode(NodePaths::getNodeNameFromPath(SiteService::SITES_ROOT_PATH));
            }

            $this->nodeImportService->import($xmlReader, $sitesNode->getPath(), dirname($pathAndFilename) . '/Resources');
        }

        if ($site === null) {
            throw new NeosException(sprintf('The XML file did not contain a valid site node.'), 1418999522);
        }
        $this->emitSiteImported($site);
        return $site;
    }

    /**
     * Updates or creates a site with the given $siteNodeName
     *
     * @param string $siteNodeName
     * @return Site
     */
    protected function getSiteByNodeName($siteNodeName)
    {
        $site = $this->siteRepository->findOneByNodeName($siteNodeName);

        if ($site === null) {
            $site = new Site($siteNodeName);
            $this->siteRepository->add($site);
        } else {
            $this->siteRepository->update($site);
        }

        return $site;
    }


    /**
     * Signal that is triggered when a site has been imported successfully
     *
     * @Flow\Signal
     * @param Site $site The site that has been imported
     * @return void
     */
    protected function emitSiteImported(Site $site)
    {
    }
}
