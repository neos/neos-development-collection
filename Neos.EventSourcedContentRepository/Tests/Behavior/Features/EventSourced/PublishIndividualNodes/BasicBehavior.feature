@fixtures
Feature: Publishing individual nodes (basics)

  Publishing an individual node works
  Node structure is as follows:
  - rn-identifier (root node)
  -- na-identifier (name=text1) <== modifications!
  --- cna-identifier (name=text2) <== modifications!
  -- na2-identifier (name=image) <== modifications!


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
    'Neos.ContentRepository.Testing:Image':
      properties:
        image:
          type: string
    """
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                               |
      | contentStreamIdentifier       | "cs-identifier"                                     |
      | nodeAggregateIdentifier       | "na-identifier"                                     |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"            |
      | nodeIdentifier                | "node-identifier"                                   |
      | parentNodeIdentifier          | "rn-identifier"                                     |
      | nodeName                      | "text1"                                             |
      | propertyDefaultValuesAndTypes | {"text": {"type": "string", "value": "Initial t1"}} |

    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                               |
      | contentStreamIdentifier       | "cs-identifier"                                     |
      | nodeAggregateIdentifier       | "cna-identifier"                                    |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"            |
      | nodeIdentifier                | "cnode-identifier"                                  |
      | parentNodeIdentifier          | "node-identifier"                                   |
      | nodeName                      | "text2"                                             |
      | propertyDefaultValuesAndTypes | {"text": {"type": "string", "value": "Initial t2"}} |

    # create the "na2-node" node
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                  |
      | contentStreamIdentifier       | "cs-identifier"                                        |
      | nodeAggregateIdentifier       | "na2-identifier"                                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Image"                 |
      | nodeIdentifier                | "imagenode-identifier"                                 |
      | parentNodeIdentifier          | "rn-identifier"                                        |
      | nodeName                      | "image"                                                |
      | propertyDefaultValuesAndTypes | {"image": {"type": "image", "value": "Initial image"}} |
    And the graph projection is fully up to date

    # Create user workspace
    And the command CreateWorkspace is executed with payload:
      | Key                     | Value             |
      | workspaceName           | "user-test"       |
      | baseWorkspaceName       | "live"            |
      | contentStreamIdentifier | "cs-2-identifier" |
    And the graph projection is fully up to date
    # modify nodes in user WS
    And the command "SetNodeProperty" is executed with payload:
      | Key                       | Value                                   |
      | contentStreamIdentifier   | "cs-2-identifier"                       |
      | nodeAggregateIdentifier   | "na-identifier"                         |
      | originDimensionSpacePoint | {}                                      |
      | propertyName              | "text"                                  |
      | value                     | {"value":"Modified t1","type":"string"} |
    And the command "SetNodeProperty" is executed with payload:
      | Key                       | Value                                   |
      | contentStreamIdentifier   | "cs-2-identifier"                       |
      | nodeAggregateIdentifier   | "cna-identifier"                        |
      | originDimensionSpacePoint | {}                                      |
      | propertyName              | "text"                                  |
      | value                     | {"value":"Modified t2","type":"string"} |
    And the command "SetNodeProperty" is executed with payload:
      | Key                       | Value                                      |
      | contentStreamIdentifier   | "cs-2-identifier"                          |
      | nodeAggregateIdentifier   | "na2-identifier"                           |
      | originDimensionSpacePoint | {}                                         |
      | propertyName              | "image"                                    |
      | value                     | {"value":"Modified image","type":"string"} |
    And the graph projection is fully up to date

  Scenario: It is possible to publish a single node; and only this one is live.
    # publish "na2-identifier" only
    When the command "PublishIndividualNodesFromWorkspace" is executed with payload:
      | Key           | Value                                                                                                                    |
      | workspaceName | "user-test"                                                                                                              |
      | nodeAddresses | [{"contentStreamIdentifier": "cs-2-identifier", "dimensionSpacePoint": {}, "nodeAggregateIdentifier": "na2-identifier"}] |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect the Node Aggregate "na-identifier" to have the properties:
      | Key  | Value      |
      | text | Initial t1 |
    And I expect the Node Aggregate "cna-identifier" to have the properties:
      | Key  | Value      |
      | text | Initial t2 |
    And I expect the Node Aggregate "na2-identifier" to have the properties:
      | Key   | Value          |
      | image | Modified image |

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect the Node Aggregate "na-identifier" to have the properties:
      | Key  | Value       |
      | text | Modified t1 |
    And I expect the Node Aggregate "cna-identifier" to have the properties:
      | Key  | Value       |
      | text | Modified t2 |
    And I expect the Node Aggregate "na2-identifier" to have the properties:
      | Key   | Value          |
      | image | Modified image |


  Scenario: It is possible to publish no node
    When the command "PublishIndividualNodesFromWorkspace" is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |
      | nodeAddresses | []          |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect the Node Aggregate "na-identifier" to have the properties:
      | Key  | Value      |
      | text | Initial t1 |
    And I expect the Node Aggregate "cna-identifier" to have the properties:
      | Key  | Value      |
      | text | Initial t2 |
    And I expect the Node Aggregate "na2-identifier" to have the properties:
      | Key   | Value         |
      | image | Initial image |

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect the Node Aggregate "na-identifier" to have the properties:
      | Key  | Value       |
      | text | Modified t1 |
    And I expect the Node Aggregate "cna-identifier" to have the properties:
      | Key  | Value       |
      | text | Modified t2 |
    And I expect the Node Aggregate "na2-identifier" to have the properties:
      | Key   | Value          |
      | image | Modified image |


  Scenario: It is possible to publish all nodes
    When the command "PublishIndividualNodesFromWorkspace" is executed with payload:
      | Key           | Value                                                                                                                                                                                                                                                                                                                                                                   |
      | workspaceName | "user-test"                                                                                                                                                                                                                                                                                                                                                             |
      | nodeAddresses | [{"contentStreamIdentifier": "cs-2-identifier", "dimensionSpacePoint": {}, "nodeAggregateIdentifier": "na-identifier"}, {"contentStreamIdentifier": "cs-2-identifier", "dimensionSpacePoint": {}, "nodeAggregateIdentifier": "cna-identifier"}, {"contentStreamIdentifier": "cs-2-identifier", "dimensionSpacePoint": {}, "nodeAggregateIdentifier": "na2-identifier"}] |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect the Node Aggregate "na-identifier" to have the properties:
      | Key  | Value       |
      | text | Modified t1 |
    And I expect the Node Aggregate "cna-identifier" to have the properties:
      | Key  | Value       |
      | text | Modified t2 |
    And I expect the Node Aggregate "na2-identifier" to have the properties:
      | Key   | Value          |
      | image | Modified image |

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect the Node Aggregate "na-identifier" to have the properties:
      | Key  | Value       |
      | text | Modified t1 |
    And I expect the Node Aggregate "cna-identifier" to have the properties:
      | Key  | Value       |
      | text | Modified t2 |
    And I expect the Node Aggregate "na2-identifier" to have the properties:
      | Key   | Value          |
      | image | Modified image |
