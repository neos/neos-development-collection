Feature: When an editor authenticates for the first time, a new workspace is created.
  As this happens, a new content stream is forked from the live content stream.

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    unstructured: []
    Neos.ContentRepository:Root: []
    Neos.Neos:Sites: []
    Neos.Neos:Site: []
    """
    And the command "CreateRootWorkspace" is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | workspaceTitle           | Live                                 |      |
      | workspaceDescription     | The live workspace                   |      |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |      |

  @fixtures
  Scenario: Successfully created missing workspace with no other workspaces around
    Given I have the following nodes:
      | Identifier                           | Path           | Node Type       |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites         | Neos.Neos:Sites |
      | a14ebc05-f6a0-4939-9019-ef7a4bbdd3bc | /sites/my-site | Neos.Neos:Site  |
    And the following users exist:
      | username       | password | firstname | lastname | roles            |
      | me@example.com | password | John      | Doe      | RestrictedEditor |
    When I am authenticated as "me@example.com" via authentication provider "Neos.Neos:Backend"
    And the graph projection is fully up to date
    And I am in the active content stream of workspace "user-me-example-com" and Dimension Space Point {"coordinates": []}
    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:Workspace:user-me-example-com"
    And event at index 0 is of type "Neos.ContentRepository:WorkspaceWasCreated" with payload:
      | Key                            | Expected                             |
      | workspaceName                  | user-me-example-com                  |
      | baseWorkspaceName              | live                                 |
      | workspaceTitle                 | John Doe                             |
      | workspaceDescription           |                                      |
    And workspace "user-me-example-com" points to another content stream than workspace "live"
    And I expect a node identified by aggregate identifier "ecf40ad1-3119-0a43-d02e-55f8b5aa3c70" to exist in the subgraph
    And I expect a node identified by aggregate identifier "a14ebc05-f6a0-4939-9019-ef7a4bbdd3bc" to exist in the subgraph

  @fixtures
  Scenario: Successfully created missing workspace with a likewise named workspace around
    Given I have the following nodes:
      | Identifier                           | Path           | Node Type       |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites         | Neos.Neos:Sites |
      | a14ebc05-f6a0-4939-9019-ef7a4bbdd3bc | /sites/my-site | Neos.Neos:Site  |
    And the command "CreateWorkspace" is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | user-me-example-com                  |      |
      | baseWorkspaceName        | live                                 |      |
      | workspaceTitle           | John Doe                             |      |
      | workspaceDescription     |                                      |      |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |      |
      | workspaceOwner           | 00000000-0000-0000-0000-000000000000 |      |
    And the following users exist:
      | username       | password | firstname | lastname | roles            |
      | me@example.com | password | John      | Doe      | RestrictedEditor |
    When I am authenticated as "me@example.com" via authentication provider "Neos.Neos:Backend"
    And the graph projection is fully up to date
    And I am in the active content stream of workspace "user-me-example-com_1" and Dimension Space Point {"coordinates": []}
    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:Workspace:user-me-example-com_1"
    And event at index 0 is of type "Neos.ContentRepository:WorkspaceWasCreated" with payload:
      | Key                            | Expected                             |
      | workspaceName                  | user-me-example-com_1                |
      | baseWorkspaceName              | live                                 |
      | workspaceTitle                 | John Doe                             |
      | workspaceDescription           |                                      |
    And workspace "user-me-example-com_1" points to another content stream than workspace "live"
    And workspace "user-me-example-com_1" points to another content stream than workspace "user-me-example-com"
    And I expect a node identified by aggregate identifier "ecf40ad1-3119-0a43-d02e-55f8b5aa3c70" to exist in the subgraph
    And I expect a node identified by aggregate identifier "a14ebc05-f6a0-4939-9019-ef7a4bbdd3bc" to exist in the subgraph