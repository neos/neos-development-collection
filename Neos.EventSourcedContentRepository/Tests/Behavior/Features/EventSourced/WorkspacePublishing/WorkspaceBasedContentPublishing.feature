@fixtures
Feature: Workspace based content publishing

  This is an END TO END test; testing all layers of the related functionality step by step together

  Basic fixture setup is:
  - root workspace with a single "root" node inside; and an additional child node.
  - then, a nested workspace is created based on the "root" node

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value           |
      | workspaceName              | "live"          |
      | newContentStreamIdentifier | "cs-identifier" |
    And the graph projection is fully up to date
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                         |
      | contentStreamIdentifier     | "cs-identifier"               |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
      | coveredDimensionSpacePoints | [{}]                          |
      | initiatingUserIdentifier    | "initiating-user-identifier"  |
      | nodeAggregateClassification | "root"                        |

    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "cs-identifier"                          |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | coveredDimensionSpacePoints   | [{}]                                     |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                 |
      | nodeName                      | "child"                                  |
      | nodeAggregateClassification   | "regular"                                |

    And the graph projection is fully up to date

    And the command "SetNodeProperties" is executed with payload:
      | Key                       | Value                                             |
      | contentStreamIdentifier   | "cs-identifier"                                   |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                                |
      | originDimensionSpacePoint | {}                                                |
      | propertyValues            | {"text": {"type": "string", "value": "Original"}} |
      | initiatingUserIdentifier  | "initiating-user-identifier"                      |
    And the command CreateWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "user-test"          |
      | baseWorkspaceName          | "live"               |
      | newContentStreamIdentifier | "user-cs-identifier" |
    And the graph projection is fully up to date

  Scenario: Basic events are emitted
    # LIVE workspace
    Then I expect exactly 4 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 0 is of type "Neos.EventSourcedContentRepository:ContentStreamWasCreated" with payload:
      | Key                      | Expected                     |
      | contentStreamIdentifier  | "cs-identifier"              |
      | initiatingUserIdentifier | "initiating-user-identifier" |

    # Event 1 is the root Node Created event (we can skip this here, it is tested somewhere else); Event 2 is the SetProperty
    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:Workspace:live"
    And event at index 0 is of type "Neos.EventSourcedContentRepository:RootWorkspaceWasCreated" with payload:
      | Key                        | Expected                     |
      | workspaceName              | "live"                       |
      | workspaceTitle             | "Live"                       |
      | workspaceDescription       | "The workspace \"live\""     |
      | initiatingUserIdentifier   | "initiating-user-identifier" |
      | newContentStreamIdentifier | "cs-identifier"              |

    # USER workspace
    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:ContentStream:user-cs-identifier"
    And event at index 0 is of type "Neos.EventSourcedContentRepository:ContentStreamWasForked" with payload:
      | Key                           | Expected                     |
      | contentStreamIdentifier       | "user-cs-identifier"         |
      | sourceContentStreamIdentifier | "cs-identifier"              |
      | initiatingUserIdentifier      | "initiating-user-identifier" |

    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:Workspace:user-test"
    And event at index 0 is of type "Neos.EventSourcedContentRepository:WorkspaceWasCreated" with payload:
      | Key                        | Expected                      |
      | workspaceName              | "user-test"                   |
      | baseWorkspaceName          | "live"                        |
      | workspaceTitle             | "User-test"                   |
      | workspaceDescription       | "The workspace \"user-test\"" |
      | initiatingUserIdentifier   | "initiating-user-identifier"  |
      | newContentStreamIdentifier | "user-cs-identifier"          |
      | workspaceOwner             | "owner-identifier"            |

  Scenario: modify the property in the nested workspace and publish afterwards works
    When the command "SetNodeProperties" is executed with payload:
      | Key                       | Value                                             |
      | contentStreamIdentifier   | "user-cs-identifier"                              |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                                |
      | originDimensionSpacePoint | {}                                                |
      | propertyValues            | {"text": {"type": "string", "value": "Modified"}} |
      | initiatingUserIdentifier  | "initiating-user-identifier"                      |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value    |
      | text | Original |

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value    |
      | text | Modified |

    # PUBLISHING
    When the command "PublishWorkspace" is executed with payload:
      | Key                      | Value                        |
      | workspaceName            | "user-test"                  |
      | initiatingUserIdentifier | "initiating-user-identifier" |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value    |
      | text | Modified |

  Scenario: modify the property in the nested workspace, do modification in live workspace; publish afterwards will not work because rebase is missing; then rebase and publish

    When the command "SetNodeProperties" is executed with payload:
      | Key                       | Value                                                               |
      | contentStreamIdentifier   | "user-cs-identifier"                                                |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                                                  |
      | originDimensionSpacePoint | {}                                                                  |
      | propertyValues            | {"text": {"type": "string", "value": "Modified in user workspace"}} |
      | initiatingUserIdentifier  | "initiating-user-identifier"                                        |
    And the graph projection is fully up to date
    And the command "SetNodeProperties" is executed with payload:
      | Key                       | Value                                                               |
      | contentStreamIdentifier   | "cs-identifier"                                                     |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                                                  |
      | originDimensionSpacePoint | {}                                                                  |
      | propertyValues            | {"text": {"type": "string", "value": "Modified in live workspace"}} |
      | initiatingUserIdentifier  | "initiating-user-identifier"                                        |
    And the graph projection is fully up to date

    # PUBLISHING without rebase: error
    When the command "PublishWorkspace" is executed with payload and exceptions are caught:
      | Key                      | Value                        |
      | workspaceName            | "user-test"                  |
      | initiatingUserIdentifier | "initiating-user-identifier" |

    Then the last command should have thrown an exception of type "BaseWorkspaceHasBeenModifiedInTheMeantime"

    # REBASING + Publishing: works now (TODO soft constraint check for old value)
    When the command "RebaseWorkspace" is executed with payload:
      | Key                      | Value                        |
      | workspaceName            | "user-test"                  |
      | initiatingUserIdentifier | "initiating-user-identifier" |
    And the graph projection is fully up to date

    And the command "PublishWorkspace" is executed with payload:
      | Key                      | Value                        |
      | workspaceName            | "user-test"                  |
      | initiatingUserIdentifier | "initiating-user-identifier" |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value                      |
      | text | Modified in user workspace |

  Scenario: modify the property in the nested workspace, publish, modify again and publish again (e.g. a workspace can be re-used after publishing for other changes)

    When the command "SetNodeProperties" is executed with payload:
      | Key                       | Value                                             |
      | contentStreamIdentifier   | "user-cs-identifier"                              |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                                |
      | originDimensionSpacePoint | {}                                                |
      | propertyValues            | {"text": {"type": "string", "value": "Modified"}} |
      | initiatingUserIdentifier  | "initiating-user-identifier"                      |

    And the graph projection is fully up to date

    # PUBLISHING
    And the command "PublishWorkspace" is executed with payload:
      | Key                      | Value                        |
      | workspaceName            | "user-test"                  |
      | initiatingUserIdentifier | "initiating-user-identifier" |
    And the graph projection is fully up to date
    When I am in the active content stream of workspace "live" and Dimension Space Point {}

    When the command "SetNodeProperties" is executed with payload:
      | Key                       | Value                                                  |
      | contentStreamIdentifier   | $this->contentStreamIdentifier                         |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                                     |
      | originDimensionSpacePoint | {}                                                     |
      | propertyValues            | {"text": {"type": "string", "value": "Modified anew"}} |
      | initiatingUserIdentifier  | "initiating-user-identifier"                           |

    And the graph projection is fully up to date

    # PUBLISHING
    And the command "PublishWorkspace" is executed with payload:
      | Key                      | Value                        |
      | workspaceName            | "user-test"                  |
      | initiatingUserIdentifier | "initiating-user-identifier" |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value         |
      | text | Modified anew |

  Scenario: Discarding a full workspace works
    When the command "SetNodeProperties" is executed with payload:
      | Key                       | Value                                             |
      | contentStreamIdentifier   | "user-cs-identifier"                              |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                                |
      | originDimensionSpacePoint | {}                                                |
      | propertyValues            | {"text": {"type": "string", "value": "Modified"}} |
      | initiatingUserIdentifier  | "initiating-user-identifier"                      |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value    |
      | text | Modified |

    # Discarding
    When the command DiscardWorkspace is executed with payload:
      | Key                      | Value                        |
      | workspaceName            | "user-test"                  |
      | initiatingUserIdentifier | "initiating-user-identifier" |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value    |
      | text | Original |

  Scenario: Discarding a full workspace shows the most up-to-date base workspace when the base WS was modified in the meantime
    When the command "SetNodeProperties" is executed with payload:
      | Key                       | Value                                             |
      | contentStreamIdentifier   | "user-cs-identifier"                              |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                                |
      | originDimensionSpacePoint | {}                                                |
      | propertyValues            | {"text": {"type": "string", "value": "Modified"}} |
      | initiatingUserIdentifier  | "initiating-user-identifier"                      |
    And the graph projection is fully up to date

    And the command "SetNodeProperties" is executed with payload:
      | Key                       | Value                                                               |
      | contentStreamIdentifier   | "cs-identifier"                                                     |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                                                  |
      | originDimensionSpacePoint | {}                                                                  |
      | propertyValues            | {"text": {"type": "string", "value": "Modified in live workspace"}} |
      | initiatingUserIdentifier  | "initiating-user-identifier"                                        |
    And the graph projection is fully up to date

    # Discarding
    When the command DiscardWorkspace is executed with payload:
      | Key                      | Value                        |
      | workspaceName            | "user-test"                  |
      | initiatingUserIdentifier | "initiating-user-identifier" |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value                      |
      | text | Modified in live workspace |
