@flowEntities @contentrepository
Feature: Routing behavior of removed, disabled and re-enabled nodes

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

    # lady-eleonode-rootford
    #   shernode-homes
    #      sir-david-nodenborough
    #        duke-of-contentshire (content node)
    #        earl-o-documentbourgh
    #          leaf-mc-node
    #      nody-mc-nodeface
    #
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

  Scenario: Disable leaf node
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value          |
      | nodeAggregateId              | "leaf-mc-node" |
      | coveredDimensionSpacePoint   | {}             |
      | nodeVariantSelectionStrategy | "allVariants"  |
    Then No node should match URL "/david-nodenborough/earl-document/leaf"
    # contraire to matching, we DO allow resolving of disabled nodes https://github.com/neos/neos-development-collection/pull/4363
    And The node "leaf-mc-node" in dimension "{}" should resolve to URL "/david-nodenborough/earl-document/leaf"

  Scenario: Disable node with child nodes
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in dimension "{}" should resolve to URL "/david-nodenborough"
    And The node "earl-o-documentbourgh" in dimension "{}" should resolve to URL "/david-nodenborough/earl-document"

  Scenario: Disable two nodes, re-enable the higher one
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "earl-o-documentbourgh" |
      | coveredDimensionSpacePoint   | {}                      |
      | nodeVariantSelectionStrategy | "allVariants"           |
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in dimension "{}" should resolve to URL "/david-nodenborough"
    And The node "earl-o-documentbourgh" in dimension "{}" should resolve to URL "/david-nodenborough/earl-document"
    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    When I am on URL "/david-nodenborough"
    Then the matched node should be "sir-david-nodenborough" in dimension "{}"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in dimension "{}" should resolve to URL "/david-nodenborough"
    And The node "earl-o-documentbourgh" in dimension "{}" should resolve to URL "/david-nodenborough/earl-document"

  Scenario: Disable two nodes, re-enable the lower one
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "earl-o-documentbourgh" |
      | coveredDimensionSpacePoint   | {}                      |
      | nodeVariantSelectionStrategy | "allVariants"           |
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in dimension "{}" should resolve to URL "/david-nodenborough"
    And The node "earl-o-documentbourgh" in dimension "{}" should resolve to URL "/david-nodenborough/earl-document"
    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "earl-o-documentbourgh" |
      | coveredDimensionSpacePoint   | {}                      |
      | nodeVariantSelectionStrategy | "allVariants"           |
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in dimension "{}" should resolve to URL "/david-nodenborough"
    And The node "earl-o-documentbourgh" in dimension "{}" should resolve to URL "/david-nodenborough/earl-document"

  Scenario: Move implicit disabled node
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                   |
      | nodeAggregateId                     | "earl-o-documentbourgh" |
      | dimensionSpacePoint                 | {}                      |
      | newParentNodeAggregateId            | "nody-mc-nodeface"      |
      | newSucceedingSiblingNodeAggregateId | null                    |
    When I am on URL "/nody/earl-document"
    Then the matched node should be "earl-o-documentbourgh" in dimension "{}"

  Scenario: Move explicit disabled node
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "earl-o-documentbourgh" |
      | coveredDimensionSpacePoint   | {}                      |
      | nodeVariantSelectionStrategy | "allVariants"           |
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                   |
      | nodeAggregateId                     | "earl-o-documentbourgh" |
      | dimensionSpacePoint                 | {}                      |
      | newParentNodeAggregateId            | "nody-mc-nodeface"      |
      | newSucceedingSiblingNodeAggregateId | null                    |
    Then No node should match URL "/nody/earl-document"
    And The node "leaf-mc-node" in dimension "{}" should resolve to URL "/nody/earl-document/leaf"

  Scenario: Add child node underneath disabled node and re-enable parent (see https://github.com/neos/neos-development-collection/issues/4639)
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "nody-mc-nodeface" |
      | coveredDimensionSpacePoint   | {}                 |
      | nodeVariantSelectionStrategy | "allVariants"      |
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | parentNodeAggregateId | nodeTypeName                | initialPropertyValues            |
      | nody-mc-nodeface-child | nody-mc-nodeface      | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "nody-child"} |
    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "nody-mc-nodeface" |
      | coveredDimensionSpacePoint   | {}                 |
      | nodeVariantSelectionStrategy | "allVariants"      |
    When I am on URL "/nody/nody-child"
    Then the matched node should be "nody-mc-nodeface-child" in dimension "{}"

  Scenario: Duplicate SubtreeWasUntagged on live must not crash projection (issue #5778)
    # Root cause identified in production:
    #   SQLSTATE[22003]: Numeric value out of range: 1690
    #   BIGINT UNSIGNED value is out of range in `disabled` - 1
    #
    # In production, a duplicate SubtreeWasUntagged event reached the live content stream
    # The first untag set disabled from 1 to 0. The second untag tried 0 - 1 → unsigned underflow.
    #
    # We reproduce this by injecting a raw SubtreeWasUntagged event directly onto the live
    # content stream, bypassing the command handler guard (SubtreeIsNotTagged).
    # NOT SURE HOW THIS COULD BE TRIGGERED BY A USER -> WAS NOT ABLE TO REPRODUCE IT THROUGH THE UI, although the history showed
    # that TWO UntagCommands for the same node were added a few seconds apart from each other.
    # DETAILS: https://github.com/neos/neos-development-collection/issues/5778
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "nody-mc-nodeface" |
      | coveredDimensionSpacePoint   | {}                 |
      | nodeVariantSelectionStrategy | "allVariants"      |
    Then No node should match URL "/nody"

    # First enable via normal command → disabled goes from 1 to 0
    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "nody-mc-nodeface" |
      | coveredDimensionSpacePoint   | {}                 |
      | nodeVariantSelectionStrategy | "allVariants"      |
    When I am on URL "/nody"
    Then the matched node should be "nody-mc-nodeface" in dimension "{}"

    # Inject a SECOND SubtreeWasUntagged event directly on live, bypassing the guard.
    # This simulates the production scenario where a duplicate event reached live.
    # Without the fix, this causes: disabled = 0 - 1 → UNSIGNED underflow crash.
    And the event SubtreeWasUntagged was published with payload:
      | Key                          | Value              |
      | workspaceName                | "live"             |
      | contentStreamId              | "cs-identifier"    |
      | nodeAggregateId              | "nody-mc-nodeface" |
      | affectedDimensionSpacePoints | [{}]               |
      | tag                          | "disabled"         |
    Then catching up projections leads to no errors

    # The projection must NOT crash and the node must still be accessible
    When I am on URL "/nody"
    Then the matched node should be "nody-mc-nodeface" in dimension "{}"

  Scenario: Disable leaf node and create sibling with same uri path segment
    When I am on URL "/david-nodenborough/earl-document/leaf"
    Then the matched node should be "leaf-mc-node" in dimension "{}"
    And The node "leaf-mc-node" in dimension "{}" should resolve to URL "/david-nodenborough/earl-document/leaf"

    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "leaf-mc-node" |
      | coveredDimensionSpacePoint   | {}                 |
      | nodeVariantSelectionStrategy | "allVariants"      |
    Then No node should match URL "/david-nodenborough/earl-document/leaf"
    # uri building is ambiguous but not matching!
    And The node "leaf-mc-node" in dimension "{}" should resolve to URL "/david-nodenborough/earl-document/leaf"

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
