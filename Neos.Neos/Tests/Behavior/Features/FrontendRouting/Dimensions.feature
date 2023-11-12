@flowEntities @contentrepository
Feature: Routing functionality with multiple content dimensions

  Background:
    Given using the following content dimensions:
      | Identifier | Values      | Generalizations |
      | market     | DE, CH      | CH->DE          |
      | language   | en, de, gsw | gsw->de->en     |
    And using the following node types:
    """yaml
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document':
      properties:
        uriPathSegment:
          type: string
    'Neos.Neos:Content': []

    'Neos.Neos:Test.Routing.Page':
      superTypes:
        'Neos.Neos:Document': true
      constraints:
        nodeTypes:
          '*': true
          'Neos.Neos:Test.Routing.Page': true
          'Neos.Neos:Test.Routing.SomeOtherPage': true
          'Neos.Neos:Test.Routing.Content': true

    'Neos.Neos:Test.Routing.Content':
      superTypes:
        'Neos.Neos:Content': true

    'Neos.Neos:Test.Routing.SomeOtherPage':
      superTypes:
        'Neos.Neos:Test.Routing.Page': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"

    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                                                                                                                                                                     |
      | contentStreamId             | "cs-identifier"                                                                                                                                                                                           |
      | nodeAggregateId             | "lady-eleonode-rootford"                                                                                                                                                                                  |
      | nodeTypeName                | "Neos.Neos:Sites"                                                                                                                                                                                         |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"en"}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | parentNodeAggregateId  | nodeTypeName                | initialPropertyValues           | nodeName |
      | sir-david-nodenborough | lady-eleonode-rootford | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "ignore-me"} | node1    |
      | nody-mc-nodeface       | sir-david-nodenborough | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "nody"}      | node2    |
      | carl-destinode         | nody-mc-nodeface       | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "carl"}      | node3    |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                            |
      | contentStreamId | "cs-identifier"                  |
      | nodeAggregateId | "carl-destinode"                 |
      | sourceOrigin    | {"market":"DE", "language":"en"} |
      | targetOrigin    | {"market":"DE", "language":"de"} |
    And the graph projection is fully up to date
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                            |
      | contentStreamId           | "cs-identifier"                  |
      | nodeAggregateId           | "carl-destinode"                 |
      | originDimensionSpacePoint | {"market":"DE", "language":"de"} |
      | propertyValues            | {"uriPathSegment": "karl-de"}    |
    And A site exists for node name "node1"
    And the sites configuration is:
    """yaml
    Neos:
      Neos:
        sites:
          'node1':
            preset: default
            uriPathSuffix: ''
            contentDimensions:
              defaultDimensionSpacePoint:
                market: DE
                language: en
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory
                options:
                  segments:
                    -
                      dimensionIdentifier: language
                      dimensionValueMapping:
                        de: de
                        gsw: gsw
                        en: ''
                    -
                      dimensionIdentifier: market
                      dimensionValueMapping:
                        DE: ''
    """

    And the graph projection is fully up to date
    And The documenturipath projection is up to date

  Scenario: Resolve homepage URL in multiple dimensions
    When I am on URL "/"
    Then the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"en"}' should resolve to URL "/"
    And the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"de"}' should resolve to URL "/de/"

  Scenario: Resolve homepage URL without empty dimensionValueMapping
    # when resolving the URL "/", the defaultDimensionSpacePoint is used.
    # -> /en should not resolve then.
    # => that's why when we want to generate a URL for the homepage with the default dimensionSpacePoint,
    #    this must generate "/" (and not ("/en" f.e.)
    When the sites configuration is:
    """yaml
    Neos:
      Neos:
        sites:
          'node1':
            preset: default
            uriPathSuffix: ''
            contentDimensions:
              defaultDimensionSpacePoint:
                market: DE
                language: en
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory
                options:
                  segments:
                    -
                      dimensionIdentifier: language
                      dimensionValueMapping:
                        de: de
                        gsw: gsw
                        # !!! MODIFIED and not empty.
                        en: en
                    -
                      dimensionIdentifier: market
                      dimensionValueMapping:
                        DE: ''
    """
    # URL -> Node
    # special case for homepage: / should be resolved to default dimensions.
    When I am on URL "/"
    Then the matched node should be "sir-david-nodenborough" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"en"}'
    When I am on URL "/de"
    Then the matched node should be "sir-david-nodenborough" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"de"}'
    # homepage should not be available via dimension
    Then No node should match URL "/en"
    # all other english pages should have /en prefix as usual.
    When I am on URL "/en/nody/carl"
    Then the matched node should be "carl-destinode" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"en"}'
    Then No node should match URL "/nody/carl"
    When I am on URL "/de/nody/karl-de"
    Then the matched node should be "carl-destinode" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"de"}'

    # Node -> URL
    # special case for homepage: homepage should be resolved to /
    Then the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"en"}' should resolve to URL "/"
    And the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"de"}' should resolve to URL "/de/"
    And the node "carl-destinode" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"en"}' should resolve to URL "/en/nody/carl"
    And the node "carl-destinode" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"de"}' should resolve to URL "/de/nody/karl-de"

  Scenario: Resolve node URLs in multiple dimensions
    When I am on URL "/"
    Then the node "carl-destinode" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"en"}' should resolve to URL "/nody/carl"
    And the node "carl-destinode" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"de"}' should resolve to URL "/de/nody/karl-de"

  Scenario: Move Dimension, then resolving should still work
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values         | Generalizations |
      | market     | DE, CH         | CH->DE          |
      | language   | en, de_DE, gsw | gsw->de_DE->en  |
    And the sites configuration is:
    """yaml
    Neos:
      Neos:
        sites:
          'node1':
            preset: default
            uriPathSuffix: ''
            contentDimensions:
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory
                options:
                  segments:
                    -
                      dimensionIdentifier: language
                      dimensionValueMapping:
                        de_DE: de
                        gsw: gsw
                        en: ''
    """

    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """yaml
    migration:
      -
        transformations:
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: {"market":"DE", "language":"de"}
              to: {"market":"DE", "language":"de_DE"}
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: {"market":"CH", "language":"de"}
              to: {"market":"CH", "language":"de_DE"}
    """
    When the command PublishWorkspace is executed with payload:
      | Key           | Value          |
      | workspaceName | "migration-cs" |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date

    When I am on URL "/"
    Then the node "carl-destinode" in content stream "cs-identifier" and dimension '{"market":"CH", "language":"en"}' should resolve to URL "/nody/carl"
    And the node "carl-destinode" in content stream "cs-identifier" and dimension '{"market":"CH", "language":"de_DE"}' should resolve to URL "/de/nody/karl-de"
    And the node "carl-destinode" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"de_DE"}' should resolve to URL "/de/nody/karl-de"


  Scenario: Match homepage node in default dimension
    When I am on URL "/"
    Then the matched node should be "sir-david-nodenborough" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"en"}'

  Scenario: Match homepage node in specific dimension
    When I am on URL "/de"
    Then the matched node should be "sir-david-nodenborough" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"de"}'

  Scenario: Match node in default dimension
    When I am on URL "/nody/carl"
    Then the matched node should be "carl-destinode" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"en"}'

  Scenario: Match node in specific dimension
    When I am on URL "/de/nody/karl-de"
    Then the matched node should be "carl-destinode" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"de"}'

  Scenario: Add Dimension shine through, then resolving should still work
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values          | Generalizations         |
      | market     | DE, CH          | CH->DE                  |
      | language   | en, de, gsw, at | gsw->de->en, at->de->en |
    And the sites configuration is:
    """yaml
    Neos:
      Neos:
        sites:
          'node1':
            preset: default
            uriPathSuffix: ''
            contentDimensions:
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory
                options:
                  segments:
                    -
                      dimensionIdentifier: language
                      dimensionValueMapping:
                        de: de
                        at: at
                        gsw: gsw
                        en: ''
    """
    And the node "carl-destinode" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"at"}' should not resolve to an URL
    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """yaml
    migration:
      -
        transformations:
          -
            type: 'AddDimensionShineThrough'
            settings:
              from: {"market":"DE", "language":"de"}
              to: {"market":"DE", "language":"at"}
          -
            type: 'AddDimensionShineThrough'
            settings:
              from: {"market":"CH", "language":"de"}
              to: {"market":"CH", "language":"at"}
    """
    When the command PublishWorkspace is executed with payload:
      | Key           | Value          |
      | workspaceName | "migration-cs" |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date

    When I am on URL "/"
    And the node "carl-destinode" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"de"}' should resolve to URL "/de/nody/karl-de"
    And the node "carl-destinode" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"at"}' should resolve to URL "/at/nody/karl-de"

    # now, when changing the UriPathSegment, this should change in the dimension and its shine-through.
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                   |
      | contentStreamId           | "cs-identifier"                         |
      | nodeAggregateId           | "carl-destinode"                        |
      | originDimensionSpacePoint | {"market":"DE", "language":"de"}        |
      | propertyValues            | {"uriPathSegment": "karl-aktualisiert"} |
    And The documenturipath projection is up to date
    When I am on URL "/"
    And the node "carl-destinode" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"de"}' should resolve to URL "/de/nody/karl-aktualisiert"
    # testcase for #4256
    And the node "carl-destinode" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"at"}' should resolve to URL "/at/nody/karl-aktualisiert"


  Scenario: Create new Dimension value and adjust root node, then root node resolving should still work.
    # new "fr" language
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values          | Generalizations |
      | market     | DE, CH          | CH->DE          |
      | language   | en, de, gsw, fr | gsw->de->en     |
    And the sites configuration is:
    """yaml
    Neos:
      Neos:
        sites:
          'node1':
            preset: default
            uriPathSuffix: ''
            contentDimensions:
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory
                options:
                  segments:
                    -
                      dimensionIdentifier: language
                      dimensionValueMapping:
                        de: de
                        gsw: gsw
                        en: ''
                        fr: 'fr'
                    -
                      dimensionIdentifier: market
                      dimensionValueMapping:
                        DE: ''
    """
    And the command UpdateRootNodeAggregateDimensions is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
    And the graph projection is fully up to date
    # create variant for fr and sites node
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                            |
      | contentStreamId | "cs-identifier"                  |
      | nodeAggregateId | "sir-david-nodenborough"         |
      | sourceOrigin    | {"market":"DE", "language":"en"} |
      | targetOrigin    | {"market":"DE", "language":"fr"} |
    And the graph projection is fully up to date
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                            |
      | contentStreamId | "cs-identifier"                  |
      | nodeAggregateId | "nody-mc-nodeface"               |
      | sourceOrigin    | {"market":"DE", "language":"en"} |
      | targetOrigin    | {"market":"DE", "language":"fr"} |

    And the graph projection is fully up to date
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                            |
      | contentStreamId           | "cs-identifier"                  |
      | nodeAggregateId           | "nody-mc-nodeface"               |
      | originDimensionSpacePoint | {"market":"DE", "language":"fr"} |
      | propertyValues            | {"uriPathSegment": "nody-fr"}    |

    And the graph projection is fully up to date

    When I am on URL "/"
    Then the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"fr"}' should resolve to URL "/fr/"
    Then the node "nody-mc-nodeface" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"fr"}' should resolve to URL "/fr/nody-fr"


  Scenario: Create new Dimension value and adjust root node, then root node resolving should still work.
    # new "fr" language
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values          | Generalizations |
      | market     | DE, CH          | CH->DE          |
      | language   | en, de, gsw, fr | gsw->de->en     |
    And the sites configuration is:
    """yaml
    Neos:
      Neos:
        sites:
          'node1':
            preset: default
            uriPathSuffix: ''
            contentDimensions:
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\UriPathResolverFactory
                options:
                  segments:
                    -
                      dimensionIdentifier: language
                      dimensionValueMapping:
                        de: de
                        gsw: gsw
                        en: ''
                        fr: 'fr'
                    -
                      dimensionIdentifier: market
                      dimensionValueMapping:
                        DE: ''
    """
    And the command UpdateRootNodeAggregateDimensions is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
    And the graph projection is fully up to date
    # create variant for fr and sites node
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                            |
      | contentStreamId | "cs-identifier"                  |
      | nodeAggregateId | "sir-david-nodenborough"         |
      | sourceOrigin    | {"market":"DE", "language":"en"} |
      | targetOrigin    | {"market":"DE", "language":"fr"} |
    And the graph projection is fully up to date
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                            |
      | contentStreamId | "cs-identifier"                  |
      | nodeAggregateId | "nody-mc-nodeface"               |
      | sourceOrigin    | {"market":"DE", "language":"en"} |
      | targetOrigin    | {"market":"DE", "language":"fr"} |

    And the graph projection is fully up to date

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                            |
      | contentStreamId           | "cs-identifier"                  |
      | nodeAggregateId           | "nody-mc-nodeface"               |
      | originDimensionSpacePoint | {"market":"DE", "language":"fr"} |
      | propertyValues            | {"uriPathSegment": "nody-fr"}    |
    And the graph projection is fully up to date


    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                            |
      | contentStreamId                     | "cs-identifier"                  |
      | nodeAggregateId                     | "nody-mc-nodeface"               |
      | dimensionSpacePoint                 | {"market":"DE", "language":"fr"} |
      | newParentNodeAggregateId            | "lady-eleonode-rootford"         |
      | newSucceedingSiblingNodeAggregateId | null                             |
      | relationDistributionStrategy        | "scatter"                        |
    And The documenturipath projection is up to date

    And the graph projection is fully up to date

    When I am on URL "/"
    Then the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"en"}' should resolve to URL "/"
    # moved node
    Then the node "nody-mc-nodeface" in content stream "cs-identifier" and dimension '{"market":"DE", "language":"fr"}' should resolve to URL "/fr/"
