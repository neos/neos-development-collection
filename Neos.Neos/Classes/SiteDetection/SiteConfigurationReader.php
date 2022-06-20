<?php
declare(strict_types=1);

namespace Neos\Neos\SiteDetection;

use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\ValueObject\SiteIdentifier;

/**
 * TODO correct location of this class?? I guess not...
 *
 * @Flow\Scope("singleton")
 */
class SiteConfigurationReader
{
    /**
     * @Flow\InjectConfiguration(path="sites")
     * @var array
     */
    protected $configuration;

    public function getContentRepositoryIdentifierForSite(SiteIdentifier $siteIdentifier): ContentRepositoryIdentifier
    {
        $sitesConfig = $this->getConfigurationForSite($siteIdentifier);
        // TODO: more safe??
        return ContentRepositoryIdentifier::fromString($sitesConfig['contentRepository']);
    }

    public function getDimensionResolverClassNameForSite(SiteIdentifier $siteIdentifier): string
    {
        $sitesConfig = $this->getConfigurationForSite($siteIdentifier);
        // TODO: more safe??
        return $sitesConfig['routing']['dimensionResolver']['className'];
    }

    public function getDimensionResolverOptionsForSite(SiteIdentifier $siteIdentifier): array
    {
        $sitesConfig = $this->getConfigurationForSite($siteIdentifier);
        // TODO: more safe??
        return $sitesConfig['routing']['dimensionResolver']['options'];
    }

    private function getConfigurationForSite(SiteIdentifier $siteIdentifier): array
    {
        return $this->configuration[$siteIdentifier->getValue()] ?? $this->configuration['*'];
        // TODO: more safe?? (null case)
    }


}
