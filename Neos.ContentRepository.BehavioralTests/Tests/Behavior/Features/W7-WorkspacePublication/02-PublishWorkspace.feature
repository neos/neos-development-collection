@contentrepository @adapters=DoctrineDBAL
Feature: Workspace based content publishing

  This is an END TO END test; testing all layers of the related functionality step by step together

  Basic fixture setup is:
  - root workspace with a single "root" node inside; and an additional child node.
  - then, a nested workspace is created based on the "root" node

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Content':
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
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | nodeTypeName                           | parentNodeAggregateId  | nodeName |
      | nody-mc-nodeface | Neos.ContentRepository.Testing:Content | lady-eleonode-rootford | child    |

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                |
      | nodeAggregateId           | "nody-mc-nodeface"   |
      | originDimensionSpacePoint | {}                   |
      | propertyValues            | {"text": "Original"} |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |

  Scenario: Basic events are emitted
    # LIVE workspace
    Then I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"
    And event at index 0 is of type "ContentStreamWasCreated" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-identifier" |

    # Event 1 is the root Node Created event (we can skip this here, it is tested somewhere else); Event 2 is the SetProperty
    Then I expect exactly 1 event to be published on stream "Workspace:live"
    And event at index 0 is of type "RootWorkspaceWasCreated" with payload:
      | Key                  | Expected                 |
      | workspaceName        | "live"                   |
      | newContentStreamId   | "cs-identifier"          |

    # USER workspace
    Then I expect exactly 1 event to be published on stream "ContentStream:user-cs-identifier"
    And event at index 0 is of type "ContentStreamWasForked" with payload:
      | Key                   | Expected             |
      | newContentStreamId    | "user-cs-identifier" |
      | sourceContentStreamId | "cs-identifier"      |

    Then I expect exactly 1 event to be published on stream "Workspace:user-test"
    And event at index 0 is of type "WorkspaceWasCreated" with payload:
      | Key                  | Expected                      |
      | workspaceName        | "user-test"                   |
      | baseWorkspaceName    | "live"                        |
      | newContentStreamId   | "user-cs-identifier"          |

  Scenario: modify the property in the nested workspace and publish afterwards works
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                |
      | workspaceName             | "user-test"          |
      | nodeAggregateId           | "nody-mc-nodeface"   |
      | originDimensionSpacePoint | {}                   |
      | propertyValues            | {"text": "Modified"} |

    When I am in workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value      |
      | text | "Original" |

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value      |
      | text | "Modified" |

    # PUBLISHING
    When the command PublishWorkspace is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |
    Then I expect the content stream "user-cs-identifier" to not exist

    When I am in workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value      |
      | text | "Modified" |

  Scenario: modify the property in the nested workspace, do modification in live workspace; publish afterwards will not work because rebase is missing; then rebase and publish

    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                  |
      | workspaceName             | "user-test"                            |
      | nodeAggregateId           | "nody-mc-nodeface"                     |
      | originDimensionSpacePoint | {}                                     |
      | propertyValues            | {"text": "Modified in user workspace"} |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                                  |
      | workspaceName             | "live"                                 |
      | nodeAggregateId           | "nody-mc-nodeface"                     |
      | originDimensionSpacePoint | {}                                     |
      | propertyValues            | {"text": "Modified in live workspace"} |

    # PUBLISHING without rebase: error
    When the command PublishWorkspace is executed with payload and exceptions are caught:
      | Key           | Value       |
      | workspaceName | "user-test" |

    Then the last command should have thrown an exception of type "BaseWorkspaceHasBeenModifiedInTheMeantime"

    Then I expect exactly 4 event to be published on stream "ContentStream:user-cs-identifier"
    And event at index 3 is of type "ContentStreamWasReopened" with payload:
      | Key                  | Expected                 |
      | contentStreamId      | "user-cs-identifier"     |

    # REBASING + Publishing: works now (TODO soft constraint check for old value)
    When the command RebaseWorkspace is executed with payload:
      | Key                    | Value           |
      | workspaceName          | "user-test"     |
      | rebasedContentStreamId | "rebased-cs-id" |

    And the command PublishWorkspace is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |

    When I am in workspace "live" and dimension space point {}

    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value                        |
      | text | "Modified in user workspace" |

  Scenario: modify the property in the nested workspace, publish, modify again and publish again (e.g. a workspace can be re-used after publishing for other changes)

    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                |
      | workspaceName             | "user-test"          |
      | nodeAggregateId           | "nody-mc-nodeface"   |
      | originDimensionSpacePoint | {}                   |
      | propertyValues            | {"text": "Modified"} |


    # PUBLISHING
    And the command PublishWorkspace is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |
    When I am in workspace "live" and dimension space point {}

    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                     |
      | workspaceName             | "live"                    |
      | nodeAggregateId           | "nody-mc-nodeface"        |
      | originDimensionSpacePoint | {}                        |
      | propertyValues            | {"text": "Modified anew"} |


    # PUBLISHING
    And the command PublishWorkspace is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |

    When I am in workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value           |
      | text | "Modified anew" |

  Scenario: Publish is a no-op if there are no changes
    And I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier;nody-mc-nodeface;{}

    And the command PublishWorkspace is executed with payload:
      | Key                | Value         |
      | workspaceName      | "user-test"   |
      | newContentStreamId | "user-cs-new" |

    # the user and live workspace are unchanged
    Then I expect exactly 1 event to be published on stream "Workspace:user-test"
    Then I expect exactly 3 event to be published on stream "ContentStream:user-cs-identifier"
    And event at index 2 is of type "ContentStreamWasReopened" with payload:
      | Key                  | Expected                 |
      | contentStreamId      | "user-cs-identifier"     |

    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier;nody-mc-nodeface;{}

    # checks for the live workspace (same as above)
    Then I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"
    Then I expect exactly 1 event to be published on stream "Workspace:live"
