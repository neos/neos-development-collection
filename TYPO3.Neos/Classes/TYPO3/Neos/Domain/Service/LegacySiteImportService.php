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
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Resource\Resource as PersistentResource;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Flow\Utility\Files;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\Image;
use TYPO3\Media\Domain\Model\ImageVariant;
use TYPO3\Media\Domain\Repository\ImageRepository;
use TYPO3\Media\Domain\Repository\AssetRepository;
use TYPO3\Neos\Domain\Exception as DomainException;
use TYPO3\Neos\Domain\Exception;
use TYPO3\Neos\Domain\Model\Site;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Exception as NeosException;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Service\Context;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;
use TYPO3\TYPO3CR\Domain\Utility\NodePaths;

/**
 * The "legacy" Site Import Service which understands the "old" XML format used in Neos 1.0 and 1.1 which does not
 * allow for content dimensions.
 *
 * @Flow\Scope("singleton")
 * @deprecated since Neos 1.2. Will be removed with Neos 1.4
 */
class LegacySiteImportService
{
    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var ImageRepository
     */
    protected $imageRepository;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
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
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

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
     * Imports one or multiple sites from the XML file at $pathAndFilename
     *
     * @param string $pathAndFilename
     * @return Site The imported site or NULL if not successful
     * @throws UnknownPackageException
     * @throws InvalidPackageStateException
     */
    public function importSitesFromFile($pathAndFilename)
    {
        $contentContext = $this->createContext();
        $sitesXmlString = $this->loadSitesXml($pathAndFilename);

        if (defined('LIBXML_PARSEHUGE')) {
            $options = LIBXML_PARSEHUGE;
        } else {
            $options = 0;
        }
        $site = null;
        $sitesXml = new \SimpleXMLElement($sitesXmlString, $options);
        foreach ($sitesXml->site as $siteXml) {
            $site = $this->getSiteByNodeName((string)$siteXml['nodeName']);
            if ((string)$siteXml->properties->name !== '') {
                $site->setName((string)$siteXml->properties->name);
            }
            $site->setState((integer)$siteXml->properties->state);

            $siteResourcesPackageKey = (string)$siteXml->properties->siteResourcesPackageKey;
            if (!$this->packageManager->isPackageAvailable($siteResourcesPackageKey)) {
                throw new UnknownPackageException(sprintf('Package "%s" specified in the XML as site resources package does not exist.', $siteResourcesPackageKey), 1303891443);
            }
            if (!$this->packageManager->isPackageActive($siteResourcesPackageKey)) {
                throw new InvalidPackageStateException(sprintf('Package "%s" specified in the XML as site resources package is not active.', $siteResourcesPackageKey), 1303898135);
            }
            $site->setSiteResourcesPackageKey($siteResourcesPackageKey);

            $rootNode = $contentContext->getRootNode();

            $sitesNode = $rootNode->getNode(SiteService::SITES_ROOT_PATH);
            if ($sitesNode === null) {
                $sitesNode = $rootNode->createSingleNode(NodePaths::getNodeNameFromPath(SiteService::SITES_ROOT_PATH));
            }
            // We fetch the workspace to be sure it's known to the persistence manager and persist all
            // so the workspace and site node are persisted before we import any nodes to it.
            $rootNode->getContext()->getWorkspace();
            $this->persistenceManager->persistAll();

            if ($siteXml['type'] === null) {
                $this->upgradeLegacySiteXml($siteXml, $site);
            }

            $this->importNode($siteXml, $sitesNode);
        }
        return $site;
    }

    /**
     * @return Context
     */
    protected function createContext()
    {
        $workspace = $this->workspaceRepository->findOneByName('live');
        if ($workspace === null) {
            $this->workspaceRepository->add(new Workspace('live'));
        }
        $this->persistenceManager->persistAll();

        return $this->contextFactory->create(array(
            'workspaceName' => 'live',
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true
        ));
    }

