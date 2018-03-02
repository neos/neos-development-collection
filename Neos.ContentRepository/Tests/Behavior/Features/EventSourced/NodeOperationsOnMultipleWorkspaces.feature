@fixtures
Feature: Single Node operations on live workspace

  As a user of the CR I want to execute operations on a node in live workspace.

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
    And the command "CreateRootWorkspace" is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | workspaceTitle           | Live                                 |      |
      | workspaceDescription     | The live workspace                   |      |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |      |
      | contentStreamIdentifier  | cs-identifier                        | Uuid |
      | rootNodeIdentifier       | rn-identifier                        | Uuid |
      | rootNodeTypeName         | Neos.ContentRepository:Root          |      |

    And the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:[cs-identifier]:NodeAggregate:[na-identifier]" with payload:
      | Key                           | Value                                  | Type                   |
      | contentStreamIdentifier       | cs-identifier                          | Uuid                   |
      | nodeAggregateIdentifier       | na-identifier                          | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Content |                        |
      | dimensionSpacePoint           | {"coordinates":[]}                     | json                   |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":[]}]}        | DimensionSpacePointSet |
      | nodeIdentifier                | node-identifier                        | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                          | Uuid                   |
      | nodeName                      | text1                                  |                        |
      | propertyDefaultValuesAndTypes | {}                                     | json                   |

    And the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:[cs-identifier]:NodeAggregate:[na-2-identifier]" with payload:
      | Key                           | Value                                  | Type                   |
      | contentStreamIdentifier       | cs-identifier                          | Uuid                   |
      | nodeAggregateIdentifier       | na-2-identifier                        | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Content |                        |
      | dimensionSpacePoint           | {"coordinates":[]}                     | json                   |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":[]}]}        | DimensionSpacePointSet |
      | nodeIdentifier                | node-2-identifier                      | Uuid                   |
      | parentNodeIdentifier          | node-identifier                        | Uuid                   |
      | nodeName                      | text2                                  |                        |
      | propertyDefaultValuesAndTypes | {}                                     | json                   |

    When the command "SetNodeProperty" is executed with payload:
      | Key                     | Value                                | Type |
      | contentStreamIdentifier | cs-identifier                        | Uuid |
      | nodeIdentifier          | node-identifier                      | Uuid |
      | propertyName            | text                                 |      |
      | value                   | {"value":"Original","type":"string"} | json |

    And the command "CreateWorkspace" is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | user-test                            |      |
      | baseWorkspaceName        | live                                 |      |
      | workspaceTitle           | Test User WS                         |      |
      | workspaceDescription     | The user-test workspace              |      |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |      |
      | contentStreamIdentifier  | user-cs-identifier                   | Uuid |
      | workspaceOwner           | 00000000-0000-0000-0000-000000000000 |      |

  Scenario: Set property of a node

    When I am in the active content stream of workspace "live" and Dimension Space Point {"coordinates": []}
    Then I expect the path "/text1/text2" to lead to the node "[node-2-identifier]"


    Given the command "SetNodeProperty" is executed with payload:
      | Key                     | Value                               | Type |
      | contentStreamIdentifier | user-cs-identifier                  | Uuid |
      | nodeIdentifier          | node-identifier                     | Uuid |
      | propertyName            | text                                |      |
      | value                   | {"value":"Changed","type":"string"} | json |


    Then I expect exactly 2 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:[user-cs-identifier]"
    And event at index 1 is of type "Neos.ContentRepository:NodePropertyWasSet" with payload:
      | Key                     | Expected           | Type |
      | contentStreamIdentifier | user-cs-identifier | Uuid |
      | nodeIdentifier          | node-identifier    | Uuid |
      | propertyName            | text               |      |
      | value.value             | Changed            |      |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {"coordinates": []}
    Then I expect a node "[node-identifier]" to exist in the graph projection
    And I expect the Node "[node-identifier]" to have the properties:
      | Key  | Value    |
      | text | Original |

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {"coordinates": []}
    Then I expect a node "[node-identifier]" to exist in the graph projection
    And I expect the Node "[node-identifier]" to have the properties:
      | Key  | Value   |
      | text | Changed |

    When I am in the active content stream of workspace "live" and Dimension Space Point {"coordinates": []}
    Then I expect the path "/text1/text2" to lead to the node "[node-2-identifier]"

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {"coordinates": []}
    Then I expect the path "/text1/text2" to lead to the node "[node-2-identifier]"
