@contentrepository @adapters=DoctrineDBAL
Feature: Deactivate/Activate workspace constraints

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

    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |

  Scenario: Deactivating the workspace is not allowed for workspaces with other workspaces depending on it
    When the command DeactivateWorkspace is executed with payload and exceptions are caught:
      | Key               | Value   |
      | workspaceName     | "live" |

    Then the last command should have thrown an exception of type "WorkspaceHasWorkspacesDependingOnIt"

  Scenario: Activating the workspace is not allowed for active workspaces
    When the command ActivateWorkspace is executed with payload and exceptions are caught:
      | Key                | Value                    |
      | workspaceName      | "user-test"              |
      | newContentStreamId | "new-user-cs-identifier" |

    Then the last command should have thrown an exception of type "WorkspaceIsActivated" with code 1766069245 and message:
    """
    The workspace "user-test" is activated
    """

  Scenario: Deactivating the workspace is not allowed if there are pending changes
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                    |
      | workspaceName             | "user-test"                              |
      | nodeAggregateId           | "holy-nody"                              |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint | {}                                       |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                 |
      | initialPropertyValues     | {"text": "New node in shared"}           |

    Given I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "holy-nody" to lead to node user-cs-identifier;holy-nody;{}

    When the command DeactivateWorkspace is executed with payload and exceptions are caught:
      | Key                | Value                        |
      | workspaceName      | "user-test"                  |

    Then the last command should have thrown an exception of type "WorkspaceContainsPublishableChanges"
