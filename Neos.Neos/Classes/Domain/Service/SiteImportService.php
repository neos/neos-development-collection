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

use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\Exception\InvalidPackageStateException;
use Neos\Flow\Package\Exception\UnknownPackageException;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\EventLog\Domain\Service\EventEmittingService;
use Neos\Neos\Exception as NeosException;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Utility\NodePaths;

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
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceRepository;

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
    protected $imageVariantClassNames = [];

    /**
     * An array that contains all fully qualified class names that implement AssetInterface
     *
     * @var array<string>
     */
    protected $assetClassNames = [];

    /**
     * An array that contains all fully qualified class names that extend \DateTime including \DateTime itself
     *
     * @var array<string>
     */
    protected $dateTimeClassNames = [];

    /**
     * @return void
     */
    public function initializeObject()
    {
        $this->imageVariantClassNames = $this->reflectionService->getAllSubClassNamesForClass(ImageVariant::class);
        array_unshift($this->imageVariantClassNames, ImageVariant::class);

        $this->assetClassNames = $this->reflectionService->getAllImplementationClassNamesForInterface(AssetInterface::class);

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
        if (!$this->packageManager->isPackageAvailable($packageKey)) {
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
        if (!file_exists($pathAndFilename)) {
            throw new NeosException(sprintf('Error: File "%s" does not exist.', $pathAndFilename), 1540934412);
        }

        /** @var Site $importedSite */
        $site = null;
        $xmlReader = new \XMLReader();
        if ($xmlReader->open($pathAndFilename, null, LIBXML_PARSEHUGE) === false) {
            throw new NeosException(sprintf('Error: XMLReader could not open "%s".', $pathAndFilename), 1540934199);
        }

        if ($this->workspaceRepository->findOneByName('live') === null) {
            $this->workspaceRepository->add(new Workspace('live'));
            $this->persistenceManager->persistAll();
        }

        while ($xmlReader->read()) {
            if ($xmlReader->nodeType != \XMLReader::ELEMENT || $xmlReader->name !== 'site') {
                continue;
            }

            $site = $this->getSiteByNodeName($xmlReader->getAttribute('siteNodeName'));
            $site->setName($xmlReader->getAttribute('name'));
            $site->setState((integer)$xmlReader->getAttribute('state'));

            $siteResourcesPackageKey = $xmlReader->getAttribute('siteResourcesPackageKey');
            if (!$this->packageManager->isPackageAvailable($siteResourcesPackageKey)) {
                throw new UnknownPackageException(sprintf('Package "%s" specified in the XML as site resources package does not exist.', $siteResourcesPackageKey), 1303891443);
            }
            if (!$this->packageManager->isPackageAvailable($siteResourcesPackageKey)) {
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
