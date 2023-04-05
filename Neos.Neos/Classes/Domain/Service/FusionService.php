<?php
declare(strict_types=1);

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

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Utility\Files;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Core\RuntimeFactory;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;

/**
 * @todo currently scope prototype will change with the removal of the internal state to singleton in Neos 9.0
 *
 * @Flow\Scope("prototype")
 * @api
 */
class FusionService
{
    /**
     * Pattern used for determining the Fusion root file for a site
     *
     * @deprecated with Neos 8.3, will be immutable with 9.0
     * @var string
     */
    protected $siteRootFusionPattern = 'resource://%s/Private/Fusion/Root.fusion';

    /**
     * Array of Fusion files to include before the site Fusion
     *
     * Example:
     *
     *     array(
     *         'resources://MyVendor.MyPackageKey/Private/Fusion/Root.fusion',
     *         'resources://SomeVendor.OtherPackage/Private/Fusion/Root.fusion'
     *     )
     *
     * @deprecated with Neos 8.3, will be removed with 9.0
     * @var array<int,string>
     */
    protected array $prependFusionIncludes = [];

    /**
     * Array of Fusion files to include after the site Fusion
     *
     * Example:
     *
     *     array(
     *         'resources://MyVendor.MyPackageKey/Private/Fusion/Root.fusion',
     *         'resources://SomeVendor.OtherPackage/Private/Fusion/Root.fusion'
     *     )
     *
<<<<<<< HEAD
     * @var array<int,string>
=======
     * @deprecated with Neos 8.3, will be removed with 9.0
     * @var array
>>>>>>> 8.3
     */
    protected array $appendFusionIncludes = [];

    /**
<<<<<<< HEAD
     * Declaration of package inclusions as packageKey:included, e.g. "Acme.Site": true
     * @Flow\InjectConfiguration("fusion.autoInclude")
     * @var array
     * @phpstan-var array<string,bool>
=======
     * @Flow\Inject
     * @var SiteRepository
>>>>>>> 8.3
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
<<<<<<< HEAD
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;
=======
     * @var Parser
     */
    protected $fusionParser;
>>>>>>> 8.3

    /**
     * @Flow\Inject
     * @var RuntimeFactory
     */
    protected $runtimeFactory;

    /**
<<<<<<< HEAD
     * Create a runtime for the given site node
     *
     * @return Runtime
     * @throws \Neos\Fusion\Exception
     * @throws \Neos\Neos\Domain\Exception
     */
    public function createRuntime(Node $currentSiteNode, ControllerContext $controllerContext)
=======
     * @Flow\Inject
     * @var FusionSourceCodeFactory
     */
    protected $fusionSourceCodeFactory;

    /**
     * @Flow\Inject
     * @var FusionConfigurationCache
     */
    protected $fusionConfigurationCache;

    public function createFusionConfigurationFromSite(Site $site): FusionConfiguration
>>>>>>> 8.3
    {
        return $this->fusionConfigurationCache->cacheFusionConfigurationBySite($site, function () use ($site) {
            $siteResourcesPackageKey = $site->getSiteResourcesPackageKey();

            $siteRootFusionPathAndFilename = sprintf($this->siteRootFusionPattern, $siteResourcesPackageKey);

            return $this->fusionParser->parseFromSource(
                $this->fusionSourceCodeFactory->createFromNodeTypeDefinitions()
                    ->union(
                        $this->fusionSourceCodeFactory->createFromAutoIncludes()
                    )
                    ->union(
                        $this->createSourceCodeFromLegacyFusionIncludes($this->prependFusionIncludes, $siteRootFusionPathAndFilename)
                    )
                    ->union(
                        FusionSourceCodeCollection::tryFromFilePath($siteRootFusionPathAndFilename)
                    )
                    ->union(
                        $this->createSourceCodeFromLegacyFusionIncludes($this->appendFusionIncludes, $siteRootFusionPathAndFilename)
                    )
            );
        });
    }

