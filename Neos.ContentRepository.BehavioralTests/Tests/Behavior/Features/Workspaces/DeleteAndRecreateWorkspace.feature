Feature: Test for delete and recreate workspace

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

  Scenario: Delete workspace and create a new one with the same name works
    And the command DeleteWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |

    Then I expect exactly 2 event to be published on stream "Workspace:user-test"
    And event at index 1 is of type "WorkspaceWasRemoved" with payload:
      | Key                  | Expected                      |
      | workspaceName        | "user-test"                   |

    Then I expect the workspace "user-test" to not exist
    Then I expect the following workspaces to exist:
      | name   | base workspace | status       | content stream  | publishable changes |
      | "live" | null           | "UP_TO_DATE" | "cs-identifier" | false               |

    # recreate workspace
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                    |
      | workspaceName      | "user-test"              |
      | baseWorkspaceName  | "live"                   |
      | newContentStreamId | "user-cs-identifier-new" |

    Then I expect the following workspaces to exist:
      | name        | base workspace | status       | content stream           | publishable changes |
      | "live"      | null           | "UP_TO_DATE" | "cs-identifier"          | false               |
      | "user-test" | "live"         | "UP_TO_DATE" | "user-cs-identifier-new" | false               |

    Given I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier-new;nody-mc-nodeface;{}
