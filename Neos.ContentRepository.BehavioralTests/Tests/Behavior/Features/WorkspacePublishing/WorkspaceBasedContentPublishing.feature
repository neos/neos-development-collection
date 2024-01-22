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
      | Key                | Value                        |
      | workspaceName      | "live"                       |
      | newContentStreamId | "cs-identifier"              |
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                         |
      | contentStreamId             | "cs-identifier"               |
      | nodeAggregateId             | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |

    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                    |
      | contentStreamId             | "cs-identifier"                          |
      | nodeAggregateId             | "nody-mc-nodeface"                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint   | {}                                       |
      | coveredDimensionSpacePoints | [{}]                                     |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                 |
      | nodeName                    | "child"                                  |
      | nodeAggregateClassification | "regular"                                |

    And the graph projection is fully up to date

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | contentStreamId           | "cs-identifier"              |
      | nodeAggregateId           | "nody-mc-nodeface"           |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"text": "Original"}         |
    # we need to ensure that the projections are up to date now; otherwise a content stream is forked with an out-
    # of-date base version. This means the content stream can never be merged back, but must always be rebased.
    And the graph projection is fully up to date
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                        |
      | workspaceName      | "user-test"                  |
      | baseWorkspaceName  | "live"                       |
      | newContentStreamId | "user-cs-identifier"         |
      | workspaceOwner     | "owner-identifier"           |
    And the graph projection is fully up to date

  Scenario: Basic events are emitted
    # LIVE workspace
    Then I expect exactly 4 events to be published on stream "ContentStream:cs-identifier"
    And event at index 0 is of type "ContentStreamWasCreated" with payload:
      | Key              | Expected                     |
      | contentStreamId  | "cs-identifier"              |

    # Event 1 is the root Node Created event (we can skip this here, it is tested somewhere else); Event 2 is the SetProperty
    Then I expect exactly 1 event to be published on stream "Workspace:live"
    And event at index 0 is of type "RootWorkspaceWasCreated" with payload:
      | Key                  | Expected                     |
      | workspaceName        | "live"                       |
      | workspaceTitle       | "Live"                       |
      | workspaceDescription | "The workspace \"live\""     |
      | newContentStreamId   | "cs-identifier"              |

    # USER workspace
    Then I expect exactly 1 event to be published on stream "ContentStream:user-cs-identifier"
    And event at index 0 is of type "ContentStreamWasForked" with payload:
      | Key                   | Expected                     |
      | newContentStreamId    | "user-cs-identifier"         |
      | sourceContentStreamId | "cs-identifier"              |

    Then I expect exactly 1 event to be published on stream "Workspace:user-test"
    And event at index 0 is of type "WorkspaceWasCreated" with payload:
      | Key                  | Expected                      |
      | workspaceName        | "user-test"                   |
      | baseWorkspaceName    | "live"                        |
      | workspaceTitle       | "User-test"                   |
      | workspaceDescription | "The workspace \"user-test\"" |
      | newContentStreamId   | "user-cs-identifier"          |
      | workspaceOwner       | "owner-identifier"            |

  Scenario: modify the property in the nested workspace and publish afterwards works
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | contentStreamId           | "user-cs-identifier"         |
      | nodeAggregateId           | "nody-mc-nodeface"           |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"text": "Modified"}         |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value      |
      | text | "Original" |

    When I am in the active content stream of workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value      |
      | text | "Modified" |

    # PUBLISHING
    When the command PublishWorkspace is executed with payload:
      | Key              | Value                        |
      | workspaceName    | "user-test"                  |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value      |
      | text | "Modified" |

  Scenario: modify the property in the nested workspace, do modification in live workspace; publish afterwards will not work because rebase is missing; then rebase and publish

    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                  |
      | contentStreamId           | "user-cs-identifier"                   |
      | nodeAggregateId           | "nody-mc-nodeface"                     |
      | originDimensionSpacePoint | {}                                     |
      | propertyValues            | {"text": "Modified in user workspace"} |
    And the graph projection is fully up to date
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                                  |
      | contentStreamId           | "cs-identifier"                        |
      | nodeAggregateId           | "nody-mc-nodeface"                     |
      | originDimensionSpacePoint | {}                                     |
      | propertyValues            | {"text": "Modified in live workspace"} |
    And the graph projection is fully up to date

    # PUBLISHING without rebase: error
    When the command PublishWorkspace is executed with payload and exceptions are caught:
      | Key              | Value                        |
      | workspaceName    | "user-test"                  |

    Then the last command should have thrown an exception of type "BaseWorkspaceHasBeenModifiedInTheMeantime"

    # REBASING + Publishing: works now (TODO soft constraint check for old value)
    When the command RebaseWorkspace is executed with payload:
      | Key              | Value                        |
      | workspaceName    | "user-test"                  |
    And the graph projection is fully up to date

    And the command PublishWorkspace is executed with payload:
      | Key              | Value                        |
      | workspaceName    | "user-test"                  |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value                        |
      | text | "Modified in user workspace" |

  Scenario: modify the property in the nested workspace, publish, modify again and publish again (e.g. a workspace can be re-used after publishing for other changes)

    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | contentStreamId           | "user-cs-identifier"         |
      | nodeAggregateId           | "nody-mc-nodeface"           |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"text": "Modified"}         |

    And the graph projection is fully up to date

    # PUBLISHING
    And the command PublishWorkspace is executed with payload:
      | Key              | Value                        |
      | workspaceName    | "user-test"                  |
    And the graph projection is fully up to date
    When I am in the active content stream of workspace "live" and dimension space point {}

    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | contentStreamId           | $this->contentStreamId       |
      | nodeAggregateId           | "nody-mc-nodeface"           |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"text": "Modified anew"}    |

    And the graph projection is fully up to date

    # PUBLISHING
    And the command PublishWorkspace is executed with payload:
      | Key              | Value                        |
      | workspaceName    | "user-test"                  |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value           |
      | text | "Modified anew" |

  Scenario: Discarding a full workspace works
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | contentStreamId           | "user-cs-identifier"         |
      | nodeAggregateId           | "nody-mc-nodeface"           |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"text": "Modified"}         |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value      |
      | text | "Modified" |

    # Discarding
    When the command DiscardWorkspace is executed with payload:
      | Key                | Value                         |
      | workspaceName      | "user-test"                   |
      | newContentStreamId | "user-cs-identifier-modified" |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier-modified;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value      |
      | text | "Original" |

  Scenario: Discarding a full workspace shows the most up-to-date base workspace when the base WS was modified in the meantime
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | contentStreamId           | "user-cs-identifier"         |
      | nodeAggregateId           | "nody-mc-nodeface"           |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"text": "Modified"}         |
    And the graph projection is fully up to date

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                                  |
      | contentStreamId           | "cs-identifier"                        |
      | nodeAggregateId           | "nody-mc-nodeface"                     |
      | originDimensionSpacePoint | {}                                     |
      | propertyValues            | {"text": "Modified in live workspace"} |
    And the graph projection is fully up to date

    # Discarding
    When the command DiscardWorkspace is executed with payload:
      | Key                | Value                         |
      | workspaceName      | "user-test"                   |
      | newContentStreamId | "user-cs-identifier-modified" |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier-modified;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value                        |
      | text | "Modified in live workspace" |
