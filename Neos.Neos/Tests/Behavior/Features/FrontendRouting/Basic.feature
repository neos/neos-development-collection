@flowEntities @contentrepository
Feature: Basic routing functionality (match & resolve document nodes in one dimension)

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
    And I am in the active content stream of workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                    |
      | nodeAggregateId             | "lady-eleonode-rootford" |
      | nodeTypeName                | "Neos.Neos:Sites"        |
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
    """yaml
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

  Scenario: Match homepage URL
    When I am on URL "/"
    Then the matched node should be "shernode-homes" in content stream "cs-identifier" and dimension "{}"

  Scenario: Resolve nodes correctly from homepage
    When I am on URL "/"
    Then the node "shernode-homes" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/"
    And the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough"
    And the node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/earl-document"

  Scenario: Match node lower in the tree
    When I am on URL "/david-nodenborough/earl-document"
    Then the matched node should be "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}"

  Scenario: Resolve from node lower in the tree
    When I am on URL "/david-nodenborough/earl-document"
    Then the node "shernode-homes" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/"
    And the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough"
    And the node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/earl-document"

  Scenario: Change uri path segment
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                            |
      | nodeAggregateId           | "sir-david-nodenborough"                         |
      | originDimensionSpacePoint | {}                                               |
      | propertyValues            | {"uriPathSegment": "david-nodenborough-updated"} |
    And The documenturipath projection is up to date
    And I am on URL "/"
    Then the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough-updated"
    And the node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough-updated/earl-document"

  Scenario: Change uri path segment works multiple times (bug #4253)
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

    And I am on URL "/"
    Then the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough-updated-b"
    And the node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough-updated-b/earl-document"

    # !!! when caches were still enabled (without calling DocumentUriPathFinder->disableCache()), the replay below will
    # show really "interesting" (non-correct) results. This was bug #4253.
    When I replay the "Neos\Neos\FrontendRouting\Projection\DocumentUriPathProjection" projection
    Then the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough-updated-b"
    And the node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough-updated-b/earl-document"


  Scenario: Move node upwards in the tree
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                   |
      | nodeAggregateId                     | "earl-o-documentbourgh" |
      | dimensionSpacePoint                 | {}                      |
      | newParentNodeAggregateId            | "shernode-homes"        |
      | newSucceedingSiblingNodeAggregateId | null                    |
    And The documenturipath projection is up to date
    And I am on URL "/earl-document"
    Then the matched node should be "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}"
    And the node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/earl-document"

  Scenario: Move node downwards in the tree
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                   |
      | nodeAggregateId                     | "nody-mc-nodeface"      |
      | dimensionSpacePoint                 | {}                      |
      | newParentNodeAggregateId            | "earl-o-documentbourgh" |
      | newSucceedingSiblingNodeAggregateId | null                    |
    And The documenturipath projection is up to date
    And I am on URL "/david-nodenborough/earl-document/nody"
    Then the matched node should be "nody-mc-nodeface" in content stream "cs-identifier" and dimension "{}"
    And the node "nody-mc-nodeface" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/earl-document/nody"
