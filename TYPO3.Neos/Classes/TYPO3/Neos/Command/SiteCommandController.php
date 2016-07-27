<?php
namespace TYPO3\Neos\Command;

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
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\SiteExportService;
use TYPO3\Neos\Domain\Service\SiteImportService;
use TYPO3\Neos\Domain\Service\SiteService;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;
use TYPO3\TYPO3CR\Domain\Service\NodeService;

/**
 * The Site Command Controller
 *
 * @Flow\Scope("singleton")
 */
class SiteCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var SiteImportService
     */
    protected $siteImportService;

    /**
     * @Flow\Inject
     * @var SiteExportService
     */
    protected $siteExportService;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var SiteService
     */
    protected $siteService;

    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $nodeContextFactory;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var NodeService
     */
    protected $nodeService;

    /**
     * Create a new (blank) site
     *
     * This command allows to create a blank site with just a single empty document. The name of the site and the
     * packageKey to assign to it must be specified.
     *
     * If no "--nodeName" is specified the command will create a unique node-name from the name of the site. If a node name is given
     * it has to be unique.
     *
     * The specified "--nodeType" must it exists and have the superType "TYPO3.Neos:Document".
     *
     * If the flag "--active" is set the created site will be activated immediately.
     *
     * @param string $name The name of the site
     * @param string $packageKey The site package
     * @param string $nodeType The nodetype to use for the site node.
     * @param string $nodeName The name of the site node. If no nodeName is given it will be determined from the siteName.
     * @param boolean $active Activate the new site immediately.
     * @return void
     */
    public function createCommand($name, $packageKey, $nodeType, $nodeName = null, $active = false)
    {
        if ($nodeName === null) {
            $nodeName = $this->nodeService->generateUniqueNodeName('/sites', $name);
        }

        if ($this->siteRepository->findOneByNodeName($nodeName)) {
            $this->outputLine('Error: A site with siteNodeName "%s" already exists', [$nodeName]);
            $this->quit(1);
        }

        if ($this->packageManager->isPackageAvailable($packageKey) === false) {
            $this->ouputLine('Error: Could not find package "%s"', [$packageKey]);
            $this->quit(1);
        }

        $rootNode = $this->nodeContextFactory->create()->getRootNode();
        // We fetch the workspace to be sure it's known to the persistence manager and persist all
        // so the workspace and site node are persisted before we import any nodes to it.
        $rootNode->getContext()->getWorkspace();
        $sitesNode = $rootNode->getNode('/sites');
        if ($sitesNode === null) {
            $sitesNode = $rootNode->createNode('sites');
        }

        $siteNodeType = $this->nodeTypeManager->getNodeType($nodeType);
        if ($siteNodeType->isOfType('TYPO3.Neos:Document') === false) {
            $this->outputLine('Error: the given root nodeType "%s" is not based on the superType "%s"', [$nodeType, 'TYPO3.Neos:Document']);
            $this->quit(1);
        }

        $siteNode = $sitesNode->createNode($nodeName, $siteNodeType);
        $siteNode->setProperty('title', $name);

        $site = new Site($nodeName);
        $site->setSiteResourcesPackageKey($packageKey);
        $site->setState($active ? Site::STATE_ONLINE : Site::STATE_OFFLINE);
        $site->setName($name);

        $this->siteRepository->add($site);

        $this->outputLine('Successfully created site "%s" with siteNode "%s" and packageKey "%s"', [$name, $nodeName, $packageKey]);
    }

    /**
     * Import sites content
     *
     * This command allows for importing one or more sites or partial content from an XML source. The format must
     * be identical to that produced by the export command.
     *
     * If a filename is specified, this command expects the corresponding file to contain the XML structure. The
     * filename php://stdin can be used to read from standard input.
     *
     * If a package key is specified, this command expects a Sites.xml file to be located in the private resources
     * directory of the given package (Resources/Private/Content/Sites.xml).
     *
     * @param string $packageKey Package key specifying the package containing the sites content
     * @param string $filename relative path and filename to the XML file containing the sites content
     * @return void
     */
    public function importCommand($packageKey = null, $filename = null)
    {
        $exceedingArguments = $this->request->getExceedingArguments();
        if (isset($exceedingArguments[0]) && $packageKey === null && $filename === null) {
            if (file_exists($exceedingArguments[0])) {
                $filename = $exceedingArguments[0];
            } elseif ($this->packageManager->isPackageAvailable($exceedingArguments[0])) {
                $packageKey = $exceedingArguments[0];
            }
        }

        if ($packageKey === null && $filename === null) {
            $this->outputLine('You have to specify either "--package-key" or "--filename"');
            $this->quit(1);
        }
        $site = null;
        if ($filename !== null) {
            try {
                $site = $this->siteImportService->importFromFile($filename);
            } catch (\Exception $exception) {
                $this->systemLogger->logException($exception);
                $this->outputLine('Error: During the import of the file "%s" an exception occurred: %s, see log for further information.', array($filename, $exception->getMessage()));
                $this->quit(1);
            }
        } else {
            try {
                $site = $this->siteImportService->importFromPackage($packageKey);
            } catch (\Exception $exception) {
                $this->systemLogger->logException($exception);
                $this->outputLine('Error: During the import of the "Sites.xml" from the package "%s" an exception occurred: %s, see log for further information.', array($packageKey, $exception->getMessage()));
                $this->quit(1);
            }
        }
        $this->outputLine('Import of site "%s" finished.', array($site->getName()));
    }

    /**
     * Export sites content (e.g. site:export --package-key "Neos.Demo")
     *
     * This command exports all or one specific site with all its content into an XML format.
     *
     * If the package key option is given, the site(s) will be exported to the given package in the default
     * location Resources/Private/Content/Sites.xml.
     *
     * If the filename option is given, any resources will be exported to files in a folder named "Resources"
     * alongside the XML file.
     *
     * If neither the filename nor the package key option are given, the XML will be printed to standard output and
     * assets will be embedded into the XML in base64 encoded form.
     *
     * @param string $siteNode the node name of the site to be exported; if none given will export all sites
     * @param boolean $tidy Whether to export formatted XML. This is defaults to true
     * @param string $filename relative path and filename to the XML file to create. Any resource will be stored in a sub folder "Resources".
     * @param string $packageKey Package to store the XML file in. Any resource will be stored in a sub folder "Resources".
     * @param string $nodeTypeFilter Filter the node type of the nodes, allows complex expressions (e.g. "TYPO3.Neos:Page", "!TYPO3.Neos:Page,TYPO3.Neos:Text")
     * @return void
     */
    public function exportCommand($siteNode = null, $tidy = true, $filename = null, $packageKey = null, $nodeTypeFilter = null)
    {
        if ($siteNode === null) {
            $sites = $this->siteRepository->findAll()->toArray();
        } else {
            $sites = $this->siteRepository->findByNodeName($siteNode)->toArray();
        }

        if (count($sites) === 0) {
            $this->outputLine('Error: No site for exporting found');
            $this->quit(1);
        }

        if ($packageKey !== null) {
            $this->siteExportService->exportToPackage($sites, $tidy, $packageKey, $nodeTypeFilter);
            if ($siteNode !== null) {
                $this->outputLine('The site "%s" has been exported to package "%s".', array($siteNode, $packageKey));
            } else {
                $this->outputLine('All sites have been exported to package "%s".', array($packageKey));
            }
        } elseif ($filename !== null) {
            $this->siteExportService->exportToFile($sites, $tidy, $filename, $nodeTypeFilter);
            if ($siteNode !== null) {
                $this->outputLine('The site "%s" has been exported to "%s".', array($siteNode, $filename));
            } else {
                $this->outputLine('All sites have been exported to "%s".', array($filename));
            }
        } else {
            $this->output($this->siteExportService->export($sites, $tidy, $nodeTypeFilter));
        }
    }

    /**
     * Remove all content and related data - for now. In the future we need some more sophisticated cleanup.
     *
     * @param string $siteNodeName Name of a site root node to clear only content of this site.
     * @return void
     */
    public function pruneCommand($siteNodeName = null)
    {
        if ($siteNodeName !== null) {
            $possibleSite = $this->siteRepository->findOneByNodeName($siteNodeName);
            if ($possibleSite === null) {
                $this->outputLine('The given site site node did not match an existing site.');
                $this->quit(1);
            }
            $this->siteService->pruneSite($possibleSite);
            $this->outputLine('Site with root "' . $siteNodeName . '" has been removed.');
        } else {
            $this->siteService->pruneAll();
            $this->outputLine('All sites and content have been removed.');
        }
    }

    /**
     * Display a list of available sites
     *
     * @return void
     */
    public function listCommand()
    {
        $sites = $this->siteRepository->findAll();

        if ($sites->count() === 0) {
            $this->outputLine('No sites available');
            $this->quit(0);
        }

        $longestSiteName = 4;
        $longestNodeName = 9;
        $longestSiteResource = 17;
        $availableSites = array();

        foreach ($sites as $site) {
            /** @var Site $site */
            array_push($availableSites, array(
                'name' => $site->getName(),
                'nodeName' => $site->getNodeName(),
                'siteResourcesPackageKey' => $site->getSiteResourcesPackageKey()
            ));
            if (strlen($site->getName()) > $longestSiteName) {
                $longestSiteName = strlen($site->getName());
            }
            if (strlen($site->getNodeName()) > $longestNodeName) {
                $longestNodeName = strlen($site->getNodeName());
            }
            if (strlen($site->getSiteResourcesPackageKey()) > $longestSiteResource) {
                $longestSiteResource = strlen($site->getSiteResourcesPackageKey());
            }
        }

        $this->outputLine();
        $this->outputLine(' ' . str_pad('Name', $longestSiteName + 15) . str_pad('Node name', $longestNodeName + 15) . 'Resources package');
        $this->outputLine(str_repeat('-', $longestSiteName + $longestNodeName + $longestSiteResource + 15 + 15 + 2));
        foreach ($availableSites as $site) {
            $this->outputLine(' ' . str_pad($site['name'], $longestSiteName + 15) . str_pad($site['nodeName'], $longestNodeName + 15) . $site['siteResourcesPackageKey']);
        }
        $this->outputLine();
    }
}
