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

  Scenario: Create single root workspace
    When the root workspace "Some root workspace" is created
    Then the following workspaces should exist:
      | Name                | Base workspace | Title               | Classification |
      | some-root-workspace |                | Some root workspace | ROOT           |

  Scenario: Create root workspace with a title that exceeds the workspace name max length
    When the root workspace "Some root workspace with a title that exceeds the max name length" is created
    Then the following workspaces should exist:
      | Name                                 | Base workspace | Title                                                             | Classification |
      | some-root-workspace-with-a-title-tha |                | Some root workspace with a title that exceeds the max name length | ROOT           |

  Scenario: Create multiple root workspaces with the same derived name
    When the root workspace "Root" is created
    And the root workspace "Root 5" is created
    And the root workspace "root" is created
    And the root workspace "-Root" is created
    And the root workspace "Root" is created
    And the root workspace "Root" is created
    And the root workspace "Root" is created
    And the root workspace "Root" is created
    And the root workspace "Root" is created
    Then the following workspaces should exist:
      | Name   | Base workspace | Title  | Classification |
      | root   |                | Root   | ROOT           |
      | root-1 |                | root   | ROOT           |
      | root-2 |                | -Root  | ROOT           |
      | root-3 |                | Root   | ROOT           |
      | root-4 |                | Root   | ROOT           |
      | root-5 |                | Root 5 | ROOT           |
      | root-6 |                | Root   | ROOT           |
      | root-7 |                | Root   | ROOT           |
      | root-8 |                | Root   | ROOT           |

  Scenario: Create multiple root workspaces with the same derived name with a lenght that exceeds the allowed max length
    And the root workspace "some-root-workspace-with-a-long-title" is created
    And the root workspace "some-root-workspace-with-a-long-title" is created
    And the root workspace "some-root-workspace-with-a-long-title" is created
    Then the following workspaces should exist:
      | Name                                 | Base workspace | Title                                 | Classification |
      | some-root-workspace-with-a-long-ti-1 |                | some-root-workspace-with-a-long-title | ROOT           |
      | some-root-workspace-with-a-long-ti-2 |                | some-root-workspace-with-a-long-title | ROOT           |
      | some-root-workspace-with-a-long-titl |                | some-root-workspace-with-a-long-title | ROOT           |

  Scenario: Create a single personal workspace
    When the root workspace "Some root workspace" is created
    And the personal workspace "Some user workspace" is created with the target workspace "some-root-workspace"
    Then the following workspaces should exist:
      | Name                | Base workspace      | Title               | Classification |
      | some-root-workspace |                     | Some root workspace | ROOT           |
      | some-user-workspace | some-root-workspace | Some user workspace | PERSONAL       |

  Scenario: Create a single shared workspace
    When the root workspace "Some root workspace" is created
    And the shared workspace "Some shared workspace" is created with the target workspace "some-root-workspace"
    Then the following workspaces should exist:
      | Name                  | Base workspace      | Title                 | Classification |
      | some-root-workspace   |                     | Some root workspace   | ROOT           |
      | some-shared-workspace | some-root-workspace | Some shared workspace | SHARED         |

  Scenario: Creating several workspaces with the same derived names
    When the root workspace "root 1" is created
    And the root workspace "Root 2" is created
    And the personal workspace "User 1" is created with the target workspace "root-1"
    And the personal workspace "User 2" is created with the target workspace "root-2"
    And the personal workspace "root 1" is created with the target workspace "root-2"
    And the shared workspace "Root 1" is created with the target workspace "root-2"
    And the shared workspace "Shared 2" is created with the target workspace "root-1"
    Then the following workspaces should exist:
      | Name     | Base workspace | Title    | Classification |
      | root-1   |                | root 1   | ROOT           |
      | root-1-1 | root-2         | root 1   | PERSONAL       |
      | root-1-2 | root-2         | Root 1   | SHARED         |
      | root-2   |                | Root 2   | ROOT           |
      | shared-2 | root-1         | Shared 2 | SHARED         |
      | user-1   | root-1         | User 1   | PERSONAL       |
      | user-2   | root-2         | User 2   | PERSONAL       |
