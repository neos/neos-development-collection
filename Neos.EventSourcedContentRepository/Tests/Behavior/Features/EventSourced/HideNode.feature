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
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | contentStreamIdentifier  | cs-identifier                        | Uuid |
      | rootNodeIdentifier       | rn-identifier                        | Uuid |
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
      | Key                           | Value                                  | Type                   |
      | contentStreamIdentifier       | cs-identifier                          | Uuid                   |
      | nodeAggregateIdentifier       | na-identifier                          | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Content |                        |
      | nodeIdentifier                | node-identifier                        | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                          | Uuid                   |
      | nodeName                      | text1                                  |                        |

    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                  | Type |
      | contentStreamIdentifier | cs-identifier                          | Uuid |
      | nodeAggregateIdentifier | cna-identifier                         | Uuid |
      | nodeTypeName            | Neos.ContentRepository.Testing:Content |      |
      | nodeIdentifier          | cnode-identifier                       | Uuid |
      | parentNodeIdentifier    | node-identifier                        | Uuid |
      | nodeName                | text2                                  |      |

    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                            | Type |
      | contentStreamIdentifier | cs-identifier                                    | Uuid |
      | nodeAggregateIdentifier | refna-identifier                                 | Uuid |
      | nodeTypeName            | Neos.ContentRepository.Testing:NodeWithReference |      |
      | nodeIdentifier          | rednode-identifier                               | Uuid |
      | parentNodeIdentifier    | node-identifier                                  | Uuid |
      | nodeName                | ref                                              |      |

  Scenario: Hide a node generates the correct events
    Given the command "HideNode" is executed with payload:
      | Key                          | Value         | Type |
      | contentStreamIdentifier      | cs-identifier | Uuid |
      | nodeAggregateIdentifier      | na-identifier | Uuid |
      | affectedDimensionSpacePoints | [{}]          | json |

    Then I expect exactly 6 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:[cs-identifier]"
    And event at index 5 is of type "Neos.EventSourcedContentRepository:NodeWasHidden" with payload:
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


    # TODO: findChildNodeByNodeAggregateIdentifierConnectedThroughEdgeName
    # TODO: findSubtrees
    # TODO: findReferencedNodes
    # TODO: findReferencingNodes

    # TODO: findSiblings
    # TODO: findSucceedingSiblings
    # TODO: findPrecedingSiblings






    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect a node identified by aggregate identifier "[na-identifier]" to exist in the subgraph
    Then I expect a node "[node-identifier]" to exist in the graph projection
    # TODO: 2nd part of testcase

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
