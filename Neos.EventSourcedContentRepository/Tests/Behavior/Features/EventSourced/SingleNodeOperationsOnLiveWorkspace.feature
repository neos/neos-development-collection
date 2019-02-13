@fixtures
Feature: Single Node operations on live workspace

  As a user of the CR I want to execute operations on a node in live workspace.

  Background:
    Given I have no content dimensions
    And the command CreateWorkspace is executed with payload:
      | Key                     | Value           |
      | workspaceName           | "live"          |
      | contentStreamIdentifier | "cs-identifier" |
      | rootNodeIdentifier      | "rn-identifier" |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                    |
      | contentStreamIdentifier | "cs-identifier"                          |
      | nodeAggregateIdentifier | "na-identifier"                          |
      | nodeTypeName            | "Neos.ContentRepository.Testing:Content" |
      | nodeIdentifier          | "node-identifier"                        |
      | parentNodeIdentifier    | "rn-identifier"                          |
      | nodeName                | "text1"                                  |

    And the graph projection is fully up to date

  Scenario: Set property of a node
    Given the command "SetNodeProperty" is executed with payload:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "na-identifier"                   |
      | originDimensionSpacePoint | {}                                |
      | propertyName              | "text"                            |
      | value                     | {"value":"Hello","type":"string"} |


    Then I expect exactly 4 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 3 is of type "Neos.EventSourcedContentRepository:NodePropertyWasSet" with payload:
      | Key                       | Expected        |
      | contentStreamIdentifier   | "cs-identifier" |
      | nodeAggregateIdentifier   | "na-identifier" |
      | originDimensionSpacePoint | []              |
      | propertyName              | "text"          |
      | value.value               | "Hello"         |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node "node-identifier" to exist in the graph projection
    And I expect the Node "node-identifier" to have the properties:
      | Key  | Value |
      | text | Hello |

  Scenario: Show a node
    Given the command "ShowNode" is executed with payload:
      | Key                          | Value           |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "na-identifier" |
      | affectedDimensionSpacePoints | [{}]            |


    Then I expect exactly 4 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 3 is of type "Neos.EventSourcedContentRepository:NodeWasShown" with payload:
      | Key                          | Expected        |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "na-identifier" |
      | affectedDimensionSpacePoints | [[]]            |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node "node-identifier" to exist in the graph projection
