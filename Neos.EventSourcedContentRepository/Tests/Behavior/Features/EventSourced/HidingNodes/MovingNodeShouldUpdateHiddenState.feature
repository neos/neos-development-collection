@fixtures
Feature: Moving a node should update the hidden state accordingly.

  This is actually two cases:

  - moving a node INTO a parent node which is hidden -> node should automatically be hidden.
  - moving a node OUT OF a parent node which is hidden -> node should automatically be visible.

  Node structure is as follows:
  - rn-identifier (root node)
  -- na-identifier (name=text1) <== HIDDEN!

  Background:
    Given I have no content dimensions
    And the command CreateWorkspace is executed with payload:
      | Key                     | Value         | Type |
      | workspaceName           | live          |      |
      | contentStreamIdentifier | cs-identifier | Uuid |
      | rootNodeIdentifier      | rn-identifier | Uuid |
    And I have the following NodeTypes configuration:
    """
    Neos.ContentRepository:Root: {}
    'Neos.ContentRepository.Testing:Content': {}
    """
    And the command "CreateNodeAggregateWithNode" is executed with payload:
      | Key                     | Value                                  | Type                |
      | contentStreamIdentifier | cs-identifier                          | Uuid                |
      | nodeAggregateIdentifier | na-identifier                          | Uuid                |
      | dimensionSpacePoint     | {}                                     | DimensionSpacePoint |
      | nodeTypeName            | Neos.ContentRepository.Testing:Content |                     |
      | nodeIdentifier          | node-identifier                        | Uuid                |
      | parentNodeIdentifier    | rn-identifier                          | Uuid                |
      | nodeName                | text1                                  |                     |

    And the command "HideNode" is executed with payload:
      | Key                          | Value         | Type |
      | contentStreamIdentifier      | cs-identifier | Uuid |
      | nodeAggregateIdentifier      | na-identifier | Uuid |
      | affectedDimensionSpacePoints | [{}]          | json |

  Scenario: Moving a node INTO a parent node which is hidden should automatically hide the node.
    Given the command "CreateNodeAggregateWithNode" is executed with payload:
      | Key                     | Value                                  | Type                |
      | contentStreamIdentifier | cs-identifier                          | Uuid                |
      | nodeAggregateIdentifier | na2-identifier                         | Uuid                |
      | dimensionSpacePoint     | {}                                     | DimensionSpacePoint |
      | nodeTypeName            | Neos.ContentRepository.Testing:Content |                     |
      | nodeIdentifier          | node2-identifier                       | Uuid                |
      | parentNodeIdentifier    | rn-identifier                          | Uuid                |
      | nodeName                | text2                                  |                     |

    When the command "MoveNode" is executed with payload:
      | Key                                         | Value          | Type                |
      | contentStreamIdentifier                     | cs-identifier  | Uuid                |
      | dimensionSpacePoint                         | {}             | DimensionSpacePoint |
      | nodeAggregateIdentifier                     | na2-identifier | Uuid                |
      | newParentNodeAggregateIdentifier            | na-identifier  | null                |
      | newSucceedingSiblingNodeAggregateIdentifier |                | null                |
      | relationDistributionStrategy                | scatter        |                     |

    And the graph projection is fully up to date
    When I am in content stream "[cs-identifier]" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "[na2-identifier]" not to exist in the subgraph

    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect a node identified by aggregate identifier "[na2-identifier]" to exist in the subgraph
    Then I expect the path "/text1/text2" to lead to the node "[node2-identifier]"

