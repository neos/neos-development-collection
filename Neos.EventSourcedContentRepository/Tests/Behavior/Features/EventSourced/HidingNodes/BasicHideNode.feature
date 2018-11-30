@fixtures
Feature: Hide Node

  Hiding a node works.
  Node structure is as follows:
  - [root node]
    - text1 (hidden)
      - text2
      - ref (a node with a reference to text2)


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
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    'Neos.ContentRepository.Testing:NodeWithReference':
      properties:
        ref:
          type: reference
    """
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                  | Type |
      | contentStreamIdentifier | cs-identifier                          | Uuid |
      | nodeAggregateIdentifier | na-identifier                          | Uuid |
      | nodeTypeName            | Neos.ContentRepository.Testing:Content |      |
      | nodeIdentifier          | node-identifier                        | Uuid |
      | parentNodeIdentifier    | rn-identifier                          | Uuid |
      | nodeName                | text1                                  |      |

    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                  | Type |
      | contentStreamIdentifier | cs-identifier                          | Uuid |
      | nodeAggregateIdentifier | cna-identifier                         | Uuid |
      | nodeTypeName            | Neos.ContentRepository.Testing:Content |      |
      | nodeIdentifier          | cnode-identifier                       | Uuid |
      | parentNodeIdentifier    | node-identifier                        | Uuid |
      | nodeName                | text2                                  |      |

    # create the "ref" node
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                            | Type |
      | contentStreamIdentifier | cs-identifier                                    | Uuid |
      | nodeAggregateIdentifier | refna-identifier                                 | Uuid |
      | nodeTypeName            | Neos.ContentRepository.Testing:NodeWithReference |      |
      | nodeIdentifier          | refnode-identifier                               | Uuid |
      | parentNodeIdentifier    | node-identifier                                  | Uuid |
      | nodeName                | ref                                              |      |
    And the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value              | Type   |
      | contentStreamIdentifier             | cs-identifier      | Uuid   |
      | nodeIdentifier                      | refnode-identifier | Uuid   |
      | propertyName                        | ref                |        |
      | destinationNodeAggregateIdentifiers | cna-identifier     | Uuid[] |

  Scenario: Hide a node generates the correct events
    Given the command "HideNode" is executed with payload:
      | Key                          | Value         | Type |
      | contentStreamIdentifier      | cs-identifier | Uuid |
      | nodeAggregateIdentifier      | na-identifier | Uuid |
      | affectedDimensionSpacePoints | [{}]          | json |

    Then I expect exactly 7 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:[cs-identifier]"
    And event at index 6 is of type "Neos.EventSourcedContentRepository:NodeWasHidden" with payload:
      | Key                          | Expected      | Type | AssertionType |
      | contentStreamIdentifier      | cs-identifier | Uuid |               |
      | nodeAggregateIdentifier      | na-identifier | Uuid |               |
      | affectedDimensionSpacePoints | [{}]          |      | json          |

  Scenario: Hiding a node means it is invisible with the various traversal methods
    Given the command "HideNode" is executed with payload:
      | Key                          | Value         | Type |
      | contentStreamIdentifier      | cs-identifier | Uuid |
      | nodeAggregateIdentifier      | na-identifier | Uuid |
      | affectedDimensionSpacePoints | [{}]          | json |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    # findNodeByNodeAggregateIdentifier
    Then I expect a node identified by aggregate identifier "[na-identifier]" not to exist in the subgraph
    # findNodeByIdentifier
    Then I expect a node "[node-identifier]" not to exist in the graph projection
    # findChildNodes
    # countChildNodes
    Then I expect the node "[rn-identifier]" to have the following child nodes:
      | Name | NodeIdentifier |
      # no child nodes as they are hidden
    # findParentNode
    When I go to the parent node of node "[cnode-identifier]"
    Then I do not find any node
    # traverseHierarchy is covered by "findChildNodes" and "findParentNode"
    # findParentNodeByNodeAggregateIdentifier
    When I go to the parent node of node aggregate "[cna-identifier]"
    Then I do not find any node
    # findNodeByPath
    # findChildNodeConnectedThroughEdgeName
    Then I expect the path "/text1" to lead to no node
    Then I expect the path "/text1/text2" to lead to no node
    # findReferencedNodes
    Then I expect the Node "[refnode-identifier]" to have the references:
      | Key | Value | Type   |
      | ref |       | Uuid[] |
    # findSubtree
    Then the subtree for node aggregate "[na-identifier]" with node types "" and 5 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | ROOT                    |

    ######################################################
    # Second part of Scenario: No visibility restrictions
    ######################################################
    When VisibilityConstraints are set to "withoutRestrictions"

    # findNodeByNodeAggregateIdentifier
    Then I expect a node identified by aggregate identifier "[na-identifier]" to exist in the subgraph
    # findNodeByIdentifier
    Then I expect a node "[node-identifier]" to exist in the graph projection
    # findChildNodes
    # countChildNodes
    Then I expect the node "[rn-identifier]" to have the following child nodes:
      | Name  | NodeIdentifier    |
      | text1 | [node-identifier] |
    # findParentNode
    When I go to the parent node of node "[cnode-identifier]"
    Then I find a node with node aggregate "[na-identifier]"
    # traverseHierarchy is covered by "findChildNodes" and "findParentNode"
    # findParentNodeByNodeAggregateIdentifier
    When I go to the parent node of node aggregate "[cna-identifier]"
    Then I find a node with node aggregate "[na-identifier]"
    # findNodeByPath
    # findChildNodeConnectedThroughEdgeName
    Then I expect the path "/text1" to lead to the node "[node-identifier]"
    Then I expect the path "/text1/text2" to lead to the node "[cnode-identifier]"
    # findReferencedNodes
    Then I expect the Node "[refnode-identifier]" to have the references:
      | Key | Value          | Type   |
      | ref | cna-identifier | Uuid[] |
    # findSubtree
    Then the subtree for node aggregate "[na-identifier]" with node types "" and 5 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | ROOT                    |
      | 0     | [na-identifier]         |
      | 1     | [cna-identifier]        |
      | 1     | [refna-identifier]      |

    # TODO: findChildNodeByNodeAggregateIdentifierConnectedThroughEdgeName

    # TODO: findSiblings
    # TODO: findSucceedingSiblings
    # TODO: findPrecedingSiblings

  Scenario: Hiding a node means it is invisible with the various traversal methods (findReferencingNodes)
    Given the command "HideNode" is executed with payload:
      | Key                          | Value            | Type |
      | contentStreamIdentifier      | cs-identifier    | Uuid |
      | nodeAggregateIdentifier      | refna-identifier | Uuid |
      | affectedDimensionSpacePoints | [{}]             | json |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}

    # findReferencingNodes
    Then I expect the Node "[cnode-identifier]" to be referenced by:
      | Key | Value | Type   |
      | ref |       | Uuid[] |

    ######################################################
    # Second part of Scenario: No visibility restrictions
    ######################################################
    When VisibilityConstraints are set to "withoutRestrictions"

    # findReferencingNodes
    Then I expect the Node "[cnode-identifier]" to be referenced by:
      | Key | Value            | Type   |
      | ref | refna-identifier | Uuid[] |


  Scenario: Hide a non-existing node should throw an exception
    Given the command "HideNode" is executed with payload and exceptions are caught:
      | Key                          | Value              | Type |
      | contentStreamIdentifier      | cs-identifier      | Uuid |
      | nodeAggregateIdentifier      | unknown-identifier | Uuid |
      | affectedDimensionSpacePoints | [{}]               | json |
    Then the last command should have thrown an exception of type "NodeNotFoundException"

  Scenario: Hide a non-existing node in a certain dimension should throw an exception
    Given the command "HideNode" is executed with payload and exceptions are caught:
      | Key                          | Value                | Type |
      | contentStreamIdentifier      | cs-identifier        | Uuid |
      | nodeAggregateIdentifier      | na-identifier        | Uuid |
      | affectedDimensionSpacePoints | [{"language": "de"}] | json |
    Then the last command should have thrown an exception of type "NodeNotFoundException"
