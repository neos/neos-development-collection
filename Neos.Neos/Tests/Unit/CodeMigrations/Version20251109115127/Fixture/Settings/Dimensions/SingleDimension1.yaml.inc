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
            values:
              - en_US
            # The default preset can also have an empty uriSegment value.
            # https://docs.neos.io/cms/manual/content-repository/content-dimensions#behind-the-scenes-routing
            uriSegment: en
          en_UK:
            label: 'English (UK)'
            values:
              - en_UK
              - en_US
            uriSegment: uk
          de:
            label: Deutsch
            values:
              - de
            uriSegment: de

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
                specializations:
                  en_UK:
                    label: 'English (UK)'
              de:
                label: Deutsch
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
                    en_UK: uk
                    de: de