    /**
     * Returns a merged Fusion object tree in the context of the given nodes
     *
<<<<<<< HEAD
     * @param Node $startNode Node marking the starting point (i.e. the "Site" node)
     * @return array<mixed> The merged object tree as of the given node
     * @throws \Neos\Neos\Domain\Exception
     * @throws \Neos\Fusion\Exception
=======
     * @deprecated with Neos 8.3, will be removed with 9.0 {@link createFusionConfigurationFromSite}
     *
     * @param TraversableNodeInterface $startNode Node marking the starting point (i.e. the "Site" node)
     * @return array The merged object tree as of the given node
>>>>>>> 8.3
     */
    public function getMergedFusionObjectTree(Node $startNode)
    {
<<<<<<< HEAD
        $site = $this->getSiteForSiteNode($startNode);
        if (is_null($site)) {
            throw new \InvalidArgumentException(
                'Could not resolve site for node "' . $startNode->getLabel() . '"',
                1651924023
            );
        }
        $siteResourcesPackageKey = $site->getSiteResourcesPackageKey();

        $siteRootFusionPathAndFilename = sprintf($this->siteRootFusionPattern, $siteResourcesPackageKey);
        $siteRootFusionCode = $this->readExternalFusionFile($siteRootFusionPathAndFilename);

        $mergedFusionCode = '';
        $mergedFusionCode .= $this->generateNodeTypeDefinitions(
            $startNode->subgraphIdentity->contentRepositoryId
        );
        $mergedFusionCode .= $this->getFusionIncludes($this->prepareAutoIncludeFusion());
        $mergedFusionCode .= $this->getFusionIncludes($this->prependFusionIncludes);
        $mergedFusionCode .= $siteRootFusionCode;
        $mergedFusionCode .= $this->getFusionIncludes($this->appendFusionIncludes);

        return $this->fusionParser->parse($mergedFusionCode, $siteRootFusionPathAndFilename);
    }

    protected function getSiteForSiteNode(Node $siteNode): ?Site
    {
        return $this->siteRepository->findOneByNodeName((string)$siteNode->nodeName);
    }

    /**
     * Reads the Fusion file from the given path and filename.
     * If it doesn't exist, this function will just return an empty string.
     *
     * @param string $pathAndFilename Path and filename of the Fusion file
     * @return string The content of the .fusion file, plus one chr(10) at the end
     */
    protected function readExternalFusionFile($pathAndFilename)
    {
        return (is_file($pathAndFilename)) ? Files::getFileContents($pathAndFilename) . chr(10) : '';
    }

