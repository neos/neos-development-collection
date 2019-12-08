@fixtures
Feature: If content streams are not in use anymore by the workspace, they can be properly pruned - this is
  tested here.

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': {}
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                     | Value           |
      | workspaceName           | "live"          |
      | contentStreamIdentifier | "cs-identifier" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                      | Value                                  |
      | contentStreamIdentifier  | "cs-identifier"                        |
      | nodeAggregateIdentifier  | "root-node"                            |
      | nodeTypeName             | "Neos.ContentRepository:Root"          |
      | initiatingUserIdentifier | "00000000-0000-0000-0000-000000000000" |
    And the graph projection is fully up to date

  Scenario: content streams are marked as IN_USE_BY_WORKSPACE properly after creation
    Then the content stream "cs-identifier" has state "IN_USE_BY_WORKSPACE"
    Then the content stream "non-existing" has state ""

  Scenario: on creating a nested workspace, the new content stream is marked as IN_USE_BY_WORKSPACE.
    When the command CreateWorkspace is executed with payload:
      | Key                     | Value                |
      | workspaceName           | "user-test"          |
      | baseWorkspaceName       | "live"               |
      | contentStreamIdentifier | "user-cs-identifier" |

    Then the content stream "user-cs-identifier" has state "IN_USE_BY_WORKSPACE"

  Scenario: when rebasing a nested workspace, the new content stream will be marked as IN_USE_BY_WORKSPACE; and the old content stream is NO_LONGER_IN_USE.
    When the command CreateWorkspace is executed with payload:
      | Key                     | Value                |
      | workspaceName           | "user-test"          |
      | baseWorkspaceName       | "live"               |
      | contentStreamIdentifier | "user-cs-identifier" |
    And the graph projection is fully up to date
    When the command "RebaseWorkspace" is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then the current content stream has state "IN_USE_BY_WORKSPACE"
    And the content stream "user-cs-identifier" has state "NO_LONGER_IN_USE"


  Scenario: when pruning content streams, NO_LONGER_IN_USE content streams will be properly cleaned from the graph projection.
    When the command CreateWorkspace is executed with payload:
      | Key                     | Value                |
      | workspaceName           | "user-test"          |
      | baseWorkspaceName       | "live"               |
      | contentStreamIdentifier | "user-cs-identifier" |
    And the graph projection is fully up to date
    When the command "RebaseWorkspace" is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |
    And the graph projection is fully up to date

    When I prune unused content streams

    When I am in content stream "user-cs-identifier" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "root-node" not to exist in the subgraph

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "root-node" to exist in the subgraph

