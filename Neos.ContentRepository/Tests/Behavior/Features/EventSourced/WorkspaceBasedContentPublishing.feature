@fixtures
Feature: Workspace based content publishing

  create a root workspace;

  Background:
    Given I have no content dimensions
    And the command "CreateRootWorkspace" is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | workspaceTitle           | Live                                 |      |
      | workspaceDescription     | The live workspace                   |      |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |      |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |      |
      | rootNodeIdentifier       | 5387cb08-2aaf-44dc-a8a1-483497aa0a03 |      |

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
    Then I expect exactly 2 events to be published on stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d"
    And event at index 0 is of type "Neos.ContentRepository:ContentStreamWasCreated" with payload:
      | Key                      | Expected                             |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |
    # Event 1 is the root Node Created event (we can skip this here, it is tested somewhere else)

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

