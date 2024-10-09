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
    And I am user identified by "editor"

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
    Then an exception 'Invalid workspace name "some-name-that-exceeds-the-max-allowed-length" given. A workspace name has to consist of at most 36 lower case characters' should be thrown

  Scenario: Create root workspace with a name that is already used
    Given the root workspace "some-root-workspace" is created
    When the root workspace "some-root-workspace" is created
    Then an exception "The workspace some-root-workspace already exists" should be thrown

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

  Scenario: Change description of root workspace without metadata
    When a root workspace "some-root-workspace" exists without metadata
    And the description of workspace "some-root-workspace" is set to "Some new workspace description"
    Then the workspace "some-root-workspace" should have the following metadata:
      | Title               | Description                    | Classification | Owner user id |
      | some-root-workspace | Some new workspace description | ROOT           |               |


  Scenario: Create a single personal workspace
    When the root workspace "some-root-workspace" is created
    And the personal workspace "some-user-workspace" is created with the target workspace "some-root-workspace" for user "some-user-id"
    Then the workspace "some-user-workspace" should have the following metadata:
      | Title               | Description | Classification | Owner user id |
      | some-user-workspace |             | PERSONAL       | some-user-id  |

  Scenario: Create a single shared workspace
    When the root workspace "some-root-workspace" is created
    And the shared workspace "some-shared-workspace" is created with the target workspace "some-root-workspace"
    Then the workspace "some-shared-workspace" should have the following metadata:
      | Title                 | Description | Classification | Owner user id |
      | some-shared-workspace |             | SHARED         |               |

  Scenario: Get metadata of non-existing sub workspace
    Given the root workspace "some-root-workspace" is created
    When a workspace "some-workspace" with base workspace "some-root-workspace" exists without metadata
    Then the workspace "some-workspace" should have the following metadata:
      | Title          | Description | Classification | Owner user id |
      | some-workspace |             | UNKNOWN        |               |
