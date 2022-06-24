<?php
declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\Configuration;

use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\FrontendRouting\ValueObject\SiteIdentifier;

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

    /*public function getContentRepositoryIdentifierForSite(SiteIdentifier $siteIdentifier): ContentRepositoryIdentifier
    {
        $sitesConfig = $this->getConfigurationForSite($siteIdentifier);
        // TODO: more safe??
        return ContentRepositoryIdentifier::fromString($sitesConfig['contentRepository']);
    }

    public function getDimensionResolverClassNameForSite(SiteIdentifier $siteIdentifier): string
    {
        $sitesConfig = $this->getConfigurationForSite($siteIdentifier);
        // TODO: more safe??
        return $sitesConfig['dimensionResolver']['className'];
    }

    public function getDimensionResolverOptionsForSite(SiteIdentifier $siteIdentifier): array
    {
        $sitesConfig = $this->getConfigurationForSite($siteIdentifier);
        // TODO: more safe??
        // TODO: remove "routing" key.
        return $sitesConfig['dimensionResolver']['options'];
    }*/

    // Domain/Model/SiteDetails
        // getSiteConfig() -> untypisiert!!!! (array)
        // Site Entity; ...
        // defaultDimensionSpacePoint()
    // Domain/Repository/SiteDetailsFinder
        // byId()
        // byRequest()

    private function getConfigurationForSite(SiteIdentifier $siteIdentifier): array
    {
        // Ohne rekursiven merge :)
        return $this->configuration[$siteIdentifier->getValue()] ?? $this->configuration['*'];
        // TODO: more safe?? (null case)
    }

    private function getSiteConfigurationForRequest(RequestInterface $request): array
    {
        // Ohne rekursiven merge :)
        return $this->configuration[$siteIdentifier->getValue()] ?? $this->configuration['*'];
        // TODO: more safe?? (null case)
    }


}
