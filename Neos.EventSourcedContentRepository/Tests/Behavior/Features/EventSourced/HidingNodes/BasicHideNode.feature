@fixtures
Feature: Hide Node

  Hiding a node works.
  Node structure is as follows:
  - rn-identifier (root node)
  -- na-identifier (name=text1) <== HIDDEN!
  --- cna-identifier (name=text2)
  --- refna-identifier (name=ref) <== a Reference node with a reference to "cna-identifier"

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
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    'Neos.ContentRepository.Testing:NodeWithReference':
      properties:
        ref:
          type: reference
    """
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                  | Type |
      | contentStreamIdentifier | cs-identifier                          | Uuid |
      | nodeAggregateIdentifier | na-identifier                          | Uuid |
      | nodeTypeName            | Neos.ContentRepository.Testing:Content |      |
      | nodeIdentifier          | node-identifier                        | Uuid |
      | parentNodeIdentifier    | rn-identifier                          | Uuid |
      | nodeName                | text1                                  |      |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                    |
      | contentStreamIdentifier | "cs-identifier"                          |
      | nodeAggregateIdentifier | "na-identifier"                          |
      | nodeTypeName            | "Neos.ContentRepository.Testing:Content" |
      | nodeIdentifier          | "node-identifier"                        |
      | parentNodeIdentifier    | "rn-identifier"                          |
      | nodeName                | "text1"                                  |

    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                  | Type |
      | contentStreamIdentifier | cs-identifier                          | Uuid |
      | nodeAggregateIdentifier | cna-identifier                         | Uuid |
      | nodeTypeName            | Neos.ContentRepository.Testing:Content |      |
      | nodeIdentifier          | cnode-identifier                       | Uuid |
      | parentNodeIdentifier    | node-identifier                        | Uuid |
      | nodeName                | text2                                  |      |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                    |
      | contentStreamIdentifier | "cs-identifier"                          |
      | nodeAggregateIdentifier | "cna-identifier"                         |
      | nodeTypeName            | "Neos.ContentRepository.Testing:Content" |
      | nodeIdentifier          | "cnode-identifier"                       |
      | parentNodeIdentifier    | "node-identifier"                        |
      | nodeName                | "text2"                                  |

    # create the "ref" node
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                              |
      | contentStreamIdentifier | "cs-identifier"                                    |
      | nodeAggregateIdentifier | "refna-identifier"                                 |
      | nodeTypeName            | "Neos.ContentRepository.Testing:NodeWithReference" |
      | nodeIdentifier          | "refnode-identifier"                               |
      | parentNodeIdentifier    | "node-identifier"                                  |
      | nodeName                | "ref"                                              |
    And the graph projection is fully up to date
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                            | Type |
      | contentStreamIdentifier | cs-identifier                                    | Uuid |
      | nodeAggregateIdentifier | refna-identifier                                 | Uuid |
      | nodeTypeName            | Neos.ContentRepository.Testing:NodeWithReference |      |
      | nodeIdentifier          | refnode-identifier                               | Uuid |
      | parentNodeIdentifier    | node-identifier                                  | Uuid |
      | nodeName                | ref                                              |      |
    And the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                |
      | contentStreamIdentifier             | "cs-identifier"      |
      | nodeIdentifier                      | "refnode-identifier" |
      | propertyName                        | "ref"                |
      | destinationNodeAggregateIdentifiers | ["cna-identifier"]   |
    And the graph projection is fully up to date

  Scenario: Hide a node generates the correct events
    When the command "HideNode" is executed with payload:
      | Key                          | Value           |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "na-identifier" |
      | affectedDimensionSpacePoints | [{}]            |

    Then I expect exactly 7 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 6 is of type "Neos.EventSourcedContentRepository:NodeWasHidden" with payload:
      | Key                          | Expected        |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "na-identifier" |
      | affectedDimensionSpacePoints | [[]]            |

  Scenario: Hiding a node means it is invisible with the various traversal methods
    When the command "HideNode" is executed with payload:
      | Key                          | Value           |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "na-identifier" |
      | affectedDimensionSpacePoints | [{}]            |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    # findNodeByNodeAggregateIdentifier
    Then I expect a node identified by aggregate identifier "na-identifier" not to exist in the subgraph
    # findNodeByIdentifier
    Then I expect a node "node-identifier" not to exist in the graph projection
    # findChildNodes
    # countChildNodes
    Then I expect the node aggregate "root" to have the following child nodes:
      | Name | NodeIdentifier |
      # no child nodes as they are hidden
    # findParentNode
    When I go to the parent node of node aggregate "cna-identifier"
    Then I do not find any node
    # traverseHierarchy is covered by "findChildNodes" and "findParentNode"
    # findParentNodeByNodeAggregateIdentifier
    When I go to the parent node of node aggregate "cna-identifier"
    Then I do not find any node
    # findNodeByPath
    # findChildNodeConnectedThroughEdgeName
    Then I expect the path "/text1" to lead to no node
    Then I expect the path "/text1/text2" to lead to no node
    # findReferencedNodes
    Then I expect the Node aggregate "refna-identifier" to have the references:
      | Key | Value |
      | ref | []    |
    # findSubtree
    Then the subtree for node aggregate "na-identifier" with node types "" and 5 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | root                    |

    ######################################################
    # Second part of Scenario: No visibility restrictions
    ######################################################
    When VisibilityConstraints are set to "withoutRestrictions"

    # findNodeByNodeAggregateIdentifier
    Then I expect a node identified by aggregate identifier "na-identifier" to exist in the subgraph
    # findNodeByIdentifier
    Then I expect a node "node-identifier" to exist in the graph projection
    # findChildNodes
    # countChildNodes
    Then I expect the node aggregate "root" to have the following child nodes:
      | Name  | NodeIdentifier  |
      | text1 | node-identifier |
    # findParentNode
    When I go to the parent node of node aggregate "cna-identifier"
    Then I find a node with node aggregate "na-identifier"
    # traverseHierarchy is covered by "findChildNodes" and "findParentNode"
    # findParentNodeByNodeAggregateIdentifier
    When I go to the parent node of node aggregate "cna-identifier"
    Then I find a node with node aggregate "na-identifier"
    # findNodeByPath
    # findChildNodeConnectedThroughEdgeName
    Then I expect the path "/text1" to lead to the node "node-identifier"
    Then I expect the path "/text1/text2" to lead to the node "cnode-identifier"
    # findReferencedNodes
    Then I expect the Node aggregate "refna-identifier" to have the references:
      | Key | Value              |
      | ref | ["cna-identifier"] |
    # findSubtree
    Then the subtree for node aggregate "na-identifier" with node types "" and 5 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | root                    |
      | 0     | na-identifier           |
      | 1     | cna-identifier          |
      | 1     | refna-identifier        |

    # TODO: findChildNodeByNodeAggregateIdentifierConnectedThroughEdgeName

    # TODO: findSiblings
    # TODO: findSucceedingSiblings
    # TODO: findPrecedingSiblings

  Scenario: Hiding a node means it is invisible with the various traversal methods (findReferencingNodes)
    When the command "HideNode" is executed with payload:
      | Key                          | Value              |
      | contentStreamIdentifier      | "cs-identifier"    |
      | nodeAggregateIdentifier      | "refna-identifier" |
      | affectedDimensionSpacePoints | [{}]               |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}

    # findReferencingNodes
    Then I expect the Node aggregate "cna-identifier" to be referenced by:
      | Key | Value |
      | ref | []    |

    ######################################################
    # Second part of Scenario: No visibility restrictions
    ######################################################
    When VisibilityConstraints are set to "withoutRestrictions"

    # findReferencingNodes
    Then I expect the Node aggregate "cna-identifier" to be referenced by:
      | Key | Value                |
      | ref | ["refna-identifier"] |


  Scenario: Hide a non-existing node should throw an exception
    When the command "HideNode" is executed with payload and exceptions are caught:
      | Key                          | Value                |
      | contentStreamIdentifier      | "cs-identifier"      |
      | nodeAggregateIdentifier      | "unknown-identifier" |
      | affectedDimensionSpacePoints | [{}]                 |
    Then the last command should have thrown an exception of type "NodeNotFoundException"

  Scenario: Hide a non-existing node in a certain dimension should throw an exception
    When the command "HideNode" is executed with payload and exceptions are caught:
      | Key                          | Value                |
      | contentStreamIdentifier      | "cs-identifier"      |
      | nodeAggregateIdentifier      | "na-identifier"      |
      | affectedDimensionSpacePoints | [{"language": "de"}] |
    Then the last command should have thrown an exception of type "NodeNotFoundException"

  Scenario: Showing a previously-hidden node works
    When the command "HideNode" is executed with payload:
      | Key                          | Value           |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "na-identifier" |
      | affectedDimensionSpacePoints | [{}]            |

    And the graph projection is fully up to date

    When the command "ShowNode" is executed with payload:
      | Key                          | Value           |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "na-identifier" |
      | affectedDimensionSpacePoints | [{}]            |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "na-identifier" to exist in the subgraph


  Scenario: Removing a previously-hidden node clears the hidden restriction
    When the command "HideNode" is executed with payload:
      | Key                          | Value           |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "na-identifier" |
      | affectedDimensionSpacePoints | [{}]            |

    And the graph projection is fully up to date

    And the command RemoveNodeAggregate was published with payload:
      | Key                     | Value           |
      | contentStreamIdentifier | "cs-identifier" |
      | nodeAggregateIdentifier | "na-identifier" |

  # TODO: Creating, Removing, Creating a NodeAggregate should actually WORK and not throw an error!!!

#    # we recreate the node again; and it should not be hidden now.
#    When the command "CreateNodeAggregateWithNode" is executed with payload:
#      | Key                     | Value                                    |
#      | contentStreamIdentifier | "cs-identifier"                          |
#      | nodeAggregateIdentifier | "na-identifier"                          |
#      | dimensionSpacePoint     | {}                                       |
#      | nodeTypeName            | "Neos.ContentRepository.Testing:Content" |
#      | nodeIdentifier          | "node-identifier"                        |
#      | parentNodeIdentifier    | "rn-identifier"                          |
#      | nodeName                | "text1"                                  |
#
#    And the graph projection is fully up to date
#
#    When I am in the active content stream of workspace "live" and Dimension Space Point {}
#    Then I expect a node identified by aggregate identifier "na-identifier" to exist in the subgraph
