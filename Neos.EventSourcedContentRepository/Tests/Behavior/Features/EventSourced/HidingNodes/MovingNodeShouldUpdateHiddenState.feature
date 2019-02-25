@fixtures
Feature: Moving a node should update the hidden state accordingly.

  This is actually two cases:

  - moving a node INTO a parent node which is hidden -> node should automatically be hidden.
  - moving a node OUT OF a parent node which is hidden -> node should automatically be visible.

  Node structure is as follows:
  - rn-identifier (root node)
  -- na-identifier (name=text1) <== HIDDEN!
  -- nb-identifier (name=foo)

  Background:
    Given I have no content dimensions
    And the command CreateWorkspace is executed with payload:
      | Key                     | Value           |
      | workspaceName           | "live"          |
      | contentStreamIdentifier | "cs-identifier" |
      | rootNodeIdentifier      | "rn-identifier" |
    And I have the following NodeTypes configuration:
    """
    Neos.ContentRepository:Root: {}
    'Neos.ContentRepository.Testing:Content': {}
    """
    And the graph projection is fully up to date
    And the command "CreateNodeAggregateWithNode" is executed with payload:
      | Key                     | Value                                    |
      | contentStreamIdentifier | "cs-identifier"                          |
      | nodeAggregateIdentifier | "na-identifier"                          |
      | dimensionSpacePoint     | {}                                       |
      | nodeTypeName            | "Neos.ContentRepository.Testing:Content" |
      | nodeIdentifier          | "node-identifier"                        |
      | parentNodeIdentifier    | "rn-identifier"                          |
      | nodeName                | "text1"                                  |
    And the command "CreateNodeAggregateWithNode" is executed with payload:
      | Key                     | Value                                    |
      | contentStreamIdentifier | "cs-identifier"                          |
      | nodeAggregateIdentifier | "nb-identifier"                          |
      | dimensionSpacePoint     | {}                                       |
      | nodeTypeName            | "Neos.ContentRepository.Testing:Content" |
      | nodeIdentifier          | "nodeb-identifier"                       |
      | parentNodeIdentifier    | "rn-identifier"                          |
      | nodeName                | "foo"                                    |

    And the graph projection is fully up to date

    And the command "HideNode" is executed with payload:
      | Key                          | Value           |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "na-identifier" |
      | affectedDimensionSpacePoints | [{}]            |

  Scenario: Moving a node INTO a parent node which is hidden should automatically hide the node.
    Given the command "CreateNodeAggregateWithNode" is executed with payload:
      | Key                     | Value                                    |
      | contentStreamIdentifier | "cs-identifier"                          |
      | nodeAggregateIdentifier | "na2-identifier"                         |
      | dimensionSpacePoint     | {}                                       |
      | nodeTypeName            | "Neos.ContentRepository.Testing:Content" |
      | nodeIdentifier          | "node2-identifier"                       |
      | parentNodeIdentifier    | "rn-identifier"                          |
      | nodeName                | "text2"                                  |

    And the graph projection is fully up to date

    When the command "MoveNode" is executed with payload:
      | Key                                         | Value            |
      | contentStreamIdentifier                     | "cs-identifier"  |
      | dimensionSpacePoint                         | {}               |
      | nodeAggregateIdentifier                     | "na2-identifier" |
      | newParentNodeAggregateIdentifier            | "na-identifier"  |
      | newSucceedingSiblingNodeAggregateIdentifier | null             |
      | relationDistributionStrategy                | "scatter"        |

    And the graph projection is fully up to date
    When I am in content stream "cs-identifier" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "na2-identifier" not to exist in the subgraph

    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect a node identified by aggregate identifier "na2-identifier" to exist in the subgraph
    Then I expect the path "/text1/text2" to lead to the node "node2-identifier"

  Scenario: Moving a node OUT OF a parent node which is hidden should automatically UN-HIDE the node.
    Given the command "CreateNodeAggregateWithNode" is executed with payload:
      | Key                     | Value                                    |
      | contentStreamIdentifier | "cs-identifier"                          |
      | nodeAggregateIdentifier | "na2-identifier"                         |
      | dimensionSpacePoint     | {}                                       |
      | nodeTypeName            | "Neos.ContentRepository.Testing:Content" |
      | nodeIdentifier          | "node2-identifier"                       |
      | parentNodeIdentifier    | "node-identifier"                        |
      | nodeName                | "text2"                                  |

    And the graph projection is fully up to date

    When the command "MoveNode" is executed with payload:
      | Key                                         | Value            |
      | contentStreamIdentifier                     | "cs-identifier"  |
      | dimensionSpacePoint                         | {}               |
      | nodeAggregateIdentifier                     | "na2-identifier" |
      | newParentNodeAggregateIdentifier            | "nb-identifier"  |
      | newSucceedingSiblingNodeAggregateIdentifier | null             |
      | relationDistributionStrategy                | "scatter"        |

    And the graph projection is fully up to date
    When I am in content stream "cs-identifier" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "na2-identifier" to exist in the subgraph
    Then I expect the path "/foo/text2" to lead to the node "node2-identifier"

