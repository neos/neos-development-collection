@flowEntities @contentrepository
Feature: Route cache invalidation

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document': {}
    'Neos.Neos:Content': {}
    'Neos.Neos:Test.Routing.Page':
      superTypes:
        'Neos.Neos:Document': true
      properties:
        uriPathSegment:
          type: string
    'Neos.Neos:Test.Routing.Content':
      superTypes:
        'Neos.Neos:Content': true
      properties:
        uriPathSegment:
          type: string
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in the active content stream of workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |
    And the graph projection is fully up to date

    # lady-eleonode-rootford
    #   shernode-homes
    #      sir-david-nodenborough
    #        duke-of-contentshire (content node)
    #        earl-o-documentbourgh
    #      nody-mc-nodeface
    #
    And I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | parentNodeAggregateId  | nodeTypeName                   | initialPropertyValues                    | nodeName |
      | shernode-homes         | lady-eleonode-rootford | Neos.Neos:Test.Routing.Page    | {"uriPathSegment": "ignore-me"}          | node1    |
      | sir-david-nodenborough | shernode-homes         | Neos.Neos:Test.Routing.Page    | {"uriPathSegment": "david-nodenborough"} | node2    |
      | duke-of-contentshire   | sir-david-nodenborough | Neos.Neos:Test.Routing.Content | {"uriPathSegment": "ignore-me"}          | node3    |
      | earl-o-documentbourgh  | sir-david-nodenborough | Neos.Neos:Test.Routing.Page    | {"uriPathSegment": "earl-document"}      | node4    |
      | nody-mc-nodeface       | shernode-homes         | Neos.Neos:Test.Routing.Page    | {"uriPathSegment": "nody"}               | node5    |
    And A site exists for node name "node1"
    And the sites configuration is:
    """
    Neos:
      Neos:
        sites:
          '*':
            contentRepository: default
            contentDimensions:
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory
    """
    And The documenturipath projection is up to date

  Scenario: Change uri path segment invalidates route cache
    When I am on URL "/"
    And The URL "/david-nodenborough" should match the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}"
    And The URL "/david-nodenborough/earl-document" should match the node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}"
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                                            |
      | nodeAggregateId           | "sir-david-nodenborough"                         |
      | originDimensionSpacePoint | {}                                               |
      | propertyValues            | {"uriPathSegment": "david-nodenborough-updated"} |
    And The documenturipath projection is up to date
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"


  Scenario: Change uri path segment multiple times invalidates route cache
    When I am on URL "/"
    And The URL "/david-nodenborough" should match the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}"
    And The URL "/david-nodenborough/earl-document" should match the node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}"
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                              |
      | nodeAggregateId           | "sir-david-nodenborough"                           |
      | originDimensionSpacePoint | {}                                                 |
      | propertyValues            | {"uriPathSegment": "david-nodenborough-updated-a"} |
    And The documenturipath projection is up to date
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                              |
      | nodeAggregateId           | "sir-david-nodenborough"                           |
      | originDimensionSpacePoint | {}                                                 |
      | propertyValues            | {"uriPathSegment": "david-nodenborough-updated-b"} |
    And The documenturipath projection is up to date

    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    Then No node should match URL "/david-nodenborough-updated-a"
    And No node should match URL "/david-nodenborough-updated-a/earl-document"


  Scenario: Move node upwards in the tree invalidates route cache
    When I am on URL "/earl-document"
    And The URL "/david-nodenborough" should match the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}"
    And The URL "/david-nodenborough/earl-document" should match the node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}"

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                   |
      | nodeAggregateId                     | "earl-o-documentbourgh" |
      | dimensionSpacePoint                 | {}                      |
      | newParentNodeAggregateId            | "shernode-homes"        |
      | newSucceedingSiblingNodeAggregateId | null                    |
    And The documenturipath projection is up to date

    Then No node should match URL "/david-nodenborough/earl-document"

  Scenario: Move node downwards in the tree invalidates route cache
    When I am on URL "/david-nodenborough/earl-document/nody"
    And The URL "/nody" should match the node "nody-mc-nodeface" in content stream "cs-identifier" and dimension "{}"

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                   |
      | nodeAggregateId                     | "nody-mc-nodeface"      |
      | dimensionSpacePoint                 | {}                      |
      | newParentNodeAggregateId            | "earl-o-documentbourgh" |
      | newSucceedingSiblingNodeAggregateId | null                    |
    And The documenturipath projection is up to date

    Then No node should match URL "/nody"

  Scenario: Disable node aggregate invalidates route cache
    When I am on URL "/earl-document"
    And The URL "/david-nodenborough" should match the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}"
    And The URL "/david-nodenborough/earl-document" should match the node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}"

    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And The documenturipath projection is up to date

    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"


  Scenario: Removed node aggregate invalidates route cache
    When I am on URL "/earl-document"
    And The URL "/david-nodenborough" should match the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}"
    And The URL "/david-nodenborough/earl-document" should match the node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}"

    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And The documenturipath projection is up to date

    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
