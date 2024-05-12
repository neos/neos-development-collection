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
    And the graph projection is fully up to date

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

    And The documenturipath projection is up to date

  Scenario: Disable leaf node
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value           |
      | nodeAggregateId              | "leaf-mc-node"  |
      | coveredDimensionSpacePoint   | {}              |
      | nodeVariantSelectionStrategy | "allVariants"   |
    And The documenturipath projection is up to date
    Then No node should match URL "/david-nodenborough/earl-document/leaf"
    And The node "leaf-mc-node" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/earl-document/leaf"

  Scenario: Disable node with child nodes
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And The documenturipath projection is up to date
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough"
    And The node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/earl-document"

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
    And the graph projection is fully up to date
    And The documenturipath projection is up to date
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough"
    And The node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/earl-document"
    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date
    When I am on URL "/david-nodenborough"
    Then the matched node should be "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough"
    And The node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/earl-document"

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
    And the graph projection is fully up to date
    And The documenturipath projection is up to date
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough"
    And The node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/earl-document"
    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "earl-o-documentbourgh" |
      | coveredDimensionSpacePoint   | {}                      |
      | nodeVariantSelectionStrategy | "allVariants"           |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough"
    And The node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/earl-document"

  Scenario: Disable the same node twice
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the event SubtreeWasTagged was published with payload:
      | Key                          | Value                    |
      | contentStreamId              | "cs-identifier"         |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [{}]                     |
      | tag                          | "disabled"               |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough"
    And The node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/earl-document"
    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date
    When I am on URL "/david-nodenborough"
    Then the matched node should be "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}"
    And The node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough"
    And The node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/earl-document"

  Scenario: Re-enable the same node twice
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the event SubtreeWasTagged was published with payload:
      | Key                          | Value                   |
      | contentStreamId              | "cs-identifier"         |
      | nodeAggregateId              | "earl-o-documentbourgh" |
      | affectedDimensionSpacePoints | [{}]                    |
      | tag                          | "disabled"              |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough"
    And The node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/earl-document"
    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the event SubtreeWasUntagged was published with payload:
      | Key                          | Value                    |
      | contentStreamId              | "cs-identifier"          |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [{}]                     |
      | tag                          | "disabled"               |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date
    When I am on URL "/david-nodenborough"
    Then the matched node should be "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}"
    And The node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough"
    And The node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/earl-document"

  Scenario: Move implicit disabled node
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the graph projection is fully up to date
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                   |
      | nodeAggregateId                     | "earl-o-documentbourgh" |
      | dimensionSpacePoint                 | {}                      |
      | newParentNodeAggregateId            | "nody-mc-nodeface"      |
      | newSucceedingSiblingNodeAggregateId | null                    |
    And The documenturipath projection is up to date
    When I am on URL "/nody/earl-document"
    Then the matched node should be "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}"

  Scenario: Move explicit disabled node
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "earl-o-documentbourgh" |
      | coveredDimensionSpacePoint   | {}                      |
      | nodeVariantSelectionStrategy | "allVariants"           |
    And the graph projection is fully up to date
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                   |
      | nodeAggregateId                     | "earl-o-documentbourgh" |
      | dimensionSpacePoint                 | {}                      |
      | newParentNodeAggregateId            | "nody-mc-nodeface"      |
      | newSucceedingSiblingNodeAggregateId | null                    |
    And The documenturipath projection is up to date
    Then No node should match URL "/nody/earl-document"
    And The node "leaf-mc-node" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/nody/earl-document/leaf"

  Scenario: Add child node underneath disabled node and re-enable parent (see https://github.com/neos/neos-development-collection/issues/4639)
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "nody-mc-nodeface" |
      | coveredDimensionSpacePoint   | {}                 |
      | nodeVariantSelectionStrategy | "allVariants"      |
    And the graph projection is fully up to date
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | parentNodeAggregateId | nodeTypeName                | initialPropertyValues            |
      | nody-mc-nodeface-child | nody-mc-nodeface      | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "nody-child"} |
    And The documenturipath projection is up to date
    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "nody-mc-nodeface" |
      | coveredDimensionSpacePoint   | {}                 |
      | nodeVariantSelectionStrategy | "allVariants"      |
    When I am on URL "/nody/nody-child"
    Then the matched node should be "nody-mc-nodeface-child" in content stream "cs-identifier" and dimension "{}"
