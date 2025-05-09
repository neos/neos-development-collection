@flowEntities
Feature: Neos WorkspaceService related features

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root': {}
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the following Neos users exist:
      | Id      | Username | First name | Last name | Roles                                            |
      | janedoe | jane.doe | Jane       | Doe       | Neos.Neos:Administrator                          |
      | johndoe | john.doe | John       | Doe       | Neos.Neos:RestrictedEditor,Neos.Neos:UserManager |
      | editor  | editor   | Edward     | Editor    | Neos.Neos:Editor                                 |

  Scenario: Create single root workspace without specifying title and description
    When the root workspace "some-root-workspace" is created
    Then the workspace "some-root-workspace" should have the following metadata:
      | Title               | Description | Classification | Owner user id |
      | some-root-workspace |             | ROOT           |               |

  Scenario: Create single root workspace with title and description
    When the root workspace "some-root-workspace" with title "Some root workspace" and description "Some description" is created
    Then the workspace "some-root-workspace" should have the following metadata:
      | Title               | Description      | Classification | Owner user id |
      | Some root workspace | Some description | ROOT           |               |

  Scenario: Create root workspace with a name that exceeds the workspace name max length
    When the root workspace "some-name-that-exceeds-the-max-allowed-length" is created
    Then an exception of type "InvalidArgumentException" should be thrown with message:
    """
    Invalid workspace name "some-name-that-exceeds-the-max-allowed-length" given. A workspace name has to consist of at most 36 lower case characters
    """

  Scenario: Create root workspace with a name that is already used
    Given the root workspace "some-root-workspace" is created
    When the root workspace "some-root-workspace" is created
    Then an exception of type "WorkspaceAlreadyExists" should be thrown

  Scenario: Get metadata of non-existing root workspace
    When a root workspace "some-root-workspace" exists without metadata
    Then the workspace "some-root-workspace" should have the following metadata:
      | Title               | Description | Classification | Owner user id |
      | some-root-workspace |             | ROOT           |               |

  Scenario: Change title of root workspace
    When the root workspace "some-root-workspace" is created
    And the title of workspace "some-root-workspace" is set to "Some new workspace title"
    Then the workspace "some-root-workspace" should have the following metadata:
      | Title                    | Description | Classification | Owner user id |
      | Some new workspace title |             | ROOT           |               |

  Scenario: Change title of root workspace to the same value
    When the root workspace "some-root-workspace" with title "some title" and description "some description" is created
    And the title of workspace "some-root-workspace" is set to "some title"
    Then the workspace "some-root-workspace" should have the following metadata:
      | Title      | Description      | Classification | Owner user id |
      | some title | some description | ROOT           |               |

  Scenario: Set title of root workspace without metadata
    When a root workspace "some-root-workspace" exists without metadata
    And the title of workspace "some-root-workspace" is set to "Some new workspace title"
    Then the workspace "some-root-workspace" should have the following metadata:
      | Title                    | Description | Classification | Owner user id |
      | Some new workspace title |             | ROOT           |               |

  Scenario: Change description of root workspace
    When the root workspace "some-root-workspace" is created
    And the description of workspace "some-root-workspace" is set to "Some new workspace description"
    Then the workspace "some-root-workspace" should have the following metadata:
      | Title               | Description                    | Classification | Owner user id |
      | some-root-workspace | Some new workspace description | ROOT           |               |

  Scenario: Change description of root workspace to the same value
    When the root workspace "some-root-workspace" with title "some title" and description "some description" is created
    And the description of workspace "some-root-workspace" is set to "some description"
    Then the workspace "some-root-workspace" should have the following metadata:
      | Title      | Description      | Classification | Owner user id |
      | some title | some description | ROOT           |               |

  Scenario: Change description of root workspace without metadata
    When a root workspace "some-root-workspace" exists without metadata
    And the description of workspace "some-root-workspace" is set to "Some new workspace description"
    Then the workspace "some-root-workspace" should have the following metadata:
      | Title               | Description                    | Classification | Owner user id |
      | some-root-workspace | Some new workspace description | ROOT           |               |

  Scenario: Create a single personal workspace
    When the root workspace "some-root-workspace" is created
    Then the user "jane.doe" does not have a personal workspace
    And the personal workspace "some-user-workspace" is created with the target workspace "some-root-workspace" for user "jane.doe"
    Then the personal workspace for user "jane.doe" is "some-user-workspace"
    Then the workspace "some-user-workspace" should have the following metadata:
      | Title               | Description | Classification | Owner user id |
      | some-user-workspace |             | PERSONAL       | janedoe       |

  Scenario: Personal workspace names are unique https://github.com/neos/neos-development-collection/issues/2850
    And the following Neos users exist:
      | Id  | Username  | First name | Last name | Roles                   |
      | 123 | test-user | Test       | User      | Neos.Neos:Administrator |
      | 456 | test.user | Test       | User      | Neos.Neos:Administrator |

    When the root workspace "live" is created

    When a personal workspace for user "test-user" is created
    Then the personal workspace for user "test-user" is "test-user"

    When a personal workspace for user "test.user" is created
    Then the personal workspace for user "test.user" is "test-user-1"

  Scenario: A user cannot have multiple personal workspaces
    When the root workspace "some-root-workspace" is created
    And the personal workspace "a-user-workspace" is created with the target workspace "some-root-workspace" for user "jane.doe"
    And the personal workspace "b-user-workspace" is created with the target workspace "some-root-workspace" for user "jane.doe"
    Then an exception of type "RuntimeException" should be thrown with message:
    """
    Failed to create personal workspace "b-user-workspace" for user with id "janedoe", because the workspace "a-user-workspace" is already assigned to the user
    """
    Then the personal workspace for user "jane.doe" is "a-user-workspace"

  Scenario: Create a single shared workspace
    When the root workspace "some-root-workspace" is created
    And the shared workspace "some-shared-workspace" is created with the target workspace "some-root-workspace"
    Then the workspace "some-shared-workspace" should have the following metadata:
      | Title                 | Description | Classification | Owner user id |
      | some-shared-workspace |             | SHARED         |               |

  Scenario: Get metadata of a sub workspace which is directly created via the content repository
    Given the root workspace "some-root-workspace" is created
    # dont use the workspace service here:
    When the command CreateWorkspace is executed with payload:
      | Key                | Value                 |
      | workspaceName      | "some-workspace"      |
      | baseWorkspaceName  | "some-root-workspace" |
      | newContentStreamId | "any-cs"              |

    Then the workspace "some-workspace" should have the following metadata:
      | Title          | Description | Classification | Owner user id |
      | some-workspace |             | UNKNOWN        |               |

  Scenario: Get metadata or roles if the workspace does not exist
    Then the metadata for workspace "non-existing-workspace" does not exist
    Then the roles for workspace "non-existing-workspace" does not exist

    When the root workspace "some-root-workspace" with title "Some root workspace" and description "Some description" is created
    And the role COLLABORATOR is assigned to workspace "some-root-workspace" for group "Neos.Neos:AbstractEditor"
    Given the workspace "some-root-workspace" is deleted

    Then the metadata for workspace "some-root-workspace" does not exist
    Then the roles for workspace "some-root-workspace" does not exist

  Scenario: Assign role to non-existing workspace
    When the role COLLABORATOR is assigned to workspace "some-workspace" for group "Neos.Neos:AbstractEditor"
    Then an exception of type "WorkspaceDoesNotExist" should be thrown with message:
    """
    The workspace "some-workspace" does not exist in content repository "default"
    """

  Scenario: Assign group role to root workspace
    Given the root workspace "some-root-workspace" is created
    When the role COLLABORATOR is assigned to workspace "some-root-workspace" for group "Neos.Neos:AbstractEditor"
    Then the workspace "some-root-workspace" should have the following role assignments:
      | Subject type | Subject                  | Role         |
      | GROUP        | Neos.Neos:AbstractEditor | COLLABORATOR |

  Scenario: Assign a role to the same group twice
    Given the root workspace "some-root-workspace" is created
    When the role COLLABORATOR is assigned to workspace "some-root-workspace" for group "Neos.Neos:AbstractEditor"
    And the role MANAGER is assigned to workspace "some-root-workspace" for group "Neos.Neos:AbstractEditor"
    Then an exception of type "RuntimeException" should be thrown with message:
    """
    Failed to assign role for workspace "some-root-workspace" to subject "Neos.Neos:AbstractEditor" (Content Repository "default"): There is already a role assigned for that user/group, please unassign that first
    """

  Scenario: Assign user role to root workspace
    Given the root workspace "some-root-workspace" is created
    When the role MANAGER is assigned to workspace "some-root-workspace" for user "jane.doe"
    Then the workspace "some-root-workspace" should have the following role assignments:
      | Subject type | Subject | Role    |
      | USER         | janedoe | MANAGER |

  Scenario: Assign a role to the same user twice
    Given the root workspace "some-root-workspace" is created
    When the role COLLABORATOR is assigned to workspace "some-root-workspace" for user "john.doe"
    And the role MANAGER is assigned to workspace "some-root-workspace" for user "john.doe"
    Then an exception of type "RuntimeException" should be thrown with message:
    """
    Failed to assign role for workspace "some-root-workspace" to subject "johndoe" (Content Repository "default"): There is already a role assigned for that user/group, please unassign that first
    """

  Scenario: Unassign role from non-existing workspace
    When the role for group "Neos.Neos:AbstractEditor" is unassigned from workspace "some-workspace"
    Then an exception of type "WorkspaceDoesNotExist" should be thrown with message:
    """
    The workspace "some-workspace" does not exist in content repository "default"
    """

  Scenario: Unassign role from workspace that has not been assigned before
    Given the root workspace "some-root-workspace" is created
    When the role for group "Neos.Neos:AbstractEditor" is unassigned from workspace "some-root-workspace"
    Then an exception of type "RuntimeException" should be thrown with message:
    """
    Failed to unassign role for subject "Neos.Neos:AbstractEditor" from workspace "some-root-workspace" (Content Repository "default"): No role assignment exists for this user/group
    """

  Scenario: Assign two roles, then unassign one
    Given the root workspace "some-root-workspace" is created
    And the role MANAGER is assigned to workspace "some-root-workspace" for user "jane.doe"
    And the role COLLABORATOR is assigned to workspace "some-root-workspace" for group "Neos.Neos:AbstractEditor"
    Then the workspace "some-root-workspace" should have the following role assignments:
      | Subject type | Subject                  | Role         |
      | GROUP        | Neos.Neos:AbstractEditor | COLLABORATOR |
      | USER         | janedoe                  | MANAGER      |
    When the role for group "Neos.Neos:AbstractEditor" is unassigned from workspace "some-root-workspace"
    Then the workspace "some-root-workspace" should have the following role assignments:
      | Subject type | Subject | Role    |
      | USER         | janedoe | MANAGER |

  Scenario: Workspace permissions for personal workspace for admin user
    Given the root workspace "live" is created
    When a personal workspace for user "jane.doe" is created
    Then the personal workspace for user "jane.doe" is "jane-doe"
    Then the workspace "jane-doe" should have the following metadata:
      | Title    | Description | Classification | Owner user id |
      | Jane Doe |             | PERSONAL       | janedoe       |
    And the Neos user "jane.doe" should have the permissions "read,write,manage" for workspace "jane-doe"
    And the Neos user "john.doe" should have no permissions for workspace "jane-doe"
    And the Neos user "editor" should have no permissions for workspace "jane-doe"

  Scenario: Workspace permissions for personal workspace for editor user
    Given the root workspace "live" is created
    When a personal workspace for user "editor" is created
    Then the workspace "edward-editor" should have the following metadata:
      | Title         | Description | Classification | Owner user id |
      | Edward Editor |             | PERSONAL       | editor        |
    And the Neos user "jane.doe" should have the permissions "manage" for workspace "edward-editor"
    And the Neos user "john.doe" should have no permissions for workspace "edward-editor"
    And the Neos user "editor" should have the permissions "read,write,manage" for workspace "edward-editor"

  Scenario: Default workspace permissions
    When the root workspace "some-root-workspace" is created
    Then the Neos user "jane.doe" should have the permissions "manage" for workspace "some-root-workspace"
    And the Neos user "john.doe" should have no permissions for workspace "some-root-workspace"
    And the Neos user "editor" should have no permissions for workspace "some-root-workspace"

  Scenario: Workspace permissions for collaborator by group
    When the root workspace "some-root-workspace" is created
    When the role COLLABORATOR is assigned to workspace "some-root-workspace" for group "Neos.Neos:AbstractEditor"
    Then the Neos user "jane.doe" should have the permissions "read,write,manage" for workspace "some-root-workspace"
    And the Neos user "john.doe" should have the permissions "read,write" for workspace "some-root-workspace"
    And the Neos user "editor" should have the permissions "read,write" for workspace "some-root-workspace"

  Scenario: Workspace permissions for manager by group
    When the root workspace "some-root-workspace" is created
    When the role MANAGER is assigned to workspace "some-root-workspace" for group "Neos.Neos:AbstractEditor"
    Then the Neos user "jane.doe" should have the permissions "read,write,manage" for workspace "some-root-workspace"
    And the Neos user "john.doe" should have the permissions "read,write,manage" for workspace "some-root-workspace"
    And the Neos user "editor" should have the permissions "read,write,manage" for workspace "some-root-workspace"

  Scenario: Workspace permissions for collaborator by user
    When the root workspace "some-root-workspace" is created
    When the role COLLABORATOR is assigned to workspace "some-root-workspace" for user "john.doe"
    Then the Neos user "jane.doe" should have the permissions "manage" for workspace "some-root-workspace"
    And the Neos user "john.doe" should have the permissions "read,write" for workspace "some-root-workspace"
    And the Neos user "editor" should have no permissions for workspace "some-root-workspace"

  Scenario: Workspace permissions for manager by user
    When the root workspace "some-root-workspace" is created
    When the role MANAGER is assigned to workspace "some-root-workspace" for user "john.doe"
    Then the Neos user "jane.doe" should have the permissions "manage" for workspace "some-root-workspace"
    And the Neos user "john.doe" should have the permissions "read,write,manage" for workspace "some-root-workspace"
    And the Neos user "editor" should have no permissions for workspace "some-root-workspace"

  Scenario: Overlapping workspace permissions 1
    When the root workspace "some-root-workspace" is created
    When the role MANAGER is assigned to workspace "some-root-workspace" for group "Neos.Neos:Editor"
    When the role COLLABORATOR is assigned to workspace "some-root-workspace" for user "editor"
    And the Neos user "editor" should have the permissions "read,write,manage" for workspace "some-root-workspace"

  Scenario: Overlapping workspace permissions 2
    When the root workspace "some-root-workspace" is created
    When the role COLLABORATOR is assigned to workspace "some-root-workspace" for group "Neos.Neos:Editor"
    When the role MANAGER is assigned to workspace "some-root-workspace" for user "editor"
    And the Neos user "editor" should have the permissions "read,write,manage" for workspace "some-root-workspace"

  Scenario: Permissions for workspace without metadata
    Given a root workspace "some-root-workspace" exists without metadata
    When the role COLLABORATOR is assigned to workspace "some-root-workspace" for user "jane.doe"
    Then the Neos user "jane.doe" should have the permissions "read,write,manage" for workspace "some-root-workspace"
    And the Neos user "john.doe" should have no permissions for workspace "some-root-workspace"
    And the Neos user "editor" should have no permissions for workspace "some-root-workspace"
