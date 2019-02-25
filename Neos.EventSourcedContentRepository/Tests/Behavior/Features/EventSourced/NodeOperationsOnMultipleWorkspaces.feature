@fixtures
Feature: Single Node operations on multiple workspaces/content streams; e.g. copy on write!

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And the command CreateWorkspace is executed with payload:
      | Key                     | Value           |
      | workspaceName           | "live"          |
      | contentStreamIdentifier | "cs-identifier" |
      | rootNodeIdentifier      | "rn-identifier" |

    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                    |
      | contentStreamIdentifier | "cs-identifier"                          |
      | nodeAggregateIdentifier | "na-identifier"                          |
      | nodeTypeName            | "Neos.ContentRepository.Testing:Content" |
      | nodeIdentifier          | "node-identifier"                        |
      | parentNodeIdentifier    | "rn-identifier"                          |
      | nodeName                | "text1"                                  |

    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                    |
      | contentStreamIdentifier | "cs-identifier"                          |
      | nodeAggregateIdentifier | "na-2-identifier"                        |
      | nodeTypeName            | "Neos.ContentRepository.Testing:Content" |
      | nodeIdentifier          | "node-2-identifier"                      |
      | parentNodeIdentifier    | "node-identifier"                        |
      | nodeName                | "text2"                                  |

    And the graph projection is fully up to date

    When the command "SetNodeProperty" is executed with payload:
      | Key                       | Value                                |
      | contentStreamIdentifier   | "cs-identifier"                      |
      | nodeAggregateIdentifier   | "na-identifier"                      |
      | originDimensionSpacePoint | {}                                   |
      | propertyName              | "text"                               |
      | value                     | {"value":"Original","type":"string"} |

    And the graph projection is fully up to date

    And the command CreateWorkspace is executed with payload:
      | Key                     | Value                |
      | workspaceName           | "user-test"          |
      | baseWorkspaceName       | "live"               |
      | contentStreamIdentifier | "user-cs-identifier" |

    And the graph projection is fully up to date

  Scenario: Set property of a node

    Given the command "SetNodeProperty" is executed with payload:
      | Key                       | Value                               |
      | contentStreamIdentifier   | "user-cs-identifier"                |
      | nodeAggregateIdentifier   | "na-identifier"                     |
      | originDimensionSpacePoint | {}                                  |
      | propertyName              | "text"                              |
      | value                     | {"value":"Changed","type":"string"} |


    Then I expect exactly 2 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:user-cs-identifier"
    And event at index 1 is of type "Neos.EventSourcedContentRepository:NodePropertyWasSet" with payload:
      | Key                       | Expected             |
      | contentStreamIdentifier   | "user-cs-identifier" |
      | nodeAggregateIdentifier   | "na-identifier"      |
      | originDimensionSpacePoint | []                   |
      | propertyName              | "text"               |
      | value.value               | "Changed"            |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node "node-identifier" to exist in the graph projection
    And I expect the Node "node-identifier" to have the properties:
      | Key  | Value    |
      | text | Original |

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect a node "node-identifier" to exist in the graph projection
    And I expect the Node "node-identifier" to have the properties:
      | Key  | Value   |
      | text | Changed |

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect the path "/text1/text2" to lead to the node "node-2-identifier"
    When I go to the parent node of node aggregate "na-2-identifier"
    Then I expect the current Node to have the properties:
      | Key  | Value    |
      | text | Original |

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect the path "/text1/text2" to lead to the node "node-2-identifier"
    When I go to the parent node of node aggregate "na-2-identifier"
    Then I expect the current Node to have the properties:
      | Key  | Value   |
      | text | Changed |
