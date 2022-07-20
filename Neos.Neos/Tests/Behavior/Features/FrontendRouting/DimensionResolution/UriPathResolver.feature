Feature: UriPathResolver works as expected

  We model the following examples:
  - Dimensions:
  -- no dimensions
  -- with one dimension
  -- with multiple dimensions
  - default value:
  -- with non-empty default value (de: 'deu')
  -- with empty default value (de: '')
  - URL:
  -- / (homepage)
  -- /deu (without further uri path segment)
  -- /deu/test (with further URI path segment)

  Additionally, the following error cases are modelled:
  - two uri path segment identifiers mapping to different dimensions
  - an empty URI path segment with is NOT the default dimension value (we might support this in the medium term)
  - non-existing dimensions
  - "/" as separator
  - separator contained in URI Path segment
  - "/" in uri path segment

  Then, the following special cases are modelled:
  - TODO different separator
  - TODO positional array sorting

  Scenario: No dimension
    Given I have no content dimensions
    When I am on URL "/"

    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options:
    """
    """
    Then the resolved dimension should be '{}' and the remaining URI Path should be "/"

  Scenario: One dimension; with non-empty default value; /
    Given I have the following content dimensions:
      | Identifier | Values | Generalizations |
      | language   | en, de |                 |

    When I am on URL "/"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options:
    """
    segments:
      -
        dimensionIdentifier: language
        dimensionValueMapping:
          # Dimension Value -> URI Path Segment
          de: deu
          en: uk
    """
    # the UriPathResolver will return an empty object for the homepage, but then the DelegatingResolver will fill
    # it with the default values
    Then the resolved dimension should be '{}' and the remaining URI Path should be "/"

  Scenario: One dimension; with non-empty default value; /deu
    Given I have the following content dimensions:
      | Identifier | Values | Generalizations |
      | language   | en, de |                 |

    When I am on URL "/deu"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options:
    """
    segments:
      -
        dimensionIdentifier: language
        dimensionValueMapping:
          # Dimension Value -> URI Path Segment
          de: deu
          en: uk
    """
    Then the resolved dimension should be '{"language": "de"}' and the remaining URI Path should be "/"

  Scenario: One dimension; with non-empty default value; /deu/test
    Given I have the following content dimensions:
      | Identifier | Values | Generalizations |
      | language   | en, de |                 |

    When I am on URL "/deu/test"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options:
    """
    segments:
      -
        dimensionIdentifier: language
        dimensionValueMapping:
          # Dimension Value -> URI Path Segment
          de: deu
          en: uk
    """
    Then the resolved dimension should be '{"language": "de"}' and the remaining URI Path should be "/test"

  Scenario: One dimension; with empty default value; /
    Given I have the following content dimensions:
      | Identifier | Values | Generalizations |
      | language   | en, de |                 |

    When I am on URL "/"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options:
    """
    segments:
      -
        dimensionIdentifier: language
        dimensionValueMapping:
          # empty for "de"
          de: ''
          en: uk
    """
    Then the resolved dimension should be '{"language": "de"}' and the remaining URI Path should be "/"

  Scenario: One dimension; with empty default value; /test
    Given I have the following content dimensions:
      | Identifier | Values | Generalizations |
      | language   | en, de |                 |

    When I am on URL "/test"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options:
    """
    segments:
      -
        dimensionIdentifier: language
        dimensionValueMapping:
          # empty for "de"
          de: ''
          en: uk
    """
    Then the resolved dimension should be '{"language": "de"}' and the remaining URI Path should be "/test"

  Scenario: One dimension; with empty default value; /uk
    Given I have the following content dimensions:
      | Identifier | Values | Generalizations |
      | language   | en, de |                 |

    When I am on URL "/uk"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options:
    """
    segments:
      -
        dimensionIdentifier: language
        dimensionValueMapping:
          # empty for "de"
          de: ''
          en: uk
    """
    Then the resolved dimension should be '{"language": "en"}' and the remaining URI Path should be "/"

  Scenario: One dimension; with empty default value; /uk/test
    Given I have the following content dimensions:
      | Identifier | Values | Generalizations |
      | language   | en, de |                 |

    When I am on URL "/uk/test"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options:
    """
    segments:
      -
        dimensionIdentifier: language
        dimensionValueMapping:
          # empty for "de"
          de: ''
          en: uk
    """
    Then the resolved dimension should be '{"language": "en"}' and the remaining URI Path should be "/test"

  Scenario: Multiple dimensions; with non-empty default value; /
    Given I have the following content dimensions:
      | Identifier   | Values         | Generalizations |
      | target_group | normal, simple |                 |
      | language     | en, de         |                 |

    When I am on URL "/"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options:
    """
    segments:
      -
        dimensionIdentifier: language
        dimensionValueMapping:
          de: 'deu'
          en: uk
      -
        dimensionIdentifier: target_group
        dimensionValueMapping:
          normal: 'no'
          simple: 'si'

    """
    # the UriPathResolver will return an empty object for the homepage, but then the DelegatingResolver will fill
    # it with the default values
    Then the resolved dimension should be '{}' and the remaining URI Path should be "/"

  Scenario: Multiple dimensions; with non-empty default value; /uk_si
    Given I have the following content dimensions:
      | Identifier   | Values         | Generalizations |
      | target_group | normal, simple |                 |
      | language     | en, de         |                 |

    When I am on URL "/uk_si"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options:
    """
    segments:
      -
        dimensionIdentifier: language
        dimensionValueMapping:
          # empty for "de"
          de: 'deu'
          en: uk
      -
        dimensionIdentifier: target_group
        dimensionValueMapping:
          # empty for "de"
          normal: 'no'
          simple: 'si'

    """
    Then the resolved dimension should be '{"language": "en", "target_group": "simple"}' and the remaining URI Path should be "/"

  Scenario: Multiple dimensions; with non-empty default value; /uk_si/test
    Given I have the following content dimensions:
      | Identifier   | Values         | Generalizations |
      | target_group | normal, simple |                 |
      | language     | en, de         |                 |

    When I am on URL "/uk_si/test"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options:
    """
    segments:
      -
        dimensionIdentifier: language
        dimensionValueMapping:
          de: 'deu'
          en: uk
      -
        dimensionIdentifier: target_group
        dimensionValueMapping:
          normal: 'no'
          simple: 'si'

    """
    Then the resolved dimension should be '{"language": "en", "target_group": "simple"}' and the remaining URI Path should be "/test"

  Scenario Outline: Multiple dimensions; with empty default value
    Given I have the following content dimensions:
      | Identifier   | Values         | Generalizations |
      | target_group | normal, simple |                 |
      | language     | en, de         |                 |

    When I am on URL "<inputUri>"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options:
    """
    segments:
      -
        dimensionIdentifier: language
        dimensionValueMapping:
          # empty for "de"
          de: ''
          en: uk
      -
        dimensionIdentifier: target_group
        dimensionValueMapping:
          normal: ''
          simple: 'si'

    """
    Then the resolved dimension should be '<expectedDimension>' and the remaining URI Path should be "<expectedRemainingUriPath>"

    Examples:
      | inputUri    | expectedDimension                            | expectedRemainingUriPath |
      | /           | {"language": "de", "target_group": "normal"} | /                        |
      | /test       | {"language": "de", "target_group": "normal"} | /test                    |
      | /si         | {"language": "de", "target_group": "simple"} | /                        |
      | /si/test    | {"language": "de", "target_group": "simple"} | /test                    |
      | /uk         | {"language": "en", "target_group": "normal"} | /                        |
      | /uk/test    | {"language": "en", "target_group": "normal"} | /test                    |
      | /uk_si      | {"language": "en", "target_group": "simple"} | /                        |
      | /uk_si/test | {"language": "en", "target_group": "simple"} | /test                    |
    # TODO /uk_ do NOT RESOLVE

  Scenario: Error: two uri path segment identifiers mapping to different dimensions
    Given I have the following content dimensions:
      | Identifier   | Values         | Generalizations |
      | target_group | normal, simple |                 |
      | language     | en, de         |                 |

    When I am on URL "/"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options and exceptions are caught:
    """
    segments:
      -
        dimensionIdentifier: language
        dimensionValueMapping:
          de: 'deu'
          en: 'deu'
    """
    Then the last command should have thrown an exception of type "UriPathResolverConfigurationException"

  Scenario: Error: two uri path segment identifiers mapping to different dimensions
    Given I have the following content dimensions:
      | Identifier   | Values         | Generalizations |
      | target_group | normal, simple |                 |
      | language     | en, de         |                 |

    When I am on URL "/"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options and exceptions are caught:
    """
    segments:
      -
        dimensionIdentifier: language
        dimensionValueMapping:
          de: ''
          en: uk
      -
        dimensionIdentifier: target_group
        dimensionValueMapping:
          normal: ''
          simple: 'uk'
    """
    Then the last command should have thrown an exception of type "UriPathResolverConfigurationException"

  Scenario: Error: non-existing dimension name
    Given I have the following content dimensions:
      | Identifier   | Values         | Generalizations |
      | language     | en, de         |                 |

    When I am on URL "/"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options and exceptions are caught:
    """
    segments:
      -
        dimensionIdentifier: language_notexisting
    """
    Then the last command should have thrown an exception of type "UriPathResolverConfigurationException"

  Scenario: Error: non-existing dimension value
    Given I have the following content dimensions:
      | Identifier   | Values         | Generalizations |
      | language     | en, de         |                 |

    When I am on URL "/"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options and exceptions are caught:
    """
    segments:
      -
        dimensionIdentifier: language
        dimensionValueMapping:
          nonExisting: "foo"
    """
    Then the last command should have thrown an exception of type "UriPathResolverConfigurationException"

  Scenario: Error: / in dimensionValueMapping
    Given I have the following content dimensions:
      | Identifier   | Values         | Generalizations |
      | language     | en, de         |                 |

    When I am on URL "/"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options and exceptions are caught:
    """
    segments:
      -
        dimensionIdentifier: language
        dimensionValueMapping:
          de: de
          en: u/k
    """
    Then the last command should have thrown an exception of type "UriPathResolverConfigurationException"

  Scenario: Error: / as separator
    Given I have the following content dimensions:
      | Identifier   | Values         | Generalizations |
      | language     | en, de         |                 |

    When I am on URL "/"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options and exceptions are caught:
    """
    separator: 'foo/f'
    segments:
      -
        dimensionIdentifier: language
        dimensionValueMapping:
          de: de
          en: uk
    """
    Then the last command should have thrown an exception of type "UriPathResolverConfigurationException"

  Scenario: Error: separator in dimensionValueMapping
    Given I have the following content dimensions:
      | Identifier   | Values         | Generalizations |
      | language     | en, de         |                 |

    When I am on URL "/"
    And I invoke the Dimension Resolver "Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory" with options and exceptions are caught:
    """
    separator: '-'
    segments:
      -
        dimensionIdentifier: language
        dimensionValueMapping:
          de: d-e
          en: uk
    """
    Then the last command should have thrown an exception of type "UriPathResolverConfigurationException"
