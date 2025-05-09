@flowEntities @contentrepository
Feature: Routing functionality if path segments are missing like during tethered node creation

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

    When the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |

    # lady-eleonode-rootford
    #   shernode-homes
    #      sir-david-nodenborough
    #        duke-of-contentshire (content node)
    #        earl-o-documentbourgh
    #      nody-mc-nodeface
    #
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | parentNodeAggregateId  | nodeTypeName                   | initialPropertyValues  | nodeName |
      | shernode-homes         | lady-eleonode-rootford | Neos.Neos:Test.Routing.Page    | {}                     | node1    |
      | sir-david-nodenborough | shernode-homes         | Neos.Neos:Test.Routing.Page    | {}                     | node2    |
      | duke-of-contentshire   | sir-david-nodenborough | Neos.Neos:Test.Routing.Content | {}                     | node3    |
      | earl-o-documentbourgh  | sir-david-nodenborough | Neos.Neos:Test.Routing.Page    | {}                     | node4    |
      | nody-mc-nodeface       | shernode-homes         | Neos.Neos:Test.Routing.Page    | {}                     | node5    |
    And A site exists for node name "node1"
    And the sites configuration is:
    """yaml
    Neos:
      Neos:
        sites:
          'node1':
            preset: 'default'
            uriPathSuffix: ''
            contentDimensions:
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory
    """
  Scenario: Match homepage URL
    When I am on URL "/"
    Then the matched node should be "shernode-homes" in dimension "{}"

  Scenario: Resolve nodes correctly from homepage
    When I am on URL "/"
    Then the node "shernode-homes" in dimension "{}" should resolve to URL "/"
    And the node "sir-david-nodenborough" in dimension "{}" should resolve to URL "/sir-david-nodenborough"
    And the node "earl-o-documentbourgh" in dimension "{}" should resolve to URL "/sir-david-nodenborough/earl-o-documentbourgh"

  Scenario: Match node lower in the tree
    When I am on URL "/sir-david-nodenborough/earl-o-documentbourgh"
    Then the matched node should be "earl-o-documentbourgh" in dimension "{}"

  Scenario: Resolve from node lower in the tree
    When I am on URL "/sir-david-nodenborough/earl-o-documentbourgh"
    Then the node "shernode-homes" in dimension "{}" should resolve to URL "/"
    And the node "sir-david-nodenborough" in dimension "{}" should resolve to URL "/sir-david-nodenborough"
    And the node "earl-o-documentbourgh" in dimension "{}" should resolve to URL "/sir-david-nodenborough/earl-o-documentbourgh"

  Scenario: Add uri path segment on first level
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                            |
      | nodeAggregateId           | "sir-david-nodenborough"                         |
      | originDimensionSpacePoint | {}                                               |
      | propertyValues            | {"uriPathSegment": "david-nodenborough-updated"} |
    And I am on URL "/"
    Then the node "sir-david-nodenborough" in dimension "{}" should resolve to URL "/david-nodenborough-updated"
    And the node "earl-o-documentbourgh" in dimension "{}" should resolve to URL "/david-nodenborough-updated/earl-o-documentbourgh"

  Scenario: Add uri path segment on second level
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                            |
      | nodeAggregateId           | "earl-o-documentbourgh"                         |
      | originDimensionSpacePoint | {}                                               |
      | propertyValues            | {"uriPathSegment": "earl-documentbourgh-updated"} |
    And I am on URL "/"
    Then the node "earl-o-documentbourgh" in dimension "{}" should resolve to URL "/sir-david-nodenborough/earl-documentbourgh-updated"

  Scenario: Add empty uri path segment on first level
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                            |
      | nodeAggregateId           | "sir-david-nodenborough"                         |
      | originDimensionSpacePoint | {}                                               |
      | propertyValues            | {"uriPathSegment": ""} |
    And I am on URL "/"
    Then the node "sir-david-nodenborough" in dimension "{}" should resolve to URL "/sir-david-nodenborough"
    And the node "earl-o-documentbourgh" in dimension "{}" should resolve to URL "/sir-david-nodenborough/earl-o-documentbourgh"

  Scenario: Uri path segment is unset after having been set before
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                            |
      | nodeAggregateId           | "sir-david-nodenborough"                         |
      | originDimensionSpacePoint | {}                                               |
      | propertyValues            | {"uriPathSegment": "david-nodenborough-updated"} |
    And I am on URL "/"
    Then the node "sir-david-nodenborough" in dimension "{}" should resolve to URL "/david-nodenborough-updated"
    And the node "earl-o-documentbourgh" in dimension "{}" should resolve to URL "/david-nodenborough-updated/earl-o-documentbourgh"
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                            |
      | nodeAggregateId           | "sir-david-nodenborough"                         |
      | originDimensionSpacePoint | {}                                               |
      | propertyValues            | {"uriPathSegment": null}                         |
    Then the node "sir-david-nodenborough" in dimension "{}" should resolve to URL "/sir-david-nodenborough"
    And the node "earl-o-documentbourgh" in dimension "{}" should resolve to URL "/sir-david-nodenborough/earl-o-documentbourgh"

  Scenario: Uri path segment is set to empty string having been set before
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                            |
      | nodeAggregateId           | "sir-david-nodenborough"                         |
      | originDimensionSpacePoint | {}                                               |
      | propertyValues            | {"uriPathSegment": "david-nodenborough-updated"} |
    And I am on URL "/"
    Then the node "sir-david-nodenborough" in dimension "{}" should resolve to URL "/david-nodenborough-updated"
    And the node "earl-o-documentbourgh" in dimension "{}" should resolve to URL "/david-nodenborough-updated/earl-o-documentbourgh"
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                            |
      | nodeAggregateId           | "sir-david-nodenborough"                         |
      | originDimensionSpacePoint | {}                                               |
      | propertyValues            | {"uriPathSegment": ""}                           |
    Then the node "sir-david-nodenborough" in dimension "{}" should resolve to URL "/sir-david-nodenborough"
    And the node "earl-o-documentbourgh" in dimension "{}" should resolve to URL "/sir-david-nodenborough/earl-o-documentbourgh"
