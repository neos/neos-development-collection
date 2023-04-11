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
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Fusion\Core\FusionConfiguration;
use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\FusionSourceCode;
use Neos\Fusion\Core\FusionSourceCodeCollection;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Core\RuntimeFactory;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Model\SiteNodeName;
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
     * @var array<mixed>
     */
    protected $prependFusionIncludes = [];

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
     * @deprecated with Neos 8.3, will be removed with 9.0
     * @var array<mixed>
     */
    protected $appendFusionIncludes = [];

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var Parser
     */
    protected $fusionParser;

    /**
     * @Flow\Inject
     * @var RuntimeFactory
     */
    protected $runtimeFactory;

    /**
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
    {
        return $this->fusionConfigurationCache->cacheFusionConfigurationBySite($site, function () use ($site) {
            $siteResourcesPackageKey = $site->getSiteResourcesPackageKey();

            $siteRootFusionPathAndFilename = sprintf($this->siteRootFusionPattern, $siteResourcesPackageKey);

            return $this->fusionParser->parseFromSource(
                $this->fusionSourceCodeFactory->createFromNodeTypeDefinitions($site->getConfiguration()->contentRepositoryId)
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
     * @deprecated with Neos 8.3, will be removed with 9.0 {@link createFusionConfigurationFromSite}
     *
     * @param Node $startNode Node marking the starting point (i.e. the "Site" node)
     * @return array<mixed> The merged object tree as of the given node
     */
    public function getMergedFusionObjectTree(Node $startNode)
    {
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
        Node $currentSiteNode,
        ControllerContext $controllerContext
    ) {
        return $this->runtimeFactory->createFromConfiguration(
            $this->createFusionConfigurationFromSite($this->findSiteBySiteNode($currentSiteNode)),
            $controllerContext
        );
    }

    /**
     * Set the pattern for including the site root Fusion
     *
     * @deprecated with Neos 8.3, will be removed with 9.0
     * use {@link FusionSourceCodeFactory} in combination with {@link RuntimeFactory::createRuntimeFromSourceCode()} instead
     *
     * @param string $siteRootFusionPattern A string for the sprintf format that takes the site package key as a single placeholder
     * @return void
     */
    public function setSiteRootFusionPattern($siteRootFusionPattern)
    {
        $this->siteRootFusionPattern = $siteRootFusionPattern;
    }

    /**
     * Get the Fusion resources that are included before the site Fusion.
     *
     * @deprecated with Neos 8.3, will be removed with 9.0
     * use {@link FusionSourceCodeFactory} in combination with {@link RuntimeFactory::createRuntimeFromSourceCode()} instead
     *
     * @return array<mixed>
     */
    public function getPrependFusionIncludes()
    {
        return $this->prependFusionIncludes;
    }

    /**
     * Set Fusion resources that should be prepended before the site Fusion,
     * it defaults to the Neos Root.fusion Fusion.
     *
     * @deprecated with Neos 8.3, will be removed with 9.0
     * use {@link FusionSourceCodeFactory} in combination with {@link RuntimeFactory::createRuntimeFromSourceCode()} instead
     *
     * @param array<mixed> $prependFusionIncludes
     * @return void
     */
    public function setPrependFusionIncludes(array $prependFusionIncludes)
    {
        $this->prependFusionIncludes = $prependFusionIncludes;
    }


    /**
     * Get Fusion resources that will be appended after the site Fusion.
     *
     * @deprecated with Neos 8.3, will be removed with 9.0
     * use {@link FusionSourceCodeFactory} in combination with {@link RuntimeFactory::createRuntimeFromSourceCode()} instead
     *
     * @return array<mixed>
     */
    public function getAppendFusionIncludes()
    {
        return $this->appendFusionIncludes;
    }

    /**
     * Set Fusion resources that should be appended after the site Fusion,
     * this defaults to an empty array.
     *
     * @deprecated with Neos 8.3, will be removed with 9.0
     * use {@link FusionSourceCodeFactory} in combination with {@link RuntimeFactory::createRuntimeFromSourceCode()} instead
     *
     * @param array<mixed> $appendFusionIncludes An array of Fusion resource URIs
     * @return void
     */
    public function setAppendFusionIncludes(array $appendFusionIncludes)
    {
        $this->appendFusionIncludes = $appendFusionIncludes;
    }

    /**
     * @param array<mixed> $fusionIncludes
     * @deprecated with Neos 8.3, will be removed with 9.0
     */
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

    private function findSiteBySiteNode(Node $siteNode): Site
    {
        return $this->siteRepository->findOneByNodeName(SiteNodeName::fromNodeName($siteNode->nodeName))
            ?? throw new \Neos\Neos\Domain\Exception(sprintf('No site found for nodeNodeName "%s"', $siteNode->nodeName), 1677245517);
    }
}
