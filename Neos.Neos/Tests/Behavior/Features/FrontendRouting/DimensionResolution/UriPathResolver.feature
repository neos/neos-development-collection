Feature: UriPathResolver works as expected

  Scenario: No dimension
    Given I have no content dimensions
    When I am on URL "/"

    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options:
    """
    """
    Then the resolved dimension should be '{}'

  Scenario: single dimension with two explicit values
    Given I have the following content dimensions:
      | Identifier | Default | Values | Generalizations |
      | language   | en      | en, de |                 |

    When I am on URL "/deu"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options:
    """
    segments:
      -
        dimensionIdentifier: language
        # TODO: REMOVE // HANDLE DIFFERENTLY??
        defaultDimensionValue: de
        toUriPathSegmentMapping:
          # Dimension Value -> URI Path Segment (or is the other way around more logical?)
          de: deu
          en: uk
    """
    Then the resolved dimension should be '{"language": deu"}' and the remaining URI Path should be "/"
