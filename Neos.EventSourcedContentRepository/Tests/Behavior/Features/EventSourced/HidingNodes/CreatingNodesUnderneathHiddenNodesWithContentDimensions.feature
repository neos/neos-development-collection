@fixtures
Feature: Creation of nodes underneath hidden nodes WITH content dimensions

  If we create new nodes belonging to an aggregate, underneath of hidden nodes, they must be marked as "hidden" as well; i.e. they
  must have the proper restriction edges as well.

  Node structure created in BG:
  - rn-identifier (root node)
  -- na-identifier (name=text1) [de, en] <== HIDDEN!
  --- cna-identifier (name=text2) [de]

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values | Generalizations |
      | language   | de      | de, en |                 |
    And the command CreateWorkspace is executed with payload:
      | Key                     | Value           |
      | workspaceName           | "live"          |
      | contentStreamIdentifier | "cs-identifier" |
      | rootNodeIdentifier      | "rn-identifier" |
    And I have the following NodeTypes configuration:
    """
    Neos.ContentRepository:Root: {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And the graph projection is fully up to date
    When the command "CreateNodeAggregateWithNode" is executed with payload:
      | Key                     | Value                                    |
      | contentStreamIdentifier | "cs-identifier"                          |
      | nodeAggregateIdentifier | "na-identifier"                          |
      | dimensionSpacePoint     | {"language": "de"}                       |
      | nodeTypeName            | "Neos.ContentRepository.Testing:Content" |
      | nodeIdentifier          | "node-identifier"                        |
      | parentNodeIdentifier    | "rn-identifier"                          |
      | nodeName                | "text1"                                  |
    And the graph projection is fully up to date
    And the command "CreateNodeAggregateWithNode" is executed with payload:
      | Key                     | Value                                    |
      | contentStreamIdentifier | "cs-identifier"                          |
      | nodeAggregateIdentifier | "cna-identifier"                         |
      | dimensionSpacePoint     | {"language": "de"}                       |
      | nodeTypeName            | "Neos.ContentRepository.Testing:Content" |
      | nodeIdentifier          | "cnode-identifier"                       |
      | parentNodeIdentifier    | "node-identifier"                        |
      | nodeName                | "text2"                                  |
    And the graph projection is fully up to date
    And the command "AddNodeToAggregate" is executed with payload:
      | Key                     | Value              |
      | contentStreamIdentifier | "cs-identifier"    |
      | nodeAggregateIdentifier | "na-identifier"    |
      | dimensionSpacePoint     | {"language": "en"} |
      | nodeIdentifier          | "node2-identifier" |
      | parentNodeIdentifier    | "rn-identifier"    |
      | nodeName                | "text1"            |
    And the graph projection is fully up to date
    And the command "HideNode" is executed with payload:
      | Key                          | Value                                    |
      | contentStreamIdentifier      | "cs-identifier"                          |
      | nodeAggregateIdentifier      | "na-identifier"                          |
      | affectedDimensionSpacePoints | [{"language": "de"}, {"language": "en"}] |
    And the graph projection is fully up to date

  Scenario: When a new node is added to an already existing aggregate underneath a hidden node, this one should be hidden as well
    When the command "AddNodeToAggregate" is executed with payload:
      | Key                     | Value               |
      | contentStreamIdentifier | "cs-identifier"     |
      | nodeAggregateIdentifier | "cna-identifier"    |
      | dimensionSpacePoint     | {"language": "en"}  |
      | nodeIdentifier          | "cnode2-identifier" |
      | parentNodeIdentifier    | "node2-identifier"  |
      | nodeName                | "text2"             |

    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and Dimension Space Point {"language": "en"}
    Then I expect a node identified by aggregate identifier "cna-identifier" not to exist in the subgraph


  Scenario: When a node is removed from an aggregate, the hidden flag should be cleared as well. We test this by recreating the node the hidden flag is ORIGINATING from; and expecting it to be *visible*
    When the command "AddNodeToAggregate" is executed with payload:
      | Key                     | Value               |
      | contentStreamIdentifier | "cs-identifier"     |
      | nodeAggregateIdentifier | "cna-identifier"    |
      | dimensionSpacePoint     | {"language": "en"}  |
      | nodeIdentifier          | "cnode2-identifier" |
      | parentNodeIdentifier    | "rn-identifier"     |
      | nodeName                | "text2"             |

    And the command RemoveNodesFromAggregate was published with payload:
      | Key                     | Value               |
      | contentStreamIdentifier | "cs-identifier"     |
      | nodeAggregateIdentifier | "na-identifier"     |
      | dimensionSpacePointSet  | [{"language":"en"}] |

    And the command "AddNodeToAggregate" is executed with payload:
      | Key                     | Value               |
      | contentStreamIdentifier | "cs-identifier"     |
      | nodeAggregateIdentifier | "na-identifier"     |
      | dimensionSpacePoint     | {"language": "en"}  |
      | nodeIdentifier          | "cnode2-identifier" |
      | parentNodeIdentifier    | "rn-identifier"     |
      | nodeName                | "text1"             |

    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and Dimension Space Point {"language": "en"}
    Then I expect a node identified by aggregate identifier "na-identifier" to exist in the subgraph


  Scenario: When a node is removed from an aggregate, the hidden flag should be cleared as well. We test this by recreating a node UNDERNEATH a hidden node; and expecting it to be *hidden* (because it inherits again the permissions from the parent) - but the test has to run through!
    When the command "AddNodeToAggregate" is executed with payload:
      | Key                     | Value               |
      | contentStreamIdentifier | "cs-identifier"     |
      | nodeAggregateIdentifier | "cna-identifier"    |
      | dimensionSpacePoint     | {"language": "en"}  |
      | nodeIdentifier          | "cnode2-identifier" |
      | parentNodeIdentifier    | "node2-identifier"  |
      | nodeName                | "text2"             |

    And the graph projection is fully up to date

    And the command RemoveNodesFromAggregate was published with payload:
      | Key                     | Value               |
      | contentStreamIdentifier | "cs-identifier"      |
      | nodeAggregateIdentifier | "cna-identifier"    |
      | dimensionSpacePointSet  | [{"language":"en"}] |

    And the graph projection is fully up to date

    And the command "AddNodeToAggregate" is executed with payload:
      | Key                     | Value               |
      | contentStreamIdentifier | "cs-identifier"     |
      | nodeAggregateIdentifier | "cna-identifier"    |
      | dimensionSpacePoint     | {"language": "en"}  |
      | nodeIdentifier          | "cnode2-identifier" |
      | parentNodeIdentifier    | "node2-identifier"  |
      | nodeName                | "text2"             |

    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and Dimension Space Point {"language": "en"}
    Then I expect a node identified by aggregate identifier "cna-identifier" not to exist in the subgraph

    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect a node identified by aggregate identifier "cna-identifier" to exist in the subgraph
