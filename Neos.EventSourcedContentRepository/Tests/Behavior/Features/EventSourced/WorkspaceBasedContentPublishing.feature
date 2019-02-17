@fixtures
Feature: Workspace based content publishing

  This is an END TO END test; testing all layers of the related functionality step by step together

  Basic fixture setup is:
  - root workspace with a single node "lady-eleonode-nodesworth" inside
  - then, a nested workspace is created based on the the root workspace

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
      | Key                     | Value           | Type                    |
      | workspaceName           | live            | WorkspaceName           |
      | contentStreamIdentifier | [cs-identifier] | ContentStreamIdentifier |

    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                      | Value                                | Type                    |
      | contentStreamIdentifier  | [cs-identifier]                      | ContentStreamIdentifier |
      | nodeAggregateIdentifier  | sir-david-nodenborough               | NodeAggregateIdentifier |
      | nodeTypeName             | Neos.ContentRepository:Root          | NodeTypeName            |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 | UserIdentifier          |

    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                  | Type                    |
      | contentStreamIdentifier       | [cs-identifier]                        | ContentStreamIdentifier |
      | nodeAggregateIdentifier       | lady-eleonode-nodesworth               | NodeAggregateIdentifier |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Content | NodeTypeName            |
      | originDimensionSpacePoint     | {}                                     | DimensionSpacePoint     |
      | visibleInDimensionSpacePoints | [{}]                                   | DimensionSpacePointSet  |
      | parentNodeAggregateIdentifier | sir-david-nodenborough                 | NodeAggregateIdentifier |
      | nodeName                      | foo                                    | NodeName                |
      | initialPropertyValues         | []                                     | PropertyValues          |

    When the command "SetNodeProperty" is executed with payload:
      | Key                       | Value                                | Type                    |
      | contentStreamIdentifier   | [cs-identifier]                      | ContentStreamIdentifier |
      | nodeAggregateIdentifier   | lady-eleonode-nodesworth             | NodeAggregateIdentifier |
      | originDimensionSpacePoint | {}                                   | DimensionSpacePoint     |
      | propertyName              | text                                 | PropertyName            |
      | value                     | {"value":"Original","type":"string"} | PropertyValue           |

    And the command CreateWorkspace is executed with payload:
      | Key                     | Value             | Type                    |
      | workspaceName           | user-test         | WorkspaceName           |
      | baseWorkspaceName       | live              | WorkspaceName           |
      | contentStreamIdentifier | [cs-2-identifier] | ContentStreamIdentifier |
      | workspaceOwner          | [test]            | UserIdentifier          |


  Scenario: Basic events are emitted

    # LIVE workspace
    Then I expect exactly 2 events to be published on stream "Neos.ContentRepository:ContentStream:[cs-identifier]"
    And event at index 0 is of type "Neos.EventSourcedContentRepository:ContentStreamWasCreated" with payload:
      | Key                      | Expected                   | Type                    |
      | contentStreamIdentifier  | [cs-identifier]            | ContentStreamIdentifier |
      | initiatingUserIdentifier | [initiatingUserIdentifier] | UserIdentifier          |

    # Event 1 is the root Node Created event (we can skip this here, it is tested somewhere else); Event 2 is the SetProperty
    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:Workspace:live"
    And event at index 0 is of type "Neos.EventSourcedContentRepository:RootWorkspaceWasCreated" with payload:
      | Key                            | Expected                 | Type                    |
      | workspaceName                  | live                     | WorkspaceName           |
      | workspaceTitle                 | Live                     | WorkspaceTitle          |
      | workspaceDescription           | The workspace "live".    | WorkspaceDescription    |
      | initiatingUserIdentifier       | [initiatingUserIdentifier] | UserIdentifier          |
      | currentContentStreamIdentifier | [cs-identifier]          | ContentStreamIdentifier |

    # USER workspace
    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:ContentStream:[cs-2-identifier]"
    And event at index 0 is of type "Neos.EventSourcedContentRepository:ContentStreamWasForked" with payload:
      | Key                           | Expected          | Type                    |
      | contentStreamIdentifier       | [cs-2-identifier] | ContentStreamIdentifier |
      | sourceContentStreamIdentifier | [cs-identifier]   | ContentStreamIdentifier |

    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:Workspace:user-test"
    And event at index 0 is of type "Neos.EventSourcedContentRepository:WorkspaceWasCreated" with payload:
      | Key                            | Expected                   | Type                    |
      | workspaceName                  | user-test                  | WorkspaceName           |
      | baseWorkspaceName              | live                       | WorkspaceName           |
      | workspaceTitle                 | User-test                  | WorkspaceTitle          |
      | workspaceDescription           | The workspace "user-test". | WorkspaceDescription    |
      | initiatingUserIdentifier       | [initiatingUserIdentifier]   | UserIdentifier          |
      | currentContentStreamIdentifier | [cs-2-identifier]          | ContentStreamIdentifier |
      | workspaceOwner                 | [test]             | UserIdentifier          |

  Scenario: modify the property in the nested workspace and publish afterwards works

    When the command "SetNodeProperty" is executed with payload:
      | Key                       | Value                                | Type                    |
      | contentStreamIdentifier   | [cs-2-identifier]                    | ContentStreamIdentifier |
      | nodeAggregateIdentifier   | lady-eleonode-nodesworth             | NodeAggregateIdentifier |
      | originDimensionSpacePoint | {}                                   | DimensionSpacePoint     |
      | propertyName              | text                                 | PropertyName            |
      | value                     | {"value":"Modified","type":"string"} | PropertyValue           |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node with identifier {"nodeAggregateIdentifier": "lady-eleonode-nodesworth", "contentStreamIdentifier": "[cs-identifier]", "originDimensionSpacePoint": "{}"} to exist in the content graph
    And I expect this node to have the properties:
      | Key  | Value    |
      | text | Original |

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect a node with identifier {"nodeAggregateIdentifier": "lady-eleonode-nodesworth", "contentStreamIdentifier": "[cs-2-identifier]", "originDimensionSpacePoint": "{}"} to exist in the content graph
    And I expect this node to have the properties:
      | Key  | Value    |
      | text | Modified |

    # PUBLISHING
    When the command "PublishWorkspace" is executed with payload:
      | Key           | Value     | Type          |
      | workspaceName | user-test | WorkspaceName |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node with identifier {"nodeAggregateIdentifier": "lady-eleonode-nodesworth", "contentStreamIdentifier": "[cs-identifier]", "originDimensionSpacePoint": "{}"} to exist in the content graph
    And I expect this node to have the properties:
      | Key  | Value    |
      | text | Modified |

  Scenario: Modify the property in the nested workspace, do modification in live workspace; publish afterwards will not work because rebase is missing; then rebase and publish

    When the command "SetNodeProperty" is executed with payload:
      | Key                       | Value                                                  | Type                    |
      | contentStreamIdentifier   | [cs-2-identifier]                                      | ContentStreamIdentifier |
      | nodeAggregateIdentifier   | lady-eleonode-nodesworth                               | NodeAggregateIdentifier |
      | originDimensionSpacePoint | {}                                                     | DimensionSpacePoint     |
      | propertyName              | text                                                   | PropertyName            |
      | value                     | {"value":"Modified in user workspace","type":"string"} | PropertyValue           |

    When the command "SetNodeProperty" is executed with payload:
      | Key                       | Value                                                  | Type                    |
      | contentStreamIdentifier   | [cs-identifier]                                        | ContentStreamIdentifier |
      | nodeAggregateIdentifier   | lady-eleonode-nodesworth                               | NodeAggregateIdentifier |
      | originDimensionSpacePoint | {}                                                     | DimensionSpacePoint     |
      | propertyName              | text                                                   | PropertyName            |
      | value                     | {"value":"Modified in live workspace","type":"string"} | PropertyValue           |

    # PUBLISHING without rebase: error
    When the command "PublishWorkspace" is executed with payload and exceptions are caught:
      | Key           | Value     | Type          |
      | workspaceName | user-test | WorkspaceName |

    Then the last command should have thrown an exception of type "BaseWorkspaceHasBeenModifiedInTheMeantime"

    # REBASING + Publishing: works now (TODO soft constraint check for old value)
    When the command "RebaseWorkspace" is executed with payload:
      | Key           | Value     | Type          |
      | workspaceName | user-test | WorkspaceName |

    And the command "PublishWorkspace" is executed with payload:
      | Key           | Value     | Type          |
      | workspaceName | user-test | WorkspaceName |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node with identifier {"nodeAggregateIdentifier": "lady-eleonode-nodesworth", "contentStreamIdentifier": "[cs-2-identifier]", "originDimensionSpacePoint": "{}"} to exist in the content graph
    And I expect this node to have the properties:
      | Key  | Value                      |
      | text | Modified in user workspace |




