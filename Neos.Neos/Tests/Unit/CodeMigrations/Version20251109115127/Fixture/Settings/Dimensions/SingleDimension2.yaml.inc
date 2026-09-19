# some YAML with comments
Neos:
  ContentRepository:
    contentDimensions:
      language:
        label: 'Neos.Demo:Main:contentDimensions.language'
        icon: icon-language
        default: en_US
        defaultPreset: en_US
        presets:
          en_US:
            label: 'English (US)'
            uriSegment: en
            values:
              - en_US

-----
Neos:
  ContentRepositoryRegistry:
    contentRepositories:
      default:
        contentDimensions:
          language:
            label: 'Neos.Demo:Main:contentDimensions.language'
            icon: icon-language
            values:
              en_US:
                label: 'English (US)'
  Neos:
    sites:
      '*':
        contentDimensions:
          defaultDimensionSpacePoint:
            language: en_US
          resolver:
            factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory
            options:
              segments:
                -
                  dimensionIdentifier: language
                  dimensionValueMapping:
                    en_US: en
