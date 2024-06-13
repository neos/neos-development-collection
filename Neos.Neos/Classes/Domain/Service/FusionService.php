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

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\FusionConfiguration;
use Neos\Fusion\Core\FusionSourceCodeCollection;
use Neos\Fusion\Core\Parser;
use Neos\Neos\Domain\Model\Site;

/**
 * @internal For interacting with Fusion from the outside a FusionView should be used.
 */
#[Flow\Scope('singleton')]
class FusionService
{
    /**
     * @Flow\Inject
     * @var Parser
     */
    protected $fusionParser;

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

            return $this->fusionParser->parseFromSource(
                $this->fusionSourceCodeFactory->createFromNodeTypeDefinitions($site->getConfiguration()->contentRepositoryId)
                    ->union(
                        $this->fusionSourceCodeFactory->createFromAutoIncludes()
                    )
                    ->union(
                        FusionSourceCodeCollection::tryFromPackageRootFusion($siteResourcesPackageKey)
                    )
            );
        });
    }

    // @todo reintroduce with edit preview mode support
    // /**
    //  * Create a runtime for the given site
    //  */
    // public function createRuntime(Site $site): Runtime;
}