    /**
     * Loads and returns the XML found at $pathAndFilename
     *
     * @param string $pathAndFilename
     * @return string
     * @throws NeosException
     */
    protected function loadSitesXml($pathAndFilename)
    {
        if ($pathAndFilename === 'php://stdin') {
            // no file_get_contents here because it does not work on php://stdin
            $fp = fopen($pathAndFilename, 'rb');
            $xmlString = '';
            while (!feof($fp)) {
                $xmlString .= fread($fp, 4096);
            }
            fclose($fp);

            return $xmlString;
        }
        if (!file_exists($pathAndFilename)) {
            throw new NeosException(sprintf('Could not load Content from "%s". This file does not exist.', $pathAndFilename), 1384193282);
        }
        $this->resourcesPath = Files::concatenatePaths(array(dirname($pathAndFilename), 'Resources'));

        return file_get_contents($pathAndFilename);
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

            return $site;
        }
        $this->siteRepository->update($site);

        return $site;
    }

    /**
     * Converts the given $nodeXml to a node and adds it to the $parentNode (or overrides an existing node)
     *
     * @param \SimpleXMLElement $nodeXml
     * @param NodeInterface $parentNode
     * @return void
     */
    protected function importNode(\SimpleXMLElement $nodeXml, NodeInterface $parentNode)
    {
        $nodeName = (string)$nodeXml['nodeName'];
        $nodeType = $this->parseNodeType($nodeXml);
        $node = $parentNode->getNode($nodeName);

        if ($node === null) {
            $identifier = (string)$nodeXml['identifier'] === '' ? null : (string)$nodeXml['identifier'];
            $node = $parentNode->createSingleNode((string)$nodeXml['nodeName'], $nodeType, $identifier);
        } else {
            $node->setNodeType($nodeType);
        }

        $this->importNodeVisibility($nodeXml, $node);
        $this->importNodeProperties($nodeXml, $node);
        $this->importNodeAccessRoles($nodeXml, $node);

        if ($nodeXml->node) {
            foreach ($nodeXml->node as $childNodeXml) {
                $this->importNode($childNodeXml, $node);
            }
        }
    }

    /**
     * Detects and retrieves the NodeType of the given $nodeXml
     *
     * @param \SimpleXMLElement $nodeXml
     * @return NodeType
     * @throws Exception
     */
    protected function parseNodeType(\SimpleXMLElement $nodeXml)
    {
        $nodeTypeName = (string)$nodeXml['type'];
        if ($this->nodeTypeManager->hasNodeType($nodeTypeName)) {
            $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
            if ($nodeType->isAbstract()) {
                throw new DomainException(sprintf('The node type "%s" is marked as abstract and cannot be assigned to nodes.', $nodeTypeName), 1386590052);
            }
            return $nodeType;
        }

        return $this->nodeTypeManager->createNodeType($nodeTypeName);
    }

    /**
     * Sets visibility of the $node according to the $nodeXml
     *
     * @param \SimpleXMLElement $nodeXml
     * @param NodeInterface $node
     * @return void
     */
    protected function importNodeVisibility(\SimpleXMLElement $nodeXml, NodeInterface $node)
    {
        $node->setHidden((boolean)$nodeXml['hidden']);
        $node->setHiddenInIndex((boolean)$nodeXml['hiddenInIndex']);
        if ((string)$nodeXml['hiddenBeforeDateTime'] !== '') {
            $node->setHiddenBeforeDateTime(\DateTime::createFromFormat(\DateTime::W3C, (string)$nodeXml['hiddenBeforeDateTime']));
        }
        if ((string)$nodeXml['hiddenAfterDateTime'] !== '') {
            $node->setHiddenAfterDateTime(\DateTime::createFromFormat(\DateTime::W3C, (string)$nodeXml['hiddenAfterDateTime']));
        }
    }

    /**
     * Iterates over properties child nodes of the given $nodeXml (if any)  and sets them on the $node instance
     *
     * @param \SimpleXMLElement $nodeXml
     * @param NodeInterface $node
     * @return void
     */
    protected function importNodeProperties(\SimpleXMLElement $nodeXml, NodeInterface $node)
    {
        if (!$nodeXml->properties) {
            return;
        }
        foreach ($nodeXml->properties->children() as $nodePropertyXml) {
            $this->importNodeProperty($nodePropertyXml, $node);
        }
    }

    /**
     * Sets the given $nodePropertyXml on the $node instance
     *
     * @param \SimpleXMLElement $nodePropertyXml
     * @param NodeInterface $node
     * @return void
     */
    protected function importNodeProperty(\SimpleXMLElement $nodePropertyXml, NodeInterface $node)
    {
        if (!isset($nodePropertyXml['__type'])) {
            $node->setProperty($nodePropertyXml->getName(), (string)$nodePropertyXml);

            return;
        }
        switch ($nodePropertyXml['__type']) {
            case 'boolean':
                $node->setProperty($nodePropertyXml->getName(), (string)$nodePropertyXml === '1');
                break;
            case 'reference':
                $targetIdentifier = ((string)$nodePropertyXml->node['identifier'] === '' ? null : (string)$nodePropertyXml->node['identifier']);
                $node->setProperty($nodePropertyXml->getName(), $targetIdentifier);
                break;
            case 'references':
                $referencedNodeIdentifiers = array();
                foreach ($nodePropertyXml->node as $referenceNodeXml) {
                    if ((string)$referenceNodeXml['identifier'] !== '') {
                        $referencedNodeIdentifiers[] = (string)$referenceNodeXml['identifier'];
                    }
                }
                $node->setProperty($nodePropertyXml->getName(), $referencedNodeIdentifiers);
                break;
            case 'array':
                $entries = array();
                foreach ($nodePropertyXml->children() as $childNodeXml) {
                    $entry = null;

                    if (!isset($childNodeXml['__classname']) || !in_array($childNodeXml['__classname'], array(Image::class, Asset::class))) {
                        // Only arrays of asset objects are supported now
                        continue;
                    }

                    $entryClassName = (string)$childNodeXml['__classname'];
                    if (isset($childNodeXml['__identifier'])) {
                        if ($entryClassName === Image::class) {
                            $entry = $this->imageRepository->findByIdentifier((string)$childNodeXml['__identifier']);
                        } else {
                            $entry = $this->assetRepository->findByIdentifier((string)$childNodeXml['__identifier']);
                        }
                    }

                    if ($entry === null) {
                        $resourceXml = $childNodeXml->xpath('resource');
                        $resourceHash = $resourceXml[0]->xpath('hash');
                        $content = $resourceXml[0]->xpath('content');
                        $filename = $resourceXml[0]->xpath('filename');

                        $resource = $this->importResource(
                            !empty($filename) ? (string)$filename[0] : null,
                            !empty($resourceHash) ? (string)$resourceHash[0] : null,
                            !empty($content) ? (string)$content[0] : null,
                            isset($resourceXml[0]['__identifier']) ? (string)$resourceXml[0]['__identifier'] : null
                        );

                        $entry = new $entryClassName($resource);

                        if (isset($childNodeXml['__identifier'])) {
                            ObjectAccess::setProperty($entry, 'Persistence_Object_Identifier', (string)$childNodeXml['__identifier'], true);
                        }
                    }

                    $propertiesXml = $childNodeXml->xpath('properties/*');
                    foreach ($propertiesXml as $propertyXml) {
                        if (!isset($propertyXml['__type'])) {
                            ObjectAccess::setProperty($entry, $propertyXml->getName(), (string)$propertyXml);
                            continue;
                        }

                        switch ($propertyXml['__type']) {
                            case 'boolean':
                                ObjectAccess::setProperty($entry, $propertyXml->getName(), (boolean)$propertyXml);
                                break;
                            case 'string':
                                ObjectAccess::setProperty($entry, $propertyXml->getName(), (string)$propertyXml);
                                break;
                            case 'object':
                                ObjectAccess::setProperty($entry, $propertyXml->getName(), $this->xmlToObject($propertyXml));
                        }
                    }

                    /**
                     * During the persist Doctrine calls the serialize() method on the asset for the ObjectArray
                     * object, during this serialize the resource property gets lost.
                     * The AssetList node type has a custom implementation to work around this bug.
                     * @see NEOS-121
                     */
                    $repositoryAction = $this->persistenceManager->isNewObject($entry) ? 'add' : 'update';
                    if ($entry instanceof Image) {
                        $this->imageRepository->$repositoryAction($entry);
                    } else {
                        $this->assetRepository->$repositoryAction($entry);
                    }

                    $entries[] = $entry;
                }

                $node->setProperty($nodePropertyXml->getName(), $entries);
                break;
            case 'object':
                $node->setProperty($nodePropertyXml->getName(), $this->xmlToObject($nodePropertyXml));
                break;
        }
    }

    /**
     * Imports a resource based on exported hash or content
     *
     * @param string $fileName
     * @param string|null $hash
     * @param string|null $content
     * @param string $forcedIdentifier
     * @return PersistentResource
     * @throws NeosException
     */
    protected function importResource($fileName, $hash = null, $content = null, $forcedIdentifier = null)
    {
        if ($hash !== null) {
            $resource = $this->resourceManager->createResourceFromContent(
                file_get_contents(Files::concatenatePaths(array($this->resourcesPath, $hash))),
                $fileName
            );
        } else {
            $resourceData = trim($content);
            if ($resourceData === '') {
                throw new NeosException('Could not import resource because neither "hash" nor "content" tags are present.', 1403009453);
            }
            $decodedResourceData = base64_decode($resourceData);
            if ($decodedResourceData === false) {
                throw new NeosException('Could not import resource because the "content" tag doesn\'t contain valid base64 encoded data.', 1403009477);
            }

            $resource = $this->resourceManager->createResourceFromContent(
                $decodedResourceData,
                $fileName
            );
        }

        if ($forcedIdentifier !== null) {
            ObjectAccess::setProperty($resource, 'Persistence_Object_Identifier', $forcedIdentifier, true);
        }

        return $resource;
    }

    /**
     * Sets the $nodes access roles
     *
     * @param \SimpleXMLElement $nodeXml
     * @param NodeInterface $node
     * @return void
     */
    protected function importNodeAccessRoles(\SimpleXMLElement $nodeXml, NodeInterface $node)
    {
        if (!$nodeXml->accessRoles) {
            return;
        }
        $accessRoles = array();
        foreach ($nodeXml->accessRoles->children() as $accessRoleXml) {
            $accessRoles[] = (string)$accessRoleXml;
        }
        $node->setAccessRoles($accessRoles);
    }

    /**
     * Handles conversion of our XML format into objects.
     *
     * @param \SimpleXMLElement $objectXml
     * @return object
     * @throws DomainException
     */
    protected function xmlToObject(\SimpleXMLElement $objectXml)
    {
        $object = null;
        $className = (string)$objectXml['__classname'];
        if (in_array($className, $this->imageVariantClassNames)) {
            return $this->importImageVariant($objectXml, $className);
        }

        if (in_array($className, $this->assetClassNames)) {
            return $this->importAsset($objectXml, $className);
        }

        if (in_array($className, $this->dateTimeClassNames)) {
            return call_user_func_array($className . '::createFromFormat', array(\DateTime::W3C, (string)$objectXml->dateTime));
        }

        throw new DomainException(sprintf('Unsupported object of target type "%s" hit during XML import.', $className), 1347144938);
    }

    /**
     * Converts the given $objectXml to an ImageVariant instance and returns it
     *
     * @param \SimpleXMLElement $objectXml
     * @param string $className the concrete class name of the ImageVariant to create (ImageVariant or a subclass)
     * @return ImageVariant
     * @throws NeosException
     */
    protected function importImageVariant(\SimpleXMLElement $objectXml, $className)
    {
        $processingInstructions = unserialize(trim((string)$objectXml->processingInstructions));

        if (isset($objectXml->originalImage['__identifier'])) {
            $image = $this->imageRepository->findByIdentifier((string)$objectXml->originalImage['__identifier']);
            if (is_object($image)) {
                return $this->objectManager->get($className, $image, $processingInstructions);
            }
        }

        $resourceHash = (string)$objectXml->originalImage->resource->hash;
        $resourceData = trim((string)$objectXml->originalImage->resource->content);

        if ((string)$objectXml->originalImage->resource['__identifier'] !== '') {
            $resource = $this->persistenceManager->getObjectByIdentifier((string)$objectXml->originalImage->resource['__identifier'], 'TYPO3\Flow\Resource\Resource');
        }

        if (!isset($resource) || $resource === null) {
            $resource = $this->importResource(
                (string)$objectXml->originalImage->resource->filename,
                $resourceHash !== '' ? $resourceHash : null,
                !empty($resourceData) ? $resourceData : null,
                (string)$objectXml->originalImage->resource['__identifier'] !== '' ? (string)$objectXml->originalImage->resource['__identifier'] : null
            );
        }

        $image = new Image($resource);
        if ((string)$objectXml->originalImage['__identifier'] !== '') {
            ObjectAccess::setProperty($image, 'Persistence_Object_Identifier', (string)$objectXml->originalImage['__identifier'], true);
        }
        $this->imageRepository->add($image);

        return $this->objectManager->get($className, $image, $processingInstructions);
    }

    /**
     * Converts the given $objectXml to an AssetInterface instance and returns it
     *
     * @param \SimpleXMLElement $objectXml
     * @param string $className the concrete class name of the AssetInterface to create
     * @return AssetInterface
     * @throws NeosException
     */
    protected function importAsset(\SimpleXMLElement $objectXml, $className)
    {
        if (isset($objectXml['__identifier'])) {
            $asset = $this->assetRepository->findByIdentifier((string)$objectXml['__identifier']);
            if (is_object($asset)) {
                return $asset;
            }
        }

        $resourceHash = (string)$objectXml->resource->hash;
        $resourceData = trim((string)$objectXml->resource->content);

        if ((string)$objectXml->resource['__identifier'] !== '') {
            $resource = $this->persistenceManager->getObjectByIdentifier((string)$objectXml->resource['__identifier'], 'TYPO3\Flow\Resource\Resource');
        }

        if (!isset($resource) || $resource === null) {
            $resource = $this->importResource(
                (string)$objectXml->resource->filename,
                $resourceHash !== '' ? $resourceHash : null,
                !empty($resourceData) ? $resourceData : null,
                (string)$objectXml->resource['__identifier'] !== '' ? (string)$objectXml->resource['__identifier'] : null
            );
        }

        $asset = $this->objectManager->get($className);
        $asset->setResource($resource);
        $this->assetRepository->add($asset);

        return $asset;
    }

    /**
     * If the imported site is of a legacy schema where the near-root <site> element wasn't
     * an actual node, the respective site xml is "upgraded" to become of type Shortcut,
     * get a `title` property being the site's name, and being set to hidden in index.
     *
     * @param \SimpleXMLElement $siteXml
     * @param Site $site
     * @return void
     */
    protected function upgradeLegacySiteXml(\SimpleXMLElement $siteXml, Site $site)
    {
        $siteXml->addAttribute('type', 'TYPO3.Neos:Shortcut');
        $siteXml->addAttribute('hiddenInIndex', 'true');

        if (property_exists($siteXml->properties, 'title') === false) {
            $siteXml->properties->addChild('title', $site->getName());
        }
    }
}
