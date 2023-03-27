@fixtures @contentrepository
# Note: For the routing tests to work we rely on Configuration/Testing/Behat/NodeTypes.Test.Routing.yaml
Feature: Routing behavior of removed, disabled and re-enabled nodes

  Background:
    Given I have no content dimensions
    And I am user identified by "initiating-user-identifier"
    And I have the following NodeTypes configuration:
    """
    'Neos.Neos:Sites': {}
    'Neos.Neos:Document': {}
    'Neos.Neos:Content': {}
    'Neos.EventSourcedNeosAdjustments:Test.Routing.Page':
      superTypes:
        'Neos.Neos:Document': true
      properties:
        uriPathSegment:
          type: string
    'Neos.EventSourcedNeosAdjustments:Test.Routing.Content':
      superTypes:
        'Neos.Neos:Content': true
      properties:
        uriPathSegment:
          type: string
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                    |
      | contentStreamId             | "cs-identifier"          |
      | nodeAggregateId             | "lady-eleonode-rootford" |
      | nodeTypeName                | "Neos.Neos:Sites"        |
      | coveredDimensionSpacePoints | [{}]                     |
      | nodeAggregateClassification | "root"                   |
    And the graph projection is fully up to date

    # lady-eleonode-rootford
    #   shernode-homes
    #      sir-david-nodenborough
    #        duke-of-contentshire (content node)
    #        earl-o-documentbourgh
    #          leaf-mc-node
    #      nody-mc-nodeface
    #
    And I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | parentNodeAggregateId  | nodeTypeName                                          | initialPropertyValues                    | nodeName |
      | shernode-homes         | lady-eleonode-rootford | Neos.EventSourcedNeosAdjustments:Test.Routing.Page    | {"uriPathSegment": "ignore-me"}          | node1    |
      | sir-david-nodenborough | shernode-homes         | Neos.EventSourcedNeosAdjustments:Test.Routing.Page    | {"uriPathSegment": "david-nodenborough"} | node2    |
      | duke-of-contentshire   | sir-david-nodenborough | Neos.EventSourcedNeosAdjustments:Test.Routing.Content | {"uriPathSegment": "ignore-me"}          | node3    |
      | earl-o-documentbourgh  | sir-david-nodenborough | Neos.EventSourcedNeosAdjustments:Test.Routing.Page    | {"uriPathSegment": "earl-document"}      | node4    |
      | leaf-mc-node           | earl-o-documentbourgh  | Neos.EventSourcedNeosAdjustments:Test.Routing.Page    | {"uriPathSegment": "leaf"}               | node5    |
      | nody-mc-nodeface       | shernode-homes         | Neos.EventSourcedNeosAdjustments:Test.Routing.Page    | {"uriPathSegment": "nody"}               | node6    |
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

  Scenario: Disable leaf node
    When the command "DisableNodeAggregate" is executed with payload:
      | Key                          | Value           |
      | contentStreamId              | "cs-identifier" |
      | nodeAggregateId              | "leaf-mc-node"  |
      | coveredDimensionSpacePoint   | {}              |
      | nodeVariantSelectionStrategy | "allVariants"   |
    And The documenturipath projection is up to date
    Then No node should match URL "/david-nodenborough/earl-document/leaf"
    And The node "leaf-mc-node" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL

  Scenario: Disable node with child nodes
    When the command "DisableNodeAggregate" is executed with payload:
      | Key                          | Value                    |
      | contentStreamId              | "cs-identifier"          |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And The documenturipath projection is up to date
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL
    And The node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL

  Scenario: Disable two nodes, re-enable the higher one
    When the command "DisableNodeAggregate" is executed with payload:
      | Key                          | Value                    |
      | contentStreamId              | "cs-identifier"          |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the command "DisableNodeAggregate" is executed with payload:
      | Key                          | Value                   |
      | contentStreamId              | "cs-identifier"         |
      | nodeAggregateId              | "earl-o-documentbourgh" |
      | coveredDimensionSpacePoint   | {}                      |
      | nodeVariantSelectionStrategy | "allVariants"           |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL
    And The node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL
    When the command "EnableNodeAggregate" is executed with payload:
      | Key                          | Value                    |
      | contentStreamId              | "cs-identifier"          |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date
    When I am on URL "/david-nodenborough"
    Then the matched node should be "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}"
    And No node should match URL "/david-nodenborough/earl-document"
    And the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough"
    And The node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL

  Scenario: Disable two nodes, re-enable the lower one
    When the command "DisableNodeAggregate" is executed with payload:
      | Key                          | Value                    |
      | contentStreamId              | "cs-identifier"          |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the command "DisableNodeAggregate" is executed with payload:
      | Key                          | Value                   |
      | contentStreamId              | "cs-identifier"         |
      | nodeAggregateId              | "earl-o-documentbourgh" |
      | coveredDimensionSpacePoint   | {}                      |
      | nodeVariantSelectionStrategy | "allVariants"           |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL
    And The node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL
    When the command "EnableNodeAggregate" is executed with payload:
      | Key                          | Value                   |
      | contentStreamId              | "cs-identifier"         |
      | nodeAggregateId              | "earl-o-documentbourgh" |
      | coveredDimensionSpacePoint   | {}                      |
      | nodeVariantSelectionStrategy | "allVariants"           |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL
    And The node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL

  Scenario: Disable the same node twice
    When the command "DisableNodeAggregate" is executed with payload:
      | Key                          | Value                    |
      | contentStreamId              | "cs-identifier"          |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                    |
      | contentStreamId              | "cs-identifier"          |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [{}]                     |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL
    And The node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL
    When the command "EnableNodeAggregate" is executed with payload:
      | Key                          | Value                    |
      | contentStreamId              | "cs-identifier"          |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date
    When I am on URL "/david-nodenborough"
    Then the matched node should be "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}"
    And the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough"
    And the node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough/earl-document"

  Scenario: Re-enable the same node twice
    When the command "DisableNodeAggregate" is executed with payload:
      | Key                          | Value                    |
      | contentStreamId              | "cs-identifier"          |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                   |
      | contentStreamId              | "cs-identifier"         |
      | nodeAggregateId              | "earl-o-documentbourgh" |
      | affectedDimensionSpacePoints | [{}]                    |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date
    Then No node should match URL "/david-nodenborough"
    And No node should match URL "/david-nodenborough/earl-document"
    And The node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL
    And The node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL
    When the command "EnableNodeAggregate" is executed with payload:
      | Key                          | Value                    |
      | contentStreamId              | "cs-identifier"          |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the event NodeAggregateWasEnabled was published with payload:
      | Key                          | Value                    |
      | contentStreamId              | "cs-identifier"          |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [{}]                     |
    And the graph projection is fully up to date
    And The documenturipath projection is up to date
    When I am on URL "/david-nodenborough"
    Then the matched node should be "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}"
    And the node "sir-david-nodenborough" in content stream "cs-identifier" and dimension "{}" should resolve to URL "/david-nodenborough"
    And The node "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL

  Scenario: Move implicit disabled node
    When the command "DisableNodeAggregate" is executed with payload:
      | Key                          | Value                    |
      | contentStreamId              | "cs-identifier"          |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the graph projection is fully up to date
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                   |
      | contentStreamId                     | "cs-identifier"         |
      | nodeAggregateId                     | "earl-o-documentbourgh" |
      | dimensionSpacePoint                 | {}                      |
      | newParentNodeAggregateId            | "nody-mc-nodeface"      |
      | newSucceedingSiblingNodeAggregateId | null                    |
    And The documenturipath projection is up to date
    When I am on URL "/nody/earl-document"
    Then the matched node should be "earl-o-documentbourgh" in content stream "cs-identifier" and dimension "{}"

  Scenario: Move explicit disabled node
    When the command "DisableNodeAggregate" is executed with payload:
      | Key                          | Value                   |
      | contentStreamId              | "cs-identifier"         |
      | nodeAggregateId              | "earl-o-documentbourgh" |
      | coveredDimensionSpacePoint   | {}                      |
      | nodeVariantSelectionStrategy | "allVariants"           |
    And the graph projection is fully up to date
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                   |
      | contentStreamId                     | "cs-identifier"         |
      | nodeAggregateId                     | "earl-o-documentbourgh" |
      | dimensionSpacePoint                 | {}                      |
      | newParentNodeAggregateId            | "nody-mc-nodeface"      |
      | newSucceedingSiblingNodeAggregateId | null                    |
    And The documenturipath projection is up to date
    Then No node should match URL "/nody/earl-document"
    And The node "leaf-mc-node" in content stream "cs-identifier" and dimension "{}" should not resolve to an URL
