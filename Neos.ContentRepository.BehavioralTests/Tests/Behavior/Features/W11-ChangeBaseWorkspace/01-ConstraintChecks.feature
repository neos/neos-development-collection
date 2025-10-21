@contentrepository @adapters=DoctrineDBAL
Feature: Change base workspace constraints

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

    And the command CreateWorkspace is executed with payload:
      | Key                | Value                  |
      | workspaceName      | "shared"               |
      | baseWorkspaceName  | "live"                 |
      | newContentStreamId | "shared-cs-identifier" |

  Scenario: Changing the base workspace is not allowed for root workspaces
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value                 |
      | workspaceName      | "groot"               |
      | newContentStreamId | "cs-groot-identifier" |

    When the command ChangeBaseWorkspace is executed with payload and exceptions are caught:
      | Key               | Value   |
      | workspaceName     | "live"  |
      | baseWorkspaceName | "groot" |

    Then the last command should have thrown an exception of type "WorkspaceHasNoBaseWorkspaceName"

  Scenario: Changing the base workspace is not allowed if there are pending changes
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

    When the command ChangeBaseWorkspace is executed with payload and exceptions are caught:
      | Key                | Value                        |
      | workspaceName      | "user-test"                  |
      | baseWorkspaceName  | "shared"                     |
      | newContentStreamId | "user-rebased-cs-identifier" |

    Then the last command should have thrown an exception of type "WorkspaceContainsPublishableChanges"

  Scenario: Changing the base workspace does not work if the new base is the current workspace (cyclic)
    When the command ChangeBaseWorkspace is executed with payload and exceptions are caught:
      | Key                | Value                        |
      | workspaceName      | "user-test"                  |
      | baseWorkspaceName  | "user-test"                  |

    Then the last command should have thrown an exception of type "BaseWorkspaceEqualsWorkspaceException"

  Scenario: Changing the base workspace does not work if the new base is a base of the current (cyclic)
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                           |
      | workspaceName      | "shared-branched"               |
      | baseWorkspaceName  | "shared"                        |
      | newContentStreamId | "shared-branched-cs-identifier" |

    When the command ChangeBaseWorkspace is executed with payload and exceptions are caught:
      | Key               | Value             |
      | workspaceName     | "shared"          |
      | baseWorkspaceName | "shared-branched" |

    Then the last command should have thrown an exception of type "CircularRelationBetweenWorkspacesException"

  Scenario: Changing the base workspace does not work if the new base complexly cyclic
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                    |
      | workspaceName      | "shared-a"               |
      | baseWorkspaceName  | "shared"                 |
      | newContentStreamId | "shared-a-cs-identifier" |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                     |
      | workspaceName      | "shared-a1"               |
      | baseWorkspaceName  | "shared"                  |
      | newContentStreamId | "shared-a1-cs-identifier" |
    When the command ChangeBaseWorkspace is executed with payload and exceptions are caught:
      | Key               | Value             |
      | workspaceName     | "shared"          |
      | baseWorkspaceName | "shared-a1" |
    Then the last command should have thrown an exception of type "CircularRelationBetweenWorkspacesException"
