@contentrepository @adapters=DoctrineDBAL
Feature: Workspace rebasing - via workspace maintenance service

  These are the test cases for the workspace maintenance service to properly do its job

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document':
      properties:
        text:
          type: string
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value                 |
      | workspaceName      | "other-root"          |
      | newContentStreamId | "other-cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | nodeTypeName                            | parentNodeAggregateId  | initialPropertyValues |
      | nody-mc-nodeface | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | {"text": "Original"}  |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                     |
      | workspaceName      | "leaf-user-test"          |
      | baseWorkspaceName  | "user-test"               |
      | newContentStreamId | "leaf-user-cs-identifier" |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                      |
      | workspaceName      | "other-user-test"          |
      | baseWorkspaceName  | "other-root"               |
      | newContentStreamId | "other-user-cs-identifier" |

  Scenario: The workspace maintenance service does nothing if there are no outdated workspaces

    When outdated workspaces are rebased
     # only the creation events are expected
    Then I expect exactly 1 events to be published on stream "Workspace:live"
    And I expect exactly 1 events to be published on stream "Workspace:other-root"
    And I expect exactly 1 events to be published on stream "Workspace:user-test"
    And I expect exactly 1 events to be published on stream "Workspace:leaf-user-test"
    And I expect exactly 1 events to be published on stream "Workspace:other-user-test"
    Then workspaces live,other-root,user-test,leaf-user-test,other-user-test have status UP_TO_DATE

  Scenario: The workspace maintenance service does nothing if there are only changes in a leaf workspace

    Given the command SetNodeProperties is executed with payload:
      | Key                       | Value               |
      | workspaceName             | "leaf-user-test"    |
      | nodeAggregateId           | "nody-mc-nodeface"  |
      | originDimensionSpacePoint | {}                  |
      | propertyValues            | {"text": "my-text"} |

    When outdated workspaces are rebased
     # only the creation events are expected
    Then I expect exactly 1 events to be published on stream "Workspace:live"
    And I expect exactly 1 events to be published on stream "Workspace:other-root"
    And I expect exactly 1 events to be published on stream "Workspace:user-test"
    And I expect exactly 1 events to be published on stream "Workspace:leaf-user-test"
    And I expect exactly 1 events to be published on stream "Workspace:other-user-test"
    Then workspaces live,other-root,user-test,leaf-user-test,other-user-test have status UP_TO_DATE

  Scenario: The workspace maintenance service only rebases a leaf workspace if its parent has changes making it outdated

    Given the command SetNodeProperties is executed with payload:
      | Key                       | Value               |
      | workspaceName             | "user-test"         |
      | nodeAggregateId           | "nody-mc-nodeface"  |
      | originDimensionSpacePoint | {}                  |
      | propertyValues            | {"text": "my-text"} |

    When outdated workspaces are rebased
    Then I expect exactly 1 events to be published on stream "Workspace:live"
    And I expect exactly 1 events to be published on stream "Workspace:other-root"
    And I expect exactly 1 events to be published on stream "Workspace:user-test"
    # rebase has happened here
    And I expect exactly 2 events to be published on stream "Workspace:leaf-user-test"
    And event at index 1 is of type "WorkspaceWasRebased" with payload:
      | Key           | Expected         |
      | workspaceName | "leaf-user-test" |
    And I expect exactly 1 events to be published on stream "Workspace:other-user-test"
    Then workspaces live,other-root,user-test,leaf-user-test,other-user-test have status UP_TO_DATE

  Scenario: The workspace maintenance service rebases all descendants if a root workspace has changes making them outdated

    Given the command SetNodeProperties is executed with payload:
      | Key                       | Value               |
      | workspaceName             | "live"         |
      | nodeAggregateId           | "nody-mc-nodeface"  |
      | originDimensionSpacePoint | {}                  |
      | propertyValues            | {"text": "my-text"} |

    When outdated workspaces are rebased
    Then I expect exactly 1 events to be published on stream "Workspace:live"
    And I expect exactly 1 events to be published on stream "Workspace:other-root"
    # rebase has happened here
    And I expect exactly 2 events to be published on stream "Workspace:user-test"
    And event at index 1 is of type "WorkspaceWasRebased" with payload:
      | Key           | Expected    |
      | workspaceName | "user-test" |
    # rebase has happened here
    And I expect exactly 2 events to be published on stream "Workspace:leaf-user-test"
    And event at index 1 is of type "WorkspaceWasRebased" with payload:
      | Key           | Expected         |
      | workspaceName | "leaf-user-test" |
    And I expect exactly 1 events to be published on stream "Workspace:other-user-test"
    Then workspaces live,other-root,user-test,leaf-user-test,other-user-test have status UP_TO_DATE
