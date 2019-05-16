@fixtures
Feature: Reading of our Graph Projection

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    """

  Scenario: Single node connected to root
    Given the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                  |
      | contentStreamIdentifier     | "cs-identifier"                        |
      | nodeAggregateIdentifier     | "sir-david-nodenborough"               |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
      | coveredDimensionSpacePoints | [{}]                                   |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000" |
      | nodeAggregateClassification | "root"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                  |
      | nodeName                      | "foo"                                     |
      | nodeAggregateClassification   | "regular"                                 |

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 2 nodes
    And I expect a node with identifier {"nodeAggregateIdentifier": "sir-david-nodenborough", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"nodeAggregateIdentifier": "nody-mc-nodeface", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}} to exist in the content graph

    When I am in content stream "cs-identifier" and Dimension Space Point {}
    Then I expect the subgraph projection to consist of exactly 2 nodes
    And I expect node aggregate identifier "sir-david-nodenborough" and path "" to lead to node {"nodeAggregateIdentifier": "sir-david-nodenborough", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "foo" to lead to node {"nodeAggregateIdentifier": "nody-mc-nodeface", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}}
