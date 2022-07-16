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
use Neos\Fusion\Core\FusionSourceCodeCollection;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Core\RuntimeFactory;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;

/**
 * @Flow\Scope("singleton")
 * @api
 */
class FusionService
{
    /**
     * Pattern used for determining the Fusion root file for a site
     */
    private const SITE_ROOT_FUSION_PATTERN = 'resource://%s/Private/Fusion/Root.fusion';

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

            $siteRootFusionPathAndFilename = sprintf(self::SITE_ROOT_FUSION_PATTERN, $siteResourcesPackageKey);

            return $this->fusionParser->parseFromSource(
                $this->fusionSourceCodeFactory->createFromNodeTypeDefinitions()
                    ->union(
                        $this->fusionSourceCodeFactory->createFromAutoIncludes()
                    )
                    ->union(
                        FusionSourceCodeCollection::tryFromFilePath($siteRootFusionPathAndFilename)
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

    private function findSiteBySiteNode(Node $siteNode): Site
    {
        return $this->siteRepository->findOneByNodeName($siteNode->nodeName)
            ?? throw new \Neos\Neos\Domain\Exception(sprintf('No site found for nodeNodeName "%s"', $siteNode->nodeName), 1677245517);
    }
}
