@fixtures
Feature: Single Node operations on live workspace

  As a user of the CR I want to execute operations on a node in live workspace.

  Background:
    Given I have no content dimensions
    And the command CreateWorkspace is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | contentStreamIdentifier  | cs-identifier                        | Uuid |
      | rootNodeIdentifier       | rn-identifier                        | Uuid |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                  | Type                   |
      | contentStreamIdentifier       | cs-identifier                          | Uuid                   |
      | nodeAggregateIdentifier       | na-identifier                          | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Content |                        |
      | nodeIdentifier                | node-identifier                        | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                          | Uuid                   |
      | nodeName                      | text1                                  |                        |

  Scenario: Set property of a node
    Given the command "SetNodeProperty" is executed with payload:
      | Key                       | Value                             | Type                |
      | contentStreamIdentifier   | cs-identifier                     | Uuid                |
      | nodeAggregateIdentifier   | na-identifier                     | Uuid                |
      | originDimensionSpacePoint | {}                                | DimensionSpacePoint |
      | propertyName              | text                              |                     |
      | value                     | {"value":"Hello","type":"string"} | json                |


    Then I expect exactly 4 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:[cs-identifier]"
    And event at index 3 is of type "Neos.EventSourcedContentRepository:NodePropertyWasSet" with payload:
      | Key                       | Expected      | Type | AssertionType |
      | contentStreamIdentifier   | cs-identifier | Uuid |               |
      | nodeAggregateIdentifier   | na-identifier | Uuid |               |
      | originDimensionSpacePoint | {}            |      | json          |
      | propertyName              | text          |      |               |
      | value.value               | Hello         |      |               |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node "[node-identifier]" to exist in the graph projection
    And I expect the Node "[node-identifier]" to have the properties:
      | Key  | Value |
      | text | Hello |

  Scenario: Hide a node
    Given the command "HideNode" is executed with payload:
      | Key                     | Value                                | Type |
      | contentStreamIdentifier | cs-identifier                        | Uuid |
      | nodeIdentifier          | node-identifier                      | Uuid |


    Then I expect exactly 4 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:[cs-identifier]"
    And event at index 3 is of type "Neos.EventSourcedContentRepository:NodeWasHidden" with payload:
      | Key                     | Expected                             | Type |
      | contentStreamIdentifier | cs-identifier                        | Uuid |
      | nodeIdentifier          | node-identifier                      | Uuid |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node "[node-identifier]" to exist in the graph projection
    And I expect the Node "[node-identifier]" is hidden

  Scenario: Show a node
    Given the command "ShowNode" is executed with payload:
      | Key                     | Value                                | Type |
      | contentStreamIdentifier | cs-identifier                        | Uuid |
      | nodeIdentifier          | node-identifier                      | Uuid |


    Then I expect exactly 4 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:[cs-identifier]"
    And event at index 3 is of type "Neos.EventSourcedContentRepository:NodeWasShown" with payload:
      | Key                     | Expected                             | Type |
      | contentStreamIdentifier | cs-identifier                        | Uuid |
      | nodeIdentifier          | node-identifier                      | Uuid |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node "[node-identifier]" to exist in the graph projection
    And I expect the Node "[node-identifier]" is shown
