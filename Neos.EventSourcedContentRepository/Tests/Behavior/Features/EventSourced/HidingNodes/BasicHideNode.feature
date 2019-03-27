@fixtures
Feature: Hide Node

  Hiding a node works.
  Node structure is as follows:
  - lady-eleonode-rootford (root node)
  -- text-1 (name=text1) <== HIDDEN!
  --- text-2 (name=text2)
  --- referencing-node (name=ref) <== a Reference node with a reference to "text-2"

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    Neos.ContentRepository:Root: {}
    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    'Neos.ContentRepository.Testing:NodeWithReference':
      properties:
        ref:
          type: reference
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                            | Value                                  |
      | workspaceName                  | "live"                                 |
      | workspaceTitle                 | "Live"                                 |
      | workspaceDescription           | "The live workspace"                   |
      | initiatingUserIdentifier       | "00000000-0000-0000-0000-000000000000" |
      | currentContentStreamIdentifier | "cs-identifier"                        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                  |
      | contentStreamIdentifier       | "cs-identifier"                        |
      | nodeAggregateIdentifier       | "lady-eleonode-rootford"               |
      | nodeTypeName                  | "Neos.ContentRepository:Root"          |
      | visibleInDimensionSpacePoints | [{}]                                   |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000" |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "cs-identifier"                          |
      | nodeAggregateIdentifier       | "text-1"                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | visibleInDimensionSpacePoints | [{}]                                     |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                 |
      | nodeName                      | "text1"                                  |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "cs-identifier"                          |
      | nodeAggregateIdentifier       | "text-2"                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | visibleInDimensionSpacePoints | [{}]                                     |
      | parentNodeAggregateIdentifier | "text-1"                                 |
      | nodeName                      | "text2"                                  |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "cs-identifier"                          |
      | nodeAggregateIdentifier       | "referencing-node"                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | visibleInDimensionSpacePoints | [{}]                                     |
      | parentNodeAggregateIdentifier | "text-1"                                 |
      | nodeName                      | "referencing"                            |
    And the event NodeReferencesWereSet was published with payload:
      | Key                                 | Value              |
      | contentStreamIdentifier             | "cs-identifier"    |
      | sourceNodeAggregateIdentifier       | "referencing-node" |
      | sourceOriginDimensionSpacePoint     | {}                 |
      | destinationNodeAggregateIdentifiers | ["text-2"]         |
      | referenceName                       | "ref"              |
    And the graph projection is fully up to date

  Scenario: Hide a node generates the correct events
    When the command "HideNode" is executed with payload:
      | Key                          | Value           |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "text-1"        |
      | affectedDimensionSpacePoints | [{}]            |

    Then I expect exactly 7 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 6 is of type "Neos.EventSourcedContentRepository:NodeWasHidden" with payload:
      | Key                          | Expected        |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "text-1"        |
      | affectedDimensionSpacePoints | [[]]            |

  Scenario: Hiding a node means it is invisible with the various traversal methods
    When the command "HideNode" is executed with payload:
      | Key                          | Value           |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "text-1"        |
      | affectedDimensionSpacePoints | [{}]            |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    # findNodeByNodeAggregateIdentifier
    Then I expect a node identified by aggregate identifier "text-1" not to exist in the subgraph
    # findNodeByIdentifier: does not support visibility constraints
    # findChildNodes
    # countChildNodes
    Then I expect the node aggregate "lady-eleonode-rootford" to have the following child nodes:
      | Name | NodeIdentifier |
      # no child nodes as they are hidden
    # findParentNode
    When I go to the parent node of node aggregate "text-2"
    Then I do not find any node
    # traverseHierarchy is covered by "findChildNodes" and "findParentNode"
    # findParentNodeByNodeAggregateIdentifier
    When I go to the parent node of node aggregate "text-2"
    Then I do not find any node
    # findNodeByPath
    # findChildNodeConnectedThroughEdgeName
    And I expect the path "/text1" to lead to no node
    And I expect the path "/text1/text2" to lead to no node
    # findReferencedNodes
    And I expect the node aggregate "referencing-node" to have the references:
      | Key | Value |
      | ref | []    |
    # findSubtree
    And the subtree for node aggregate "text-1" with node types "" and 5 levels deep should be:
      | Level | NodeAggregateIdentifier |

  ######################################################
  # Second part of Scenario: No visibility restrictions
  ######################################################

  Scenario: Hiding a node means it is visible with the various traversal methods when visibility restrictions are ignored
    When the command "HideNode" is executed with payload:
      | Key                          | Value           |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "text-1"        |
      | affectedDimensionSpacePoints | [{}]            |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    And VisibilityConstraints are set to "withoutRestrictions"

    # findNodeByNodeAggregateIdentifier
    Then I expect a node identified by aggregate identifier "text-1" to exist in the subgraph
    # findNodeByIdentifier: does not support visibility constraints
    # findChildNodes
    # countChildNodes
    Then I expect the node aggregate "lady-eleonode-rootford" to have the following child nodes:
      | Name  | NodeAggregateIdentifier  |
      | text1 | text-1                   |
    # findParentNode
    When I go to the parent node of node aggregate "text-2"
    Then I find a node with node aggregate "text-1"
    # traverseHierarchy is covered by "findChildNodes" and "findParentNode"
    # findParentNodeByNodeAggregateIdentifier
    When I go to the parent node of node aggregate "text-2"
    Then I find a node with node aggregate "text-1"
    # findNodeByPath
    # findChildNodeConnectedThroughEdgeName
    And I expect node aggregate identifier "text-1" and path "text1" to lead to node {"contentStreamIdentifier": "cs-identifier","nodeAggregateIdentifier": "text-1", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "text-2" and path "text1/text2" to lead to node {"contentStreamIdentifier": "cs-identifier","nodeAggregateIdentifier": "text-2", "originDimensionSpacePoint": {}}
    # findReferencedNodes
    And I expect the node aggregate "referencing-node" to have the references:
      | Key | Value      |
      | ref | ["text-2"] |
    # findSubtree
    And the subtree for node aggregate "text-1" with node types "" and 5 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | text-1                  |
      | 1     | text-2                  |
      | 1     | referencing-node        |

    # TODO: findChildNodeByNodeAggregateIdentifierConnectedThroughEdgeName

    # TODO: findSiblings
    # TODO: findSucceedingSiblings
    # TODO: findPrecedingSiblings

  Scenario: Hiding a node means it is invisible with the various traversal methods (findReferencingNodes)
    When the command "HideNode" is executed with payload:
      | Key                          | Value              |
      | contentStreamIdentifier      | "cs-identifier"    |
      | nodeAggregateIdentifier      | "referencing-node" |
      | affectedDimensionSpacePoints | [{}]               |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}

    # findReferencingNodes
    Then I expect the node aggregate "text-2" to be referenced by:
      | Key | Value |
      | ref | []    |

    ######################################################
    # Second part of Scenario: No visibility restrictions
    ######################################################
    When VisibilityConstraints are set to "withoutRestrictions"

    # findReferencingNodes
    Then I expect the node aggregate "text-2" to be referenced by:
      | Key | Value                |
      | ref | ["referencing-node"] |


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
      | nodeAggregateIdentifier      | "text-1"             |
      | affectedDimensionSpacePoints | [{"language": "de"}] |
    Then the last command should have thrown an exception of type "NodeNotFoundException"

  Scenario: Showing a previously-hidden node works
    When the command "HideNode" is executed with payload:
      | Key                          | Value           |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "text-1"        |
      | affectedDimensionSpacePoints | [{}]            |

    And the graph projection is fully up to date

    When the command "ShowNode" is executed with payload:
      | Key                          | Value           |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "text-1"        |
      | affectedDimensionSpacePoints | [{}]            |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "text-1" to exist in the subgraph


  Scenario: Removing a previously-hidden node clears the hidden restriction
    When the command "HideNode" is executed with payload:
      | Key                          | Value           |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "text-1"        |
      | affectedDimensionSpacePoints | [{}]            |

    And the graph projection is fully up to date

    And the command RemoveNodeAggregate was published with payload:
      | Key                     | Value           |
      | contentStreamIdentifier | "cs-identifier" |
      | nodeAggregateIdentifier | "text-1"        |

  # TODO: Creating, Removing, Creating a NodeAggregate should actually WORK and not throw an error!!!

#    # we recreate the node again; and it should not be hidden now.
#    When the command "CreateNodeAggregateWithNode" is executed with payload:
#      | Key                     | Value                                    |
#      | contentStreamIdentifier | "cs-identifier"                          |
#      | nodeAggregateIdentifier | "text-1"                          |
#      | dimensionSpacePoint     | {}                                       |
#      | nodeTypeName            | "Neos.ContentRepository.Testing:Content" |
#      | nodeIdentifier          | "node-identifier"                        |
#      | parentNodeIdentifier    | "rn-identifier"                          |
#      | nodeName                | "text1"                                  |
#
#    And the graph projection is fully up to date
#
#    When I am in the active content stream of workspace "live" and Dimension Space Point {}
#    Then I expect a node identified by aggregate identifier "text-1" to exist in the subgraph
