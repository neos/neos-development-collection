@flowEntities @contentrepository
Feature: Routing behavior of soft removed nodes

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
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |

    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | parentNodeAggregateId  | nodeTypeName                   | initialPropertyValues                    | nodeName |
      | shernode-homes         | lady-eleonode-rootford | Neos.Neos:Test.Routing.Page    | {"uriPathSegment": "ignore-me"}          | node1    |
      | sir-david-nodenborough | shernode-homes         | Neos.Neos:Test.Routing.Page    | {"uriPathSegment": "david-nodenborough"} | node2    |
      | duke-of-contentshire   | sir-david-nodenborough | Neos.Neos:Test.Routing.Content | {"uriPathSegment": "ignore-me"}          | node3    |
      | earl-o-documentbourgh  | sir-david-nodenborough | Neos.Neos:Test.Routing.Page    | {"uriPathSegment": "earl-document"}      | node4    |
      | leaf-mc-node           | earl-o-documentbourgh  | Neos.Neos:Test.Routing.Page    | {"uriPathSegment": "leaf"}               | node5    |
      | nody-mc-nodeface       | shernode-homes         | Neos.Neos:Test.Routing.Page    | {"uriPathSegment": "nody"}               | node6    |
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
              resolver:
                factoryClassName: Neos\Neos\FrontendRouting\DimensionResolution\Resolver\NoopResolverFactory
    """

  Scenario: Soft remove leaf node
    When the command TagSubtree is executed with payload:
      | Key                          | Value          |
      | nodeAggregateId              | "leaf-mc-node" |
      | coveredDimensionSpacePoint   | {}             |
      | nodeVariantSelectionStrategy | "allVariants"  |
      | tag                          | "removed"      |
    Then No node should match URL "/david-nodenborough/earl-document/leaf"
    And The node "leaf-mc-node" in dimension "{}" should not resolve to an URL

  Scenario: Soft remove leaf node and reinstate
    When the command TagSubtree is executed with payload:
      | Key                          | Value          |
      | nodeAggregateId              | "leaf-mc-node" |
      | coveredDimensionSpacePoint   | {}             |
      | nodeVariantSelectionStrategy | "allVariants"  |
      | tag                          | "removed"      |
    Then No node should match URL "/david-nodenborough/earl-document/leaf"
    And The node "leaf-mc-node" in dimension "{}" should not resolve to an URL

    When the command UntagSubtree is executed with payload:
      | Key                          | Value          |
      | nodeAggregateId              | "leaf-mc-node" |
      | coveredDimensionSpacePoint   | {}             |
      | nodeVariantSelectionStrategy | "allVariants"  |
      | tag                          | "removed"      |
    When I am on URL "/david-nodenborough/earl-document/leaf"
    Then the matched node should be "leaf-mc-node" in dimension "{}"
    And The node "leaf-mc-node" in dimension "{}" should resolve to URL "/david-nodenborough/earl-document/leaf"

  Scenario: Soft remove node with child nodes
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "removed"                |
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in dimension "{}" should not resolve to an URL
    And The node "earl-o-documentbourgh" in dimension "{}" should not resolve to an URL

  Scenario: Soft remove two nodes, reinstate the higher one
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "removed"                |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "earl-o-documentbourgh" |
      | coveredDimensionSpacePoint   | {}                      |
      | nodeVariantSelectionStrategy | "allVariants"           |
      | tag                          | "removed"               |
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in dimension "{}" should not resolve to an URL
    And The node "earl-o-documentbourgh" in dimension "{}" should not resolve to an URL
    When the command UntagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "removed"                |
    When I am on URL "/david-nodenborough"
    Then the matched node should be "sir-david-nodenborough" in dimension "{}"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in dimension "{}" should resolve to URL "/david-nodenborough"
    And The node "earl-o-documentbourgh" in dimension "{}" should not resolve to an URL

  Scenario: Soft remove two nodes, reinstate the lower one
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "removed"                |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "earl-o-documentbourgh" |
      | coveredDimensionSpacePoint   | {}                      |
      | nodeVariantSelectionStrategy | "allVariants"           |
      | tag                          | "removed"               |
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in dimension "{}" should not resolve to an URL
    And The node "earl-o-documentbourgh" in dimension "{}" should not resolve to an URL
    When the command UntagSubtree is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "earl-o-documentbourgh" |
      | coveredDimensionSpacePoint   | {}                      |
      | nodeVariantSelectionStrategy | "allVariants"           |
      | tag                          | "removed"               |
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in dimension "{}" should not resolve to an URL
    And The node "earl-o-documentbourgh" in dimension "{}" should not resolve to an URL

  Scenario: Move implicit soft removed node
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "removed"                |
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                   |
      | nodeAggregateId                     | "earl-o-documentbourgh" |
      | dimensionSpacePoint                 | {}                      |
      | newParentNodeAggregateId            | "nody-mc-nodeface"      |
      | newSucceedingSiblingNodeAggregateId | null                    |
    When I am on URL "/nody/earl-document"
    Then the matched node should be "earl-o-documentbourgh" in dimension "{}"

  Scenario: Move explicit soft removed node
    When the command TagSubtree is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "earl-o-documentbourgh" |
      | coveredDimensionSpacePoint   | {}                      |
      | nodeVariantSelectionStrategy | "allVariants"           |
      | tag                          | "removed"               |
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                   |
      | nodeAggregateId                     | "earl-o-documentbourgh" |
      | dimensionSpacePoint                 | {}                      |
      | newParentNodeAggregateId            | "nody-mc-nodeface"      |
      | newSucceedingSiblingNodeAggregateId | null                    |
    Then No node should match URL "/nody/earl-document"
    And The node "leaf-mc-node" in dimension "{}" should not resolve to an URL

  Scenario: Add child node underneath soft remove node and reinstantiate parent (see https://github.com/neos/neos-development-collection/issues/4639)
    When the command TagSubtree is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "nody-mc-nodeface" |
      | coveredDimensionSpacePoint   | {}                 |
      | nodeVariantSelectionStrategy | "allVariants"      |
      | tag                          | "removed"          |
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | parentNodeAggregateId | nodeTypeName                | initialPropertyValues            |
      | nody-mc-nodeface-child | nody-mc-nodeface      | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "nody-child"} |
    When the command UntagSubtree is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "nody-mc-nodeface" |
      | coveredDimensionSpacePoint   | {}                 |
      | nodeVariantSelectionStrategy | "allVariants"      |
      | tag                          | "removed"          |
    When I am on URL "/nody/nody-child"
    Then the matched node should be "nody-mc-nodeface-child" in dimension "{}"

  Scenario: Soft remove leaf node and create sibling with same uri path segment
    When I am on URL "/david-nodenborough/earl-document/leaf"
    Then the matched node should be "leaf-mc-node" in dimension "{}"
    And The node "leaf-mc-node" in dimension "{}" should resolve to URL "/david-nodenborough/earl-document/leaf"

    When the command TagSubtree is executed with payload:
      | Key                          | Value          |
      | nodeAggregateId              | "leaf-mc-node" |
      | coveredDimensionSpacePoint   | {}             |
      | nodeVariantSelectionStrategy | "allVariants"  |
      | tag                          | "removed"      |
    Then No node should match URL "/david-nodenborough/earl-document/leaf"
    And The node "leaf-mc-node" in dimension "{}" should not resolve to an URL

    # create sibling with the same path
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                   | Value                         |
      | nodeAggregateId       | "leaf-sibling"                |
      | nodeTypeName          | "Neos.Neos:Test.Routing.Page" |
      | parentNodeAggregateId | "earl-o-documentbourgh"       |
      | initialPropertyValues | {"uriPathSegment": "leaf"}    |

    When I am on URL "/david-nodenborough/earl-document/leaf"
    Then the matched node should be "leaf-sibling" in dimension "{}"
    And The node "leaf-sibling" in dimension "{}" should resolve to URL "/david-nodenborough/earl-document/leaf"
