@contentrepository @adapters=DoctrineDBAL
Feature: Deactivate and Activate a Workspace

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        child1:
          type: 'Neos.ContentRepository.Testing:Content'
        child2:
          type: 'Neos.ContentRepository.Testing:Content'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | workspaceName   | "live"                        |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                    |
      | workspaceName             | "live"                                   |
      | nodeAggregateId           | "nody-mc-nodeface"                       |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint | {}                                       |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                 |
      | initialPropertyValues     | {"text": "Original text"}                |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |

  Scenario: Deactivating a workspace with no pending changes
    When the command DeactivateWorkspace is executed with payload:
      | Key               | Value       |
      | workspaceName     | "user-test" |
    Then workspace user-test has status DEACTIVATED

  Scenario: Deactivating an already deactivated Workspace fails
    And the command DeactivateWorkspace is executed with payload and exceptions are caught:
      | Key               | Value       |
      | workspaceName     | "user-test" |
    When the command DeactivateWorkspace is executed with payload and exceptions are caught:
      | Key               | Value       |
      | workspaceName     | "user-test" |
    Then the last command should have thrown an exception of type "WorkspaceIsDeactivated" with code 1765977861 and message:
    """
    The workspace "user-test" is deactivated
    """

  Scenario: Activating a deactivated workspace
    When the command DeactivateWorkspace is executed with payload:
      | Key               | Value       |
      | workspaceName     | "user-test" |
    And the command ActivateWorkspace is executed with payload:
      | Key                | Value                    |
      | workspaceName      | "user-test"              |
      | newContentStreamId | "user-new-cs-identifier" |
    Then workspace user-test has status UP_TO_DATE

    Given I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-new-cs-identifier;nody-mc-nodeface;{}

