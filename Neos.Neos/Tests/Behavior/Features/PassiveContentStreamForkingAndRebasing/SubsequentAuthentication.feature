Feature: Whenever an editor authenticates and already has a content stream,
  a new content stream is created as a rebased from master to the previous content stream.
  The new content stream is assigned to the workspace and the old content stream is removed.
  In case of conflicts during rebase, these are sent to the editor for manual resolution.

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    unstructured: []
    Neos.ContentRepository:Root: []
    Neos.Neos:Sites: []
    Neos.Neos:Site: []
    Neos.Neos.Testing:ArbitraryNode: []
    """
    And the following users exist:
      | username       | password | firstname | lastname | roles            |
      | me@example.com | password | John      | Doe      | RestrictedEditor |
    And the command "CreateRootWorkspace" is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | workspaceTitle           | Live                                 |      |
      | workspaceDescription     | The live workspace                   |      |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |      |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |      |
      | rootNodeIdentifier       | 5387cb08-2aaf-44dc-a8a1-483497aa0a03 |      |
    And the command "CreateNodeAggregateWithNode" is executed with payload:
      | Key                     | Value                                  | Type |
      | contentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d   |      |
      | nodeAggregateIdentifier | 35411439-94d1-4bd4-8fac-0646856c6a1f   |      |
      | nodeTypeName            | Neos.Neos:Sites                        |      |
      | dimensionSpacePoint     | {"coordinates": []}                    | json |
      | nodeIdentifier          | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81   |      |
      | parentNodeIdentifier    | 5387cb08-2aaf-44dc-a8a1-483497aa0a03   |      |
      | nodeName                | sites                                  |      |
    And the command "CreateNodeAggregateWithNode" is executed with payload:
      | Key                     | Value                                  | Type |
      | contentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d   |      |
      | nodeAggregateIdentifier | e8b6245f-5267-400a-91c8-efcadfe2a2fd   |      |
      | nodeTypeName            | Neos.Neos:Site                         |      |
      | dimensionSpacePoint     | {"coordinates": []}                    | json |
      | nodeIdentifier          | 61fd730a-7170-4e40-afa3-24b5a384a9cb   |      |
      | parentNodeIdentifier    | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81   |      |
      | nodeName                | sites                                  |      |
    And the workspace command "CreateWorkspace" is executed for user "me@example.com" with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | user-me-example-com                  |      |
      | baseWorkspaceName        | live                                 |      |
      | workspaceTitle           | John Doe                             |      |
      | workspaceDescription     |                                      |      |
      | contentStreamIdentifier  | 3e682506-ad16-40e7-bab1-b2022b72fb72 |      |
    And the command "CreateNodeAggregateWithNode" is executed with payload:
      | Key                     | Value                                  | Type |
      | contentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d   |      |
      | nodeAggregateIdentifier | e9b12aab2-a27f-4830-a3ab-83feec484be6  |      |
      | nodeTypeName            | Neos.Neos.Testing:ArbitraryNode        |      |
      | dimensionSpacePoint     | {"coordinates": []}                    | json |
      | nodeIdentifier          | a92eeca7-158a-41e2-b482-31cc836c8d16   |      |
      | parentNodeIdentifier    | 61fd730a-7170-4e40-afa3-24b5a384a9cb   |      |
      | nodeName                | sites                                  |      |

  @fixtures
  Scenario: Successfully rebasing a content stream when no conflicts arise
    When I am authenticated as "me@example.com" via authentication provider "Neos.Neos:Backend"
    And the graph projection is fully up to date
    Then I expect exactly 2 event to be published on stream "Neos.ContentRepository:Workspace:user-me-example-com"
    And event at index 1 is of type "Neos.ContentRepository:WorkspaceWasRebased" with payload:
      | Key                            | Expected                             |
      | workspaceName                  | user-me-example-com                  |
    And workspace "user-me-example-com" does not point to content stream "3e682506-ad16-40e7-bab1-b2022b72fb72"
    When I am in the active content stream of workspace "user-me-example-com" and Dimension Space Point {"coordinates": []}
    Then I expect a node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to exist in the graph projection
    And I expect a node "61fd730a-7170-4e40-afa3-24b5a384a9cb" to exist in the graph projection
    And I expect a node "a92eeca7-158a-41e2-b482-31cc836c8d16" to exist in the graph projection
    When I am in content stream "3e682506-ad16-40e7-bab1-b2022b72fb72" and Dimension Space Point {"coordinates": []}
    Then I expect a node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" not to exist in the graph projection
    And I expect a node "61fd730a-7170-4e40-afa3-24b5a384a9cb" not to exist in the graph projection
    And I expect a node "a92eeca7-158a-41e2-b482-31cc836c8d16" not to exist in the graph projection
