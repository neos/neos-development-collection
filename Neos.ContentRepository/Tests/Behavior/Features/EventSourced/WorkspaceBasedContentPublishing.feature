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

    And the command "CreateRootWorkspace" is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | workspaceTitle           | Live                                 |      |
      | workspaceDescription     | The live workspace                   |      |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |      |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |      |
      | rootNodeIdentifier       | 5387cb08-2aaf-44dc-a8a1-483497aa0a03 |      |
      | rootNodeTypeName         | Neos.ContentRepository:Root          |      |

    And the command "CreateNodeAggregateWithNode" is executed with payload:
      | Key                     | Value                                  | Type |
      | contentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d   |      |
      | nodeAggregateIdentifier | 35411439-94d1-4bd4-8fac-0646856c6a1f   |      |
      | nodeTypeName            | Neos.ContentRepository.Testing:Content |      |
      | dimensionSpacePoint     | {"coordinates": []}                    | json |
      | nodeIdentifier          | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81   |      |
      | parentNodeIdentifier    | 5387cb08-2aaf-44dc-a8a1-483497aa0a03   |      |
      | nodeName                | foo                                    |      |

    When the command "SetNodeProperty" is executed with payload:
      | Key                     | Value                                | Type |
      | contentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d |      |
      | nodeIdentifier          | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81 |      |
      | propertyName            | text                                 |      |
      | value                   | {"value":"Original","type":"string"} | json |

    And the command "CreateWorkspace" is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | user-test                            |      |
      | baseWorkspaceName        | live                                 |      |
      | workspaceTitle           | Test User WS                         |      |
      | workspaceDescription     | The user-test workspace              |      |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |      |
      | contentStreamIdentifier  | 3e682506-ad16-40e7-bab1-b2022b72fb72 |      |
      | workspaceOwner           | 00000000-0000-0000-0000-000000000000 |      |


  Scenario: Basic events are emitted

    # LIVE workspace
    Then I expect exactly 3 events to be published on stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d"
    And event at index 0 is of type "Neos.ContentRepository:ContentStreamWasCreated" with payload:
      | Key                      | Expected                             |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |
    # Event 1 is the root Node Created event (we can skip this here, it is tested somewhere else); Event 2 is the SetProperty

    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:Workspace:live"
    And event at index 0 is of type "Neos.ContentRepository:RootWorkspaceWasCreated" with payload:
      | Key                            | Expected                             |
      | workspaceName                  | live                                 |
      | workspaceTitle                 | Live                                 |
      | workspaceDescription           | The live workspace                   |
      | initiatingUserIdentifier       | 00000000-0000-0000-0000-000000000000 |
      | currentContentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d |

    # USER workspace
    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:ContentStream:3e682506-ad16-40e7-bab1-b2022b72fb72"
    And event at index 0 is of type "Neos.ContentRepository:ContentStreamWasForked" with payload:
      | Key                           | Expected                             |
      | contentStreamIdentifier       | 3e682506-ad16-40e7-bab1-b2022b72fb72 |
      | sourceContentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d |

    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:Workspace:user-test"
    And event at index 0 is of type "Neos.ContentRepository:WorkspaceWasCreated" with payload:
      | Key                            | Expected                             |
      | workspaceName                  | user-test                            |
      | baseWorkspaceName              | live                                 |
      | workspaceTitle                 | Test User WS                         |
      | workspaceDescription           | The user-test workspace              |
      | initiatingUserIdentifier       | 00000000-0000-0000-0000-000000000000 |
      | currentContentStreamIdentifier | 3e682506-ad16-40e7-bab1-b2022b72fb72 |
      | workspaceOwner                 | 00000000-0000-0000-0000-000000000000 |

  Scenario: modify the property in the nested workspace and publish afterwards works

    When the command "SetNodeProperty" is executed with payload:
      | Key                     | Value                                | Type |
      | contentStreamIdentifier | 3e682506-ad16-40e7-bab1-b2022b72fb72 |      |
      | nodeIdentifier          | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81 |      |
      | propertyName            | text                                 |      |
      | value                   | {"value":"Modified","type":"string"} | json |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {"coordinates": []}
    Then I expect a node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to exist in the graph projection
    And I expect the Node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to have the properties:
      | Key  | Value    |
      | text | Original |

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {"coordinates": []}
    Then I expect a node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to exist in the graph projection
    And I expect the Node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to have the properties:
      | Key  | Value    |
      | text | Modified |


    # PUBLISHING
    When the command "PublishWorkspace" is executed with payload:
      | Key           | Value     | Type |
      | workspaceName | user-test |      |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {"coordinates": []}
    Then I expect a node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to exist in the graph projection
    And I expect the Node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to have the properties:
      | Key  | Value    |
      | text | Modified |

  Scenario: modify the property in the nested workspace, do modification in live workspace; publish afterwards will not work because rebase is missing; then rebase and publish

    When the command "SetNodeProperty" is executed with payload:
      | Key                     | Value                                                  | Type |
      | contentStreamIdentifier | 3e682506-ad16-40e7-bab1-b2022b72fb72                   |      |
      | nodeIdentifier          | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81                   |      |
      | propertyName            | text                                                   |      |
      | value                   | {"value":"Modified in user workspace","type":"string"} | json |

    When the command "SetNodeProperty" is executed with payload:
      | Key                     | Value                                                  | Type |
      | contentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d                   |      |
      | nodeIdentifier          | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81                   |      |
      | propertyName            | text                                                   |      |
      | value                   | {"value":"Modified in live workspace","type":"string"} | json |


    # PUBLISHING without rebase: error
    When the command "PublishWorkspace" is executed with payload and exceptions are catched:
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

    When I am in the active content stream of workspace "live" and Dimension Space Point {"coordinates": []}
    Then I expect a node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to exist in the graph projection
    And I expect the Node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to have the properties:
      | Key  | Value                      |
      | text | Modified in user workspace |




