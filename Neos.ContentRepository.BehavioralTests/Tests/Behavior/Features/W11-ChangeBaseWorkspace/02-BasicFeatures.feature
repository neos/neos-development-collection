@contentrepository @adapters=DoctrineDBAL
Feature: Change base workspace works :D what else

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
    And using identifier "testing", I define a content repository
    And I am in content repository "testing"
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

    And the command CreateWorkspace is executed with payload:
      | Key                | Value                  |
      | workspaceName      | "shared"               |
      | baseWorkspaceName  | "live"                 |
      | newContentStreamId | "shared-cs-identifier" |

  Scenario: Change base workspace is a no-op if the base already matches
    When the command ChangeBaseWorkspace is executed with payload:
      | Key               | Value       |
      | workspaceName     | "user-test" |
      | baseWorkspaceName | "live"      |

    Then I expect exactly 1 event to be published on stream "Workspace:user-test"
    And event at index 0 is of type "WorkspaceWasCreated" with payload:
      | Key                  | Expected                      |
      | workspaceName        | "user-test"                   |
      | baseWorkspaceName    | "live"                        |
      | newContentStreamId   | "user-cs-identifier"          |

    Given I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier;nody-mc-nodeface;{}

  Scenario: Change base workspace is a no-op if the base already matches but the workspace is outdated
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                    |
      | workspaceName             | "live"                                   |
      | nodeAggregateId           | "holy-nody"                       |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint | {}                                       |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                 |
      | initialPropertyValues     | {"text": "New node in live"}                |

    Then workspaces user-test has status OUTDATED

    When the command ChangeBaseWorkspace is executed with payload:
      | Key               | Value       |
      | workspaceName     | "user-test" |
      | baseWorkspaceName | "live"      |

    Then workspaces user-test has status OUTDATED

    Then I expect exactly 1 event to be published on stream "Workspace:user-test"
    And event at index 0 is of type "WorkspaceWasCreated" with payload:
      | Key                  | Expected                      |
      | workspaceName        | "user-test"                   |
      | baseWorkspaceName    | "live"                        |
      | newContentStreamId   | "user-cs-identifier"          |

    Given I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "holy-nody" to lead to no node

    Given I am in workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "holy-nody" to lead to node cs-identifier;holy-nody;{}

  Scenario: Change base workspace if user has no changes and is up to date with new base
    When the command ChangeBaseWorkspace is executed with payload:
      | Key                | Value                        |
      | workspaceName      | "user-test"                  |
      | baseWorkspaceName  | "shared"                     |
      | newContentStreamId | "user-rebased-cs-identifier" |

    Then workspaces user-test has status UP_TO_DATE

    Given I am in workspace "user-test" and dimension space point {}
    # todo no fork needed?
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-rebased-cs-identifier;nody-mc-nodeface;{}

  Scenario: Change base workspace if user has no changes and is not up to date with new base
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                    |
      | workspaceName             | "shared"                                 |
      | nodeAggregateId           | "holy-nody"                              |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint | {}                                       |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                 |
      | initialPropertyValues     | {"text": "New node in shared"}           |

    Given I am in workspace "shared" and dimension space point {}
    Then I expect node aggregate identifier "holy-nody" to lead to node shared-cs-identifier;holy-nody;{}

    When the command ChangeBaseWorkspace is executed with payload:
      | Key                | Value                        |
      | workspaceName      | "user-test"                  |
      | baseWorkspaceName  | "shared"                     |
      | newContentStreamId | "user-rebased-cs-identifier" |

    Then workspaces user-test has status UP_TO_DATE

    Given I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "holy-nody" to lead to node user-rebased-cs-identifier;holy-nody;{}
    And I expect this node to have the following properties:
      | Key  | Value                |
      | text | "New node in shared" |