    /**
     * Generate Fusion prototype definitions for all node types
     *
     * Only fully qualified node types (e.g. MyVendor.MyPackage:NodeType) will be considered.
     *
     * @return string
     * @throws \Neos\Neos\Domain\Exception
     */
    protected function generateNodeTypeDefinitions(ContentRepositoryId $contentRepositoryId)
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $code = '';
        /** @var NodeType $nodeType */
        foreach ($contentRepository->getNodeTypeManager()->getNodeTypes(false) as $nodeType) {
            $code .= $this->generateFusionForNodeType($nodeType);
        }
        return $code;
    }

    /**
     * Generate a Fusion prototype definition for a given node type
     *
     * A prototype will be rendererd with the generator-class defined in the
     * nodeType-configuration 'fusion.prototypeGenerator'
     *
     * @param NodeType $nodeType
     * @return string
     * @throws \Neos\Neos\Domain\Exception
     */
    protected function generateFusionForNodeType(NodeType $nodeType)
    {
        if (
            $nodeType->hasConfiguration('options.fusion.prototypeGenerator')
            && $nodeType->getConfiguration('options.fusion.prototypeGenerator') !== null
        ) {
            $generatorClassName = $nodeType->getConfiguration('options.fusion.prototypeGenerator');
            if (!class_exists($generatorClassName)) {
                throw new \Neos\Neos\Domain\Exception(
                    'Fusion prototype-generator Class ' . $generatorClassName . ' does not exist'
                );
            }
            $generator = $this->objectManager->get($generatorClassName);
            if (!$generator instanceof DefaultPrototypeGeneratorInterface) {
                throw new \Neos\Neos\Domain\Exception(
                    'Fusion prototype-generator Class ' . $generatorClassName . ' does not implement interface '
                        . DefaultPrototypeGeneratorInterface::class
                );
            }
            return $generator->generate($nodeType);
        }
        return '';
    }

    /**
     * Concatenate the given Fusion resources with include statements
     *
     * @param array<int,string> $fusionResources An array of Fusion resource URIs
     * @return string A string of include statements for all resources
     */
    protected function getFusionIncludes(array $fusionResources)
    {
        $code = chr(10);
        foreach ($fusionResources as $fusionResource) {
            $code .= 'include: ' . $fusionResource . chr(10);
        }
        $code .= chr(10);
        return $code;
    }

    /**
     * Prepares an array with Fusion paths to auto include before the Site Fusion.
     *
     * @return array<int,string>
     */
    protected function prepareAutoIncludeFusion()
    {
        $autoIncludeFusion = [];
        foreach (array_keys($this->packageManager->getAvailablePackages()) as $packageKey) {
            if (
                isset($this->autoIncludeConfiguration[$packageKey])
                && $this->autoIncludeConfiguration[$packageKey] === true
            ) {
                $autoIncludeFusionFile = sprintf($this->autoIncludeFusionPattern, $packageKey);
                if (is_file($autoIncludeFusionFile)) {
                    $autoIncludeFusion[] = $autoIncludeFusionFile;
                }
            }
        }

        return $autoIncludeFusion;
=======
        return $this->createFusionConfigurationFromSite($this->findSiteBySiteNode($startNode))->toArray();
    }

    /**
     * Create a runtime for the given site node
     *
     * @deprecated with Neos 8.3, will be removed with 9.0 use {@link createFusionConfigurationFromSite} and {@link RuntimeFactory::createFromConfiguration} instead
     *
     * @return Runtime
     */
    public function createRuntime(
        TraversableNodeInterface $currentSiteNode,
        ControllerContext $controllerContext
    ) {
        return $this->runtimeFactory->createFromConfiguration(
            $this->createFusionConfigurationFromSite($this->findSiteBySiteNode($currentSiteNode)),
            $controllerContext
        );
>>>>>>> 8.3
    }

    /**
     * Set the pattern for including the site root Fusion
     *
<<<<<<< HEAD
     * @param string $siteRootFusionPattern A string for the sprintf format
     *                                      that takes the site package key as a single placeholder
=======
     * @deprecated with Neos 8.3, will be removed with 9.0
     * use {@link FusionSourceCodeFactory} in combination with {@link RuntimeFactory::createRuntimeFromSourceCode()} instead
     *
     * @param string $siteRootFusionPattern A string for the sprintf format that takes the site package key as a single placeholder
>>>>>>> 8.3
     * @return void
     */
    public function setSiteRootFusionPattern($siteRootFusionPattern)
    {
        $this->siteRootFusionPattern = $siteRootFusionPattern;
    }

    /**
     * Get the Fusion resources that are included before the site Fusion.
     *
<<<<<<< HEAD
     * @return array<int,string>
=======
     * @deprecated with Neos 8.3, will be removed with 9.0
     * use {@link FusionSourceCodeFactory} in combination with {@link RuntimeFactory::createRuntimeFromSourceCode()} instead
     *
     * @return array
>>>>>>> 8.3
     */
    public function getPrependFusionIncludes(): array
    {
        return $this->prependFusionIncludes;
    }

    /**
     * Set Fusion resources that should be prepended before the site Fusion,
     * it defaults to the Neos Root.fusion Fusion.
     *
<<<<<<< HEAD
     * @param array<int,string> $prependFusionIncludes
=======
     * @deprecated with Neos 8.3, will be removed with 9.0
     * use {@link FusionSourceCodeFactory} in combination with {@link RuntimeFactory::createRuntimeFromSourceCode()} instead
     *
     * @param array $prependFusionIncludes
>>>>>>> 8.3
     * @return void
     */
    public function setPrependFusionIncludes(array $prependFusionIncludes)
    {
        $this->prependFusionIncludes = $prependFusionIncludes;
    }


    /**
     * Get Fusion resources that will be appended after the site Fusion.
     *
<<<<<<< HEAD
     * @return array<int,string>
=======
     * @deprecated with Neos 8.3, will be removed with 9.0
     * use {@link FusionSourceCodeFactory} in combination with {@link RuntimeFactory::createRuntimeFromSourceCode()} instead
     *
     * @return array
>>>>>>> 8.3
     */
    public function getAppendFusionIncludes(): array
    {
        return $this->appendFusionIncludes;
    }

    /**
     * Set Fusion resources that should be appended after the site Fusion,
     * this defaults to an empty array.
     *
<<<<<<< HEAD
     * @param array<int,string> $appendFusionIncludes An array of Fusion resource URIs
=======
     * @deprecated with Neos 8.3, will be removed with 9.0
     * use {@link FusionSourceCodeFactory} in combination with {@link RuntimeFactory::createRuntimeFromSourceCode()} instead
     *
     * @param array $appendFusionIncludes An array of Fusion resource URIs
>>>>>>> 8.3
     * @return void
     */
    public function setAppendFusionIncludes(array $appendFusionIncludes)
    {
        $this->appendFusionIncludes = $appendFusionIncludes;
    }

    /** @deprecated with Neos 8.3, will be removed with 9.0 */
    private function createSourceCodeFromLegacyFusionIncludes(array $fusionIncludes, string $filePathForRelativeResolves): FusionSourceCodeCollection
    {
        return new FusionSourceCodeCollection(...array_map(
            function (string $fusionFile) use ($filePathForRelativeResolves) {
                if (str_starts_with($fusionFile, "resource://") === false) {
                    // legacy relative includes
                    $fusionFile = dirname($filePathForRelativeResolves) . '/' . $fusionFile;
                }
                return FusionSourceCode::fromFilePath($fusionFile);
            },
            $fusionIncludes
        ));
    }

    private function findSiteBySiteNode(TraversableNodeInterface $siteNode): Site
    {
        return $this->siteRepository->findOneByNodeName((string)$siteNode->getNodeName())
            ?? throw new \Neos\Neos\Domain\Exception(sprintf('No site found for nodeNodeName "%s"', $siteNode->getNodeName()), 1677245517);
    }
}
