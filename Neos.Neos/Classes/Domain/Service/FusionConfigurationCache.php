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
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Fusion\Core\FusionConfiguration;
use Neos\Neos\Domain\Model\Site;

/**
 * @Flow\Scope("singleton")
 * @internal
 */
final class FusionConfigurationCache
{
    public function __construct(
        private VariableFrontend $cache,
        private ?bool $enabled
    ) {
    }

    /**
     * @param \Closure(): FusionConfiguration $fusionConfigurationFactory
     */
    public function cacheFusionConfigurationBySite(Site $site, \Closure $fusionConfigurationFactory): FusionConfiguration
    {
        if (!$this->enabled) {
            return $fusionConfigurationFactory();
        }

        $siteResourcesPackageKey = $site->getSiteResourcesPackageKey();

        $cacheIdentifier = str_replace('.', '_', $siteResourcesPackageKey);

        if ($this->cache->has($cacheIdentifier)) {
            return FusionConfiguration::fromArray($this->cache->get($cacheIdentifier));
        }

        $fusionConfiguration = $fusionConfigurationFactory();

        $this->cache->set($cacheIdentifier, $fusionConfiguration->toArray());

        return $fusionConfiguration;
    }
}
