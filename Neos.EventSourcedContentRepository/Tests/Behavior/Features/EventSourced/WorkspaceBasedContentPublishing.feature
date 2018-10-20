@fixtures
Feature: Workspace based content publishing

  This is an END TO END test; testing all layers of the related functionality step by step together

  Basic fixture setup is:
  - root workspace with a single "foo" node inside
  - then, a nested workspace is created based on the "foo" node

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
      | Key                     | Value         | Type |
      | workspaceName           | live          |      |
      | contentStreamIdentifier | cs-identifier | Uuid |
      | rootNodeIdentifier      | rn-identifier | Uuid |

    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                  | Type |
      | contentStreamIdentifier | cs-identifier                          | Uuid |
      | nodeAggregateIdentifier | na-identifier                          | Uuid |
      | nodeTypeName            | Neos.ContentRepository.Testing:Content |      |
      | nodeIdentifier          | node-identifier                        | Uuid |
      | parentNodeIdentifier    | rn-identifier                          | Uuid |
      | nodeName                | foo                                    |      |

    When the command "SetNodeProperty" is executed with payload:
      | Key                     | Value                                | Type |
      | contentStreamIdentifier | cs-identifier                        | Uuid |
      | nodeIdentifier          | node-identifier                      | Uuid |
      | propertyName            | text                                 |      |
      | value                   | {"value":"Original","type":"string"} | json |

    And the command CreateWorkspace is executed with payload:
      | Key                     | Value           | Type |
      | workspaceName           | user-test       |      |
      | baseWorkspaceName       | live            |      |
      | contentStreamIdentifier | cs-2-identifier | Uuid |


  Scenario: Basic events are emitted

    # LIVE workspace
    Then I expect exactly 3 events to be published on stream "Neos.ContentRepository:ContentStream:[cs-identifier]"
    And event at index 0 is of type "Neos.EventSourcedContentRepository:ContentStreamWasCreated" with payload:
      | Key                      | Expected                 | Type |
      | contentStreamIdentifier  | cs-identifier            | Uuid |
      | initiatingUserIdentifier | initiatingUserIdentifier | Uuid |

    # Event 1 is the root Node Created event (we can skip this here, it is tested somewhere else); Event 2 is the SetProperty
    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:Workspace:live"
    And event at index 0 is of type "Neos.EventSourcedContentRepository:RootWorkspaceWasCreated" with payload:
      | Key                            | Expected                 | Type |
      | workspaceName                  | live                     |      |
      | workspaceTitle                 | Live                     |      |
      | workspaceDescription           | The workspace "live".    |      |
      | initiatingUserIdentifier       | initiatingUserIdentifier | Uuid |
      | currentContentStreamIdentifier | cs-identifier            | Uuid |

    # USER workspace
    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:ContentStream:[cs-2-identifier]"
    And event at index 0 is of type "Neos.EventSourcedContentRepository:ContentStreamWasForked" with payload:
      | Key                           | Expected        | Type |
      | contentStreamIdentifier       | cs-2-identifier | Uuid |
      | sourceContentStreamIdentifier | cs-identifier   | Uuid |

    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:Workspace:user-test"
    And event at index 0 is of type "Neos.EventSourcedContentRepository:WorkspaceWasCreated" with payload:
      | Key                            | Expected                   | Type |
      | workspaceName                  | user-test                  |      |
      | baseWorkspaceName              | live                       |      |
      | workspaceTitle                 | User-test                  |      |
      | workspaceDescription           | The workspace "user-test". |      |
      | initiatingUserIdentifier       | initiatingUserIdentifier   | Uuid |
      | currentContentStreamIdentifier | cs-2-identifier            | Uuid |
      | workspaceOwner                 | workspaceOwner             | Uuid |

  Scenario: modify the property in the nested workspace and publish afterwards works

    When the command "SetNodeProperty" is executed with payload:
      | Key                     | Value                                | Type |
      | contentStreamIdentifier | cs-2-identifier                      | Uuid |
      | nodeIdentifier          | node-identifier                      | Uuid |
      | propertyName            | text                                 |      |
      | value                   | {"value":"Modified","type":"string"} | json |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node "[node-identifier]" to exist in the graph projection
    And I expect the Node "[node-identifier]" to have the properties:
      | Key  | Value    |
      | text | Original |

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect a node "[node-identifier]" to exist in the graph projection
    And I expect the Node "[node-identifier]" to have the properties:
      | Key  | Value    |
      | text | Modified |


    # PUBLISHING
    When the command "PublishWorkspace" is executed with payload:
      | Key           | Value     | Type |
      | workspaceName | user-test |      |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node "[node-identifier]" to exist in the graph projection
    And I expect the Node "[node-identifier]" to have the properties:
      | Key  | Value    |
      | text | Modified |

  Scenario: modify the property in the nested workspace, do modification in live workspace; publish afterwards will not work because rebase is missing; then rebase and publish

    When the command "SetNodeProperty" is executed with payload:
      | Key                     | Value                                                  | Type |
      | contentStreamIdentifier | cs-2-identifier                                        | Uuid |
      | nodeIdentifier          | node-identifier                                        | Uuid |
      | propertyName            | text                                                   |      |
      | value                   | {"value":"Modified in user workspace","type":"string"} | json |

    When the command "SetNodeProperty" is executed with payload:
      | Key                     | Value                                                  | Type |
      | contentStreamIdentifier | cs-identifier                                          | Uuid |
      | nodeIdentifier          | node-identifier                                        | Uuid |
      | propertyName            | text                                                   |      |
      | value                   | {"value":"Modified in live workspace","type":"string"} | json |


    # PUBLISHING without rebase: error
    When the command "PublishWorkspace" is executed with payload and exceptions are caught:
      | Key           | Value     | Type |
      | workspaceName | user-test |      |

    Then the last command should have thrown an exception of type "BaseWorkspaceHasBeenModifiedInTheMeantime"

    # REBASING + Publishing: works now (TODO soft constraint check for old value)
    When the command "RebaseWorkspace" is executed with payload:
      | Key           | Value     | Type |
      | workspaceName | user-test |      |

    And the command "PublishWorkspace" is executed with payload:
      | Key           | Value     | Type |
      | workspaceName | user-test |      |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node "[node-identifier]" to exist in the graph projection
    And I expect the Node "[node-identifier]" to have the properties:
      | Key  | Value                      |
      | text | Modified in user workspace |




