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
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | contentStreamIdentifier  | cs-identifier                        | Uuid |
      | rootNodeIdentifier       | rn-identifier                        | Uuid |

    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                  | Type                   |
      | contentStreamIdentifier       | cs-identifier                          | Uuid                   |
      | nodeAggregateIdentifier       | na-identifier                          | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Content |                        |
      | nodeIdentifier                | node-identifier                        | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                          | Uuid                   |
      | nodeName                      | text1                                  |                        |

    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                  | Type                   |
      | contentStreamIdentifier       | cs-identifier                          | Uuid                   |
      | nodeAggregateIdentifier       | na-2-identifier                        | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Content |                        |
      | nodeIdentifier                | node-2-identifier                      | Uuid                   |
      | parentNodeIdentifier          | node-identifier                        | Uuid                   |
      | nodeName                      | text2                                  |                        |

    When the command "SetNodeProperty" is executed with payload:
      | Key                     | Value                                | Type |
      | contentStreamIdentifier | cs-identifier                        | Uuid |
      | nodeIdentifier          | node-identifier                      | Uuid |
      | propertyName            | text                                 |      |
      | value                   | {"value":"Original","type":"string"} | json |

    And the command CreateWorkspace is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | user-test                            |      |
      | baseWorkspaceName        | live                                 |      |
      | contentStreamIdentifier  | user-cs-identifier                   | Uuid |

  Scenario: Set property of a node

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

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node "[node-identifier]" to exist in the graph projection
    And I expect the Node "[node-identifier]" to have the properties:
      | Key  | Value    |
      | text | Original |

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect a node "[node-identifier]" to exist in the graph projection
    And I expect the Node "[node-identifier]" to have the properties:
      | Key  | Value   |
      | text | Changed |

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect the path "/text1/text2" to lead to the node "[node-2-identifier]"
    When I go to the parent node of node "[node-2-identifier]"
    Then I expect the current Node to have the properties:
      | Key  | Value   |
      | text | Original |

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect the path "/text1/text2" to lead to the node "[node-2-identifier]"
    When I go to the parent node of node "[node-2-identifier]"
    Then I expect the current Node to have the properties:
      | Key  | Value   |
      | text | Changed |
