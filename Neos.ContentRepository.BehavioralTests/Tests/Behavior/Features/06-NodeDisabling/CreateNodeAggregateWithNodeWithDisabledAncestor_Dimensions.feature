@fixtures @adapters=DoctrineDBAL
Feature: Creation of nodes underneath hidden nodes WITH content dimensions

  If we create new nodes belonging to an aggregate, underneath of hidden nodes, they must be marked as "hidden" as well; i.e. they
  must have the proper restriction edges as well.

  Node structure created in BG:
  - rn-identifier (root node)
  -- na-identifier (name=text1) [de, en] <== HIDDEN!
  --- cna-identifier (name=text2) [de]

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values  | Generalizations |
      | language   | de      | de, gsw | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    Neos.ContentRepository:Root: {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "cs-identifier"                        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                    |
      | contentStreamIdentifier     | "cs-identifier"                          |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"                 |
      | nodeTypeName                | "Neos.ContentRepository:Root"            |
      | coveredDimensionSpacePoints | [{"language": "de"},{"language": "gsw"}] |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000"   |
      | nodeAggregateClassification | "root"                                   |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "cs-identifier"                          |
      | nodeAggregateIdentifier       | "the-great-nodini"                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {"language": "de"}                       |
      | coveredDimensionSpacePoints   | [{"language": "de"},{"language": "gsw"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                 |
      | nodeName                      | "text1"                                  |
      | nodeAggregateClassification   | "regular"                                |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "cs-identifier"                          |
      | nodeAggregateIdentifier       | "nodingers-cat"                          |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {"language": "de"}                       |
      | coveredDimensionSpacePoints   | [{"language": "de"},{"language": "gsw"}] |
      | parentNodeAggregateIdentifier | "the-great-nodini"                       |
      | nodeName                      | "text2"                                  |
      | nodeAggregateClassification   | "regular"                                |
    And the event NodeSpecializationVariantWasCreated was published with payload:
      | Key                     | Value                 |
      | contentStreamIdentifier | "cs-identifier"       |
      | nodeAggregateIdentifier | "the-great-nodini"    |
      | sourceOrigin            | {"language": "de"}    |
      | specializationOrigin    | {"language": "gsw"}   |
      | specializationCoverage  | [{"language": "gsw"}] |
    And the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                                     |
      | contentStreamIdentifier      | "cs-identifier"                           |
      | nodeAggregateIdentifier      | "the-great-nodini"                        |
      | affectedDimensionSpacePoints | [{"language": "de"}, {"language": "gsw"}] |
    And the graph projection is fully up to date

  Scenario: When a new node is added to an already existing aggregate underneath a hidden node, this one should be hidden as well
    When the command CreateNodeVariant is executed with payload:
      | Key                      | Value               |
      | contentStreamIdentifier  | "cs-identifier"     |
      | nodeAggregateIdentifier  | "nodingers-cat"     |
      | sourceOrigin             | {"language": "de"}  |
      | targetOrigin             | {"language": "gsw"} |
      | initiatingUserIdentifier | "user"              |
    And the graph projection is fully up to date

  #  When I am in content stream "cs-identifier" and dimension space point {"language": "gsw"}
  #  Then I expect a node identified by aggregate identifier "nodingers-cat" not to exist in the subgraph

  # re-add this later once removing nodes is refactored
  #Scenario: When a node is removed from an aggregate, the hidden flag should be cleared as well. We test this by recreating the node the hidden flag is ORIGINATING from; and expecting it to be *visible*
  #  When the command "AddNodeToAggregate" is executed with payload:
  #    | Key                     | Value               |
  #    | contentStreamIdentifier | "cs-identifier"     |
  #    | nodeAggregateIdentifier | "cna-identifier"    |
  #    | dimensionSpacePoint     | {"language": "gsw"}  |
  #    | nodeIdentifier          | "cnode2-identifier" |
  #    | parentNodeIdentifier    | "rn-identifier"     |
  #    | nodeName                | "text2"             |

  #  And the command RemoveNodesFromAggregate was published with payload:
  #    | Key                     | Value               |
  #    | contentStreamIdentifier | "cs-identifier"     |
  #    | nodeAggregateIdentifier | "na-identifier"     |
  #    | dimensionSpacePointSet  | [{"language":"gsw"}] |

  #  And the command "AddNodeToAggregate" is executed with payload:
  #    | Key                     | Value               |
  #    | contentStreamIdentifier | "cs-identifier"     |
  #    | nodeAggregateIdentifier | "na-identifier"     |
  #    | dimensionSpacePoint     | {"language": "gsw"}  |
  #    | nodeIdentifier          | "cnode2-identifier" |
  #    | parentNodeIdentifier    | "rn-identifier"     |
  #    | nodeName                | "text1"             |

  #  And the graph projection is fully up to date

  #  When I am in content stream "cs-identifier" and dimension space point {"language": "gsw"}
  #  Then I expect a node identified by aggregate identifier "na-identifier" to exist in the subgraph


  #Scenario: When a node is removed from an aggregate, the hidden flag should be cleared as well. We test this by recreating a node UNDERNEATH a hidden node; and expecting it to be *hidden* (because it inherits again the permissions from the parent) - but the test has to run through!
  #  When the command "AddNodeToAggregate" is executed with payload:
  #    | Key                     | Value               |
  #    | contentStreamIdentifier | "cs-identifier"     |
  #    | nodeAggregateIdentifier | "cna-identifier"    |
  #    | dimensionSpacePoint     | {"language": "gsw"}  |
  #    | nodeIdentifier          | "cnode2-identifier" |
  #    | parentNodeIdentifier    | "node2-identifier"  |
  #    | nodeName                | "text2"             |

  #  And the graph projection is fully up to date

  #  And the command RemoveNodesFromAggregate was published with payload:
  #    | Key                     | Value               |
  #    | contentStreamIdentifier | "cs-identifier"      |
  #    | nodeAggregateIdentifier | "cna-identifier"    |
  #    | dimensionSpacePointSet  | [{"language":"gsw"}] |

  #  And the graph projection is fully up to date

  #  And the command "AddNodeToAggregate" is executed with payload:
  #    | Key                     | Value               |
  #    | contentStreamIdentifier | "cs-identifier"     |
  #    | nodeAggregateIdentifier | "cna-identifier"    |
  #    | dimensionSpacePoint     | {"language": "gsw"}  |
  #    | nodeIdentifier          | "cnode2-identifier" |
  #    | parentNodeIdentifier    | "node2-identifier"  |
  #    | nodeName                | "text2"             |

  #  And the graph projection is fully up to date

  #  When I am in content stream "cs-identifier" and dimension space point {"language": "gsw"}
  #  Then I expect a node identified by aggregate identifier "cna-identifier" not to exist in the subgraph

  #  When VisibilityConstraints are set to "withoutRestrictions"
  #  Then I expect a node identified by aggregate identifier "cna-identifier" to exist in the subgraph
