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
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Fusion\Core\FusionConfiguration;
use Neos\Neos\Domain\Model\Site;

/**
 * @Flow\Scope("singleton")
 * @internal
 */
class FusionConfigurationCache
{
    /**
     * @Flow\InjectConfiguration("fusion.enableObjectTreeCache")
     * @var bool
     */
    protected $enableObjectTreeCache;

    /**
     * @var VariableFrontend
     */
    protected $fusionConfigurationCache;

    /**
     * @param \Closure(): FusionConfiguration $fusionConfigurationFactory
     */
    public function cacheFusionConfigurationBySite(Site $site, \Closure $fusionConfigurationFactory): FusionConfiguration
    {
        if (!$this->enableObjectTreeCache) {
            return $fusionConfigurationFactory();
        }

        $siteResourcesPackageKey = $site->getSiteResourcesPackageKey();

        $cacheIdentifier = str_replace('.', '_', $siteResourcesPackageKey);

        if ($this->fusionConfigurationCache->has($cacheIdentifier)) {
            return FusionConfiguration::fromArray($this->fusionConfigurationCache->get($cacheIdentifier));
        }

        $fusionConfiguration = $fusionConfigurationFactory();

        $this->fusionConfigurationCache->set($cacheIdentifier, $fusionConfiguration->toArray());

        return $fusionConfiguration;
    }
}
