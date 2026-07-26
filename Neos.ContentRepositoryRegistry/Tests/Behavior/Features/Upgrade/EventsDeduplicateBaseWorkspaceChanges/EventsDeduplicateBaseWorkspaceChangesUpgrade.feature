Feature: As a user of the CR I want to upgrade my events

  Background:
    And the current date and time is "2024-09-22T12:00:00+00:00"
    Given using the following content dimensions:
      | Identifier | Values | Generalizations |
      | language   | de, en | de, en          |
    And using the following node types:
    """yaml
    Neos.ContentRepository.Testing:Node: []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"

  Scenario: Noop for new setup
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | workspaceName   | "live"                        |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | workspaceName             | "live"                                |
      | originDimensionSpacePoint | {"language": "de"}                    |
      | nodeAggregateId           | "nody-mc-nodeface"                    |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value       |
      | workspaceName      | "review"    |
      | baseWorkspaceName  | "live"      |
      | newContentStreamId | "cs-review" |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "user"          |
      | baseWorkspaceName  | "live"          |
      | newContentStreamId | "cs-user-first" |

    And the command ChangeBaseWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user"           |
      | baseWorkspaceName  | "review"         |
      | newContentStreamId | "cs-user-second" |

    When I upgrade the events to deduplicate base-workspace-changes
    Then I expect the following upgrade output:
      """
      Migration was not necessary. No duplicate content stream removals.
      """

    # replay works
    When I replay the contentGraph projection
    Then I expect the following workspaces to exist:
      | name     | base workspace | status       | content stream   | publishable changes |
      | "live"   | null           | "UP_TO_DATE" | "cs-identifier"  | false               |
      | "review" | "live"         | "UP_TO_DATE" | "cs-review"      | false               |
      | "user"   | "review"       | "UP_TO_DATE" | "cs-user-second" | false               |

  Scenario: Basic duplicate base workspace change
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | workspaceName   | "live"                        |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | workspaceName             | "live"                                |
      | originDimensionSpacePoint | {"language": "de"}                    |
      | nodeAggregateId           | "nody-mc-nodeface"                    |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value       |
      | workspaceName      | "review"    |
      | baseWorkspaceName  | "live"      |
      | newContentStreamId | "cs-review" |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "user"          |
      | baseWorkspaceName  | "live"          |
      | newContentStreamId | "cs-user-first" |

    Given I have the following additional raw events to upgrade:
      | sequencenumber | stream                       | version | type                             | payload                                                                                                      | metadata                                                                                                                                                                              | id                                   | correlationid                          | recordedat          |
      | 9              | ContentStream:cs-user-second | 0       | ContentStreamWasForked           | {"newContentStreamId":"cs-user-second","sourceContentStreamId":"cs-review","versionOfSourceContentStream":0} | {"debug_reason": "Change base workspace of jasmin-lehmann to review", "initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:30+02:00"} | 0e27354f-2bd8-47fe-bfe3-2ab6e7ace856 | ChangeBaseWorkspace_f9819ad4fd7ff5defd | 2026-04-30 09:59:30 |
      | 10             | Workspace:user               | 1       | WorkspaceBaseWorkspaceWasChanged | {"workspaceName":"user","baseWorkspaceName":"review","newContentStreamId":"cs-user-second"}                  | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:30+02:00"}                                                                      | 01df0edb-6412-4144-92a3-013ef5ec1af4 | ChangeBaseWorkspace_f9819ad4fd7ff5defd | 2026-04-30 09:59:30 |
      | 11             | ContentStream:cs-user-first  | 1       | ContentStreamWasRemoved          | {"contentStreamId":"cs-user-first"}                                                                          | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:30+02:00"}                                                                      | a81386a2-4a81-42c0-87cc-68795a7e0e62 | ChangeBaseWorkspace_f9819ad4fd7ff5defd | 2026-04-30 09:59:30 |
      | 12             | ContentStream:cs-user-third  | 0       | ContentStreamWasForked           | {"newContentStreamId":"cs-user-third","sourceContentStreamId":"cs-review","versionOfSourceContentStream":0}  | {"debug_reason": "Change base workspace of user to review", "initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:48+02:00"}           | c5edb512-6226-4bdb-8052-8f01e20d6ab9 | ChangeBaseWorkspace_2871e0770793478646 | 2026-04-30 09:59:48 |
      | 13             | Workspace:user               | 2       | WorkspaceBaseWorkspaceWasChanged | {"workspaceName":"user","baseWorkspaceName":"review","newContentStreamId":"cs-user-third"}                   | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:52+02:00"}                                                                      | 1eb6730e-da97-4858-bd3e-3ce785103c54 | ChangeBaseWorkspace_2871e0770793478646 | 2026-04-30 09:59:52 |
      # illegal second removal
      | 14             | ContentStream:cs-user-first  | 2       | ContentStreamWasRemoved          | {"contentStreamId":"cs-user-first"}                                                                          | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:52+02:00"}                                                                      | 794bd543-4cb1-4dac-aa62-de5b280d88d9 | ChangeBaseWorkspace_2871e0770793478646 | 2026-04-30 09:59:52 |

    # Two removals
    Then I expect exactly 3 events to be published on stream with prefix "ContentStream:cs-user-first"
    And event at index 2 is of type "ContentStreamWasRemoved" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-user-first" |
    And event at index 1 is of type "ContentStreamWasRemoved" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-user-first" |

    And I upgrade the events to deduplicate base-workspace-changes
    Then I expect the following upgrade output:
      """
      1 content streams were removed more than once:

      -
          stream: ContentStream:cs-user-first
          sequenceNumbers: 11, 14
          correlationIds: ChangeBaseWorkspace_f9819ad4fd7ff5defd, ChangeBaseWorkspace_2871e0770793478646
          removals: 2

      Found 3 events to be removed
          Debug: 9,10,11
      Backup: copying events table to cr_default_events_bkp_2024_09_22_12_00_00

      Migration applied to 3 events. Please replay the content graph via `./flow crupgrade:resetupandreplaycontentgraph`
      Done.
      """

    Then I expect exactly 2 events to be published on stream with prefix "ContentStream:cs-user-first"
    And event at index 1 is of type "ContentStreamWasRemoved" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-user-first" |

    # replay works
    When I replay the contentGraph projection
    Then I expect the following workspaces to exist:
      | name     | base workspace | status       | content stream  | publishable changes |
      | "live"   | null           | "UP_TO_DATE" | "cs-identifier" | false               |
      | "review" | "live"         | "UP_TO_DATE" | "cs-review"     | false               |
      | "user"   | "review"       | "UP_TO_DATE" | "cs-user-third" | false               |

  Scenario: Multiple duplicate base workspace change
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | workspaceName   | "live"                        |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | workspaceName             | "live"                                |
      | originDimensionSpacePoint | {"language": "de"}                    |
      | nodeAggregateId           | "nody-mc-nodeface"                    |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value         |
      | workspaceName      | "review-1"    |
      | baseWorkspaceName  | "live"        |
      | newContentStreamId | "cs-review-1" |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value         |
      | workspaceName      | "review-2"    |
      | baseWorkspaceName  | "live"        |
      | newContentStreamId | "cs-review-2" |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value         |
      | workspaceName      | "review-3"    |
      | baseWorkspaceName  | "live"        |
      | newContentStreamId | "cs-review-3" |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "user"          |
      | baseWorkspaceName  | "live"          |
      | newContentStreamId | "cs-user-first" |

    Given I have the following additional raw events to upgrade:
      | sequencenumber | stream                       | version | type                             | payload                                                                                                        | metadata                                                                                                                                                                              | id                                   | correlationid                          | recordedat          |
      | 100            | ContentStream:cs-user-second | 0       | ContentStreamWasForked           | {"newContentStreamId":"cs-user-second","sourceContentStreamId":"cs-review-1","versionOfSourceContentStream":0} | {"debug_reason": "Change base workspace of jasmin-lehmann to review", "initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:30+02:00"} | 0e27354f-2bd8-47fe-bfe3-2ab6e7ace856 | ChangeBaseWorkspace_f9819ad4fd7ff5defd | 2026-04-30 09:59:30 |
      | 101            | Workspace:user               | 1       | WorkspaceBaseWorkspaceWasChanged | {"workspaceName":"user","baseWorkspaceName":"review-1","newContentStreamId":"cs-user-second"}                  | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:30+02:00"}                                                                      | 01df0edb-6412-4144-92a3-013ef5ec1af4 | ChangeBaseWorkspace_f9819ad4fd7ff5defd | 2026-04-30 09:59:30 |
      | 102            | ContentStream:cs-user-first  | 1       | ContentStreamWasRemoved          | {"contentStreamId":"cs-user-first"}                                                                            | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:30+02:00"}                                                                      | a81386a2-4a81-42c0-87cc-68795a7e0e62 | ChangeBaseWorkspace_f9819ad4fd7ff5defd | 2026-04-30 09:59:30 |
      | 103            | ContentStream:cs-user-third  | 0       | ContentStreamWasForked           | {"newContentStreamId":"cs-user-third","sourceContentStreamId":"cs-review-2","versionOfSourceContentStream":0}  | {"debug_reason": "Change base workspace of user to review", "initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:48+02:00"}           | c5edb512-6226-4bdb-8052-8f01e20d6ab9 | ChangeBaseWorkspace_2871e0770793478646 | 2026-04-30 09:59:48 |
      | 104            | Workspace:user               | 2       | WorkspaceBaseWorkspaceWasChanged | {"workspaceName":"user","baseWorkspaceName":"review-2","newContentStreamId":"cs-user-third"}                   | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:52+02:00"}                                                                      | 1eb6730e-da97-4858-bd3e-3ce785103c54 | ChangeBaseWorkspace_2871e0770793478646 | 2026-04-30 09:59:52 |
      # illegal second removal
      | 105            | ContentStream:cs-user-first  | 2       | ContentStreamWasRemoved          | {"contentStreamId":"cs-user-first"}                                                                            | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:52+02:00"}                                                                      | 794bd543-4cb1-4dac-aa62-de5b280d88d9 | ChangeBaseWorkspace_2871e0770793478646 | 2026-04-30 09:59:52 |
      | 106            | ContentStream:cs-user-forth  | 0       | ContentStreamWasForked           | {"newContentStreamId":"cs-user-forth","sourceContentStreamId":"cs-review-3","versionOfSourceContentStream":0}  | {"debug_reason": "Change base workspace of user to review", "initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:48+02:00"}           | 3514b413-fb47-48c5-8a3b-f6a83a044842 | ChangeBaseWorkspace_a0e998384894634a74 | 2026-03-30 09:59:48 |
      | 107            | Workspace:user               | 3       | WorkspaceBaseWorkspaceWasChanged | {"workspaceName":"user","baseWorkspaceName":"review-3","newContentStreamId":"cs-user-forth"}                   | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:52+02:00"}                                                                      | e363e915-e6e7-4119-973b-2e7dec4aedc7 | ChangeBaseWorkspace_a0e998384894634a74 | 2026-03-30 09:59:52 |
      # illegal third removal
      | 108            | ContentStream:cs-user-first  | 3       | ContentStreamWasRemoved          | {"contentStreamId":"cs-user-first"}                                                                            | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:52+02:00"}                                                                      | e7141c34-dded-4ee5-9852-1f2cbd12175c | ChangeBaseWorkspace_a0e998384894634a74 | 2026-03-30 09:59:52 |

    # Three removals
    Then I expect exactly 4 events to be published on stream with prefix "ContentStream:cs-user-first"
    And event at index 3 is of type "ContentStreamWasRemoved" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-user-first" |
    And event at index 2 is of type "ContentStreamWasRemoved" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-user-first" |
    And event at index 1 is of type "ContentStreamWasRemoved" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-user-first" |

    And I upgrade the events to deduplicate base-workspace-changes
    Then I expect the following upgrade output:
      """
      1 content streams were removed more than once:

      -
          stream: ContentStream:cs-user-first
          sequenceNumbers: 102, 105, 108
          correlationIds: ChangeBaseWorkspace_f9819ad4fd7ff5defd, ChangeBaseWorkspace_2871e0770793478646, ChangeBaseWorkspace_a0e998384894634a74
          removals: 3

      Found 6 events to be removed
          Debug: 100,101,102,103,104,105
      Backup: copying events table to cr_default_events_bkp_2024_09_22_12_00_00

      Migration applied to 6 events. Please replay the content graph via `./flow crupgrade:resetupandreplaycontentgraph`
      Done.
      """

    Then I expect exactly 2 events to be published on stream with prefix "ContentStream:cs-user-first"
    And event at index 1 is of type "ContentStreamWasRemoved" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-user-first" |

    Then I expect exactly 0 events to be published on stream with prefix "ContentStream:cs-user-second"
    Then I expect exactly 0 events to be published on stream with prefix "ContentStream:cs-user-third"
    Then I expect exactly 1 events to be published on stream with prefix "ContentStream:cs-user-forth"
    And event at index 0 is of type "ContentStreamWasForked" with payload:
      | Key                   | Expected        |
      | newContentStreamId    | "cs-user-forth" |
      | sourceContentStreamId | "cs-review-3"   |

    Then I expect exactly 2 events to be published on stream with prefix "Workspace:user"
    And event at index 1 is of type "WorkspaceBaseWorkspaceWasChanged" with payload:
      | Key                | Expected        |
      | workspaceName      | "user"          |
      | baseWorkspaceName  | "review-3"      |
      | newContentStreamId | "cs-user-forth" |

    # replay works
    When I replay the contentGraph projection
    Then I expect the following workspaces to exist:
      | name       | base workspace | status       | content stream  | publishable changes |
      | "live"     | null           | "UP_TO_DATE" | "cs-identifier" | false               |
      | "review-1" | "live"         | "UP_TO_DATE" | "cs-review-1"   | false               |
      | "review-2" | "live"         | "UP_TO_DATE" | "cs-review-2"   | false               |
      | "review-3" | "live"         | "UP_TO_DATE" | "cs-review-3"   | false               |
      | "user"     | "review-3"     | "UP_TO_DATE" | "cs-user-forth" | false               |

  Scenario: Constraint duplicate base workspace change with change on current workspace stream (ensures we dont delete other events where we dont understand where they did come from)
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | workspaceName   | "live"                        |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | workspaceName             | "live"                                |
      | originDimensionSpacePoint | {"language": "de"}                    |
      | nodeAggregateId           | "nody-mc-nodeface"                    |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value       |
      | workspaceName      | "review"    |
      | baseWorkspaceName  | "live"      |
      | newContentStreamId | "cs-review" |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "user"          |
      | baseWorkspaceName  | "live"          |
      | newContentStreamId | "cs-user-first" |

    Given I have the following additional raw events to upgrade:
      | sequencenumber | stream                       | version | type                                | payload                                                                                                                                                                                                                                | metadata                                                                                                                                                                              | id                                   | correlationid                           | recordedat          |
      | 9              | ContentStream:cs-user-second | 0       | ContentStreamWasForked              | {"newContentStreamId":"cs-user-second","sourceContentStreamId":"cs-review","versionOfSourceContentStream":0}                                                                                                                           | {"debug_reason": "Change base workspace of jasmin-lehmann to review", "initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:30+02:00"} | 0e27354f-2bd8-47fe-bfe3-2ab6e7ace856 | ChangeBaseWorkspace_f9819ad4fd7ff5defd  | 2026-04-30 09:59:30 |
      | 10             | Workspace:user               | 1       | WorkspaceBaseWorkspaceWasChanged    | {"workspaceName":"user","baseWorkspaceName":"review","newContentStreamId":"cs-user-second"}                                                                                                                                            | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:30+02:00"}                                                                      | 01df0edb-6412-4144-92a3-013ef5ec1af4 | ChangeBaseWorkspace_f9819ad4fd7ff5defd  | 2026-04-30 09:59:30 |
      | 11             | ContentStream:cs-user-first  | 1       | ContentStreamWasRemoved             | {"contentStreamId":"cs-user-first"}                                                                                                                                                                                                    | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:30+02:00"}                                                                      | a81386a2-4a81-42c0-87cc-68795a7e0e62 | ChangeBaseWorkspace_f9819ad4fd7ff5defd  | 2026-04-30 09:59:30 |
      | 12             | ContentStream:cs-user-first  | 2       | RootNodeAggregateWithNodeWasCreated | {"workspaceName":"user","contentStreamId":"cs-user-first","nodeAggregateId":"illegal-node","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[{"language":"en"},{"language":"de"}],"nodeAggregateClassification":"root"} | []                                                                                                                                                                                    | 2c8b9d29-c3fd-44e0-b275-15d336dc38ab | CreateNodeAggregateW_e00b8ac2a08c7fbb9b | 2025-06-11 20:36:33 |
      | 13             | ContentStream:cs-user-third  | 0       | ContentStreamWasForked              | {"newContentStreamId":"cs-user-third","sourceContentStreamId":"cs-review","versionOfSourceContentStream":0}                                                                                                                            | {"debug_reason": "Change base workspace of user to review", "initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:48+02:00"}           | c5edb512-6226-4bdb-8052-8f01e20d6ab9 | ChangeBaseWorkspace_2871e0770793478646  | 2026-04-30 09:59:48 |
      | 14             | Workspace:user               | 2       | WorkspaceBaseWorkspaceWasChanged    | {"workspaceName":"user","baseWorkspaceName":"review","newContentStreamId":"cs-user-third"}                                                                                                                                             | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:52+02:00"}                                                                      | 1eb6730e-da97-4858-bd3e-3ce785103c54 | ChangeBaseWorkspace_2871e0770793478646  | 2026-04-30 09:59:52 |
      # illegal second removal
      | 15             | ContentStream:cs-user-first  | 3       | ContentStreamWasRemoved             | {"contentStreamId":"cs-user-first"}                                                                                                                                                                                                    | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:52+02:00"}                                                                      | 794bd543-4cb1-4dac-aa62-de5b280d88d9 | ChangeBaseWorkspace_2871e0770793478646  | 2026-04-30 09:59:52 |

    And I upgrade the events to deduplicate base-workspace-changes
    Then I expect the following upgrade output:
      """
      1 content streams were removed more than once:

      -
          stream: ContentStream:cs-user-first
          sequenceNumbers: 11, 15
          correlationIds: ChangeBaseWorkspace_f9819ad4fd7ff5defd, ChangeBaseWorkspace_2871e0770793478646
          removals: 2

      Stream ContentStream:cs-user-first: Concurrent change during change base workspace sequence affected stream ContentStream:cs-user-first at 12
          Debug: {"event":{"id":{"value":"2c8b9d29-c3fd-44e0-b275-15d336dc38ab"},"type":{"value":"RootNodeAggregateWithNodeWasCreated"},"data":{"value":"{\"workspaceName\":\"user\",\"contentStreamId\":\"cs-user-first\",\"nodeAggregateId\":\"illegal-node\",\"nodeTypeName\":\"Neos.Neos:Sites\",\"coveredDimensionSpacePoints\":[{\"language\":\"en\"},{\"language\":\"de\"}],\"nodeAggregateClassification\":\"root\"}"},"metadata":{"value":[]},"causationId":null,"correlationId":{"value":"CreateNodeAggregateW_e00b8ac2a08c7fbb9b"}},"streamName":{"value":"ContentStream:cs-user-first"},"version":{"value":2},"sequenceNumber":{"value":12},"recordedAt":{"date":"2025-06-11 20:36:33.000000","timezone_type":3,"timezone":"UTC"}}
      """

  Scenario: Constraint duplicate base workspace change with change on temporary content stream (ensures we dont delete other events where we dont understand where they did come from)
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | workspaceName   | "live"                        |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | workspaceName             | "live"                                |
      | originDimensionSpacePoint | {"language": "de"}                    |
      | nodeAggregateId           | "nody-mc-nodeface"                    |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value       |
      | workspaceName      | "review"    |
      | baseWorkspaceName  | "live"      |
      | newContentStreamId | "cs-review" |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "user"          |
      | baseWorkspaceName  | "live"          |
      | newContentStreamId | "cs-user-first" |

    Given I have the following additional raw events to upgrade:
      | sequencenumber | stream                       | version | type                                | payload                                                                                                                                                                                                                                | metadata                                                                                                                                                                              | id                                   | correlationid                           | recordedat          |
      | 9              | ContentStream:cs-user-second | 0       | ContentStreamWasForked              | {"newContentStreamId":"cs-user-second","sourceContentStreamId":"cs-review","versionOfSourceContentStream":0}                                                                                                                           | {"debug_reason": "Change base workspace of jasmin-lehmann to review", "initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:30+02:00"} | 0e27354f-2bd8-47fe-bfe3-2ab6e7ace856 | ChangeBaseWorkspace_f9819ad4fd7ff5defd  | 2026-04-30 09:59:30 |
      | 10             | Workspace:user               | 1       | WorkspaceBaseWorkspaceWasChanged    | {"workspaceName":"user","baseWorkspaceName":"review","newContentStreamId":"cs-user-second"}                                                                                                                                            | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:30+02:00"}                                                                      | 01df0edb-6412-4144-92a3-013ef5ec1af4 | ChangeBaseWorkspace_f9819ad4fd7ff5defd  | 2026-04-30 09:59:30 |
      | 11             | ContentStream:cs-user-first  | 1       | ContentStreamWasRemoved             | {"contentStreamId":"cs-user-first"}                                                                                                                                                                                                    | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:30+02:00"}                                                                      | a81386a2-4a81-42c0-87cc-68795a7e0e62 | ChangeBaseWorkspace_f9819ad4fd7ff5defd  | 2026-04-30 09:59:30 |
      | 12             | ContentStream:cs-user-second | 1       | RootNodeAggregateWithNodeWasCreated | {"workspaceName":"user","contentStreamId":"cs-user-second","nodeAggregateId":"illegal-node","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[{"language":"en"},{"language":"de"}],"nodeAggregateClassification":"root"} | []                                                                                                                                                                                    | 2c8b9d29-c3fd-44e0-b275-15d336dc38ab | CreateNodeAggregateW_e00b8ac2a08c7fbb9b | 2025-06-11 20:36:33 |
      | 13             | ContentStream:cs-user-third  | 0       | ContentStreamWasForked              | {"newContentStreamId":"cs-user-third","sourceContentStreamId":"cs-review","versionOfSourceContentStream":0}                                                                                                                            | {"debug_reason": "Change base workspace of user to review", "initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:48+02:00"}           | c5edb512-6226-4bdb-8052-8f01e20d6ab9 | ChangeBaseWorkspace_2871e0770793478646  | 2026-04-30 09:59:48 |
      | 14             | Workspace:user               | 2       | WorkspaceBaseWorkspaceWasChanged    | {"workspaceName":"user","baseWorkspaceName":"review","newContentStreamId":"cs-user-third"}                                                                                                                                             | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:52+02:00"}                                                                      | 1eb6730e-da97-4858-bd3e-3ce785103c54 | ChangeBaseWorkspace_2871e0770793478646  | 2026-04-30 09:59:52 |
      # illegal second removal
      | 15             | ContentStream:cs-user-first  | 2       | ContentStreamWasRemoved             | {"contentStreamId":"cs-user-first"}                                                                                                                                                                                                    | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:52+02:00"}                                                                      | 794bd543-4cb1-4dac-aa62-de5b280d88d9 | ChangeBaseWorkspace_2871e0770793478646  | 2026-04-30 09:59:52 |

    And I upgrade the events to deduplicate base-workspace-changes
    Then I expect the following upgrade output:
      """
      1 content streams were removed more than once:

      -
          stream: ContentStream:cs-user-first
          sequenceNumbers: 11, 15
          correlationIds: ChangeBaseWorkspace_f9819ad4fd7ff5defd, ChangeBaseWorkspace_2871e0770793478646
          removals: 2

      Stream ContentStream:cs-user-first: Concurrent change during change base workspace sequence affected stream ContentStream:cs-user-second at 12
          Debug: {"event":{"id":{"value":"2c8b9d29-c3fd-44e0-b275-15d336dc38ab"},"type":{"value":"RootNodeAggregateWithNodeWasCreated"},"data":{"value":"{\"workspaceName\":\"user\",\"contentStreamId\":\"cs-user-second\",\"nodeAggregateId\":\"illegal-node\",\"nodeTypeName\":\"Neos.Neos:Sites\",\"coveredDimensionSpacePoints\":[{\"language\":\"en\"},{\"language\":\"de\"}],\"nodeAggregateClassification\":\"root\"}"},"metadata":{"value":[]},"causationId":null,"correlationId":{"value":"CreateNodeAggregateW_e00b8ac2a08c7fbb9b"}},"streamName":{"value":"ContentStream:cs-user-second"},"version":{"value":1},"sequenceNumber":{"value":12},"recordedAt":{"date":"2025-06-11 20:36:33.000000","timezone_type":3,"timezone":"UTC"}}
      """

  Scenario: Allow parallel workspace changes during current base workspace change
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | workspaceName   | "live"                        |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | workspaceName             | "live"                                |
      | originDimensionSpacePoint | {"language": "de"}                    |
      | nodeAggregateId           | "nody-mc-nodeface"                    |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value       |
      | workspaceName      | "review"    |
      | baseWorkspaceName  | "live"      |
      | newContentStreamId | "cs-review" |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "user"          |
      | baseWorkspaceName  | "live"          |
      | newContentStreamId | "cs-user-first" |

    Given I have the following additional raw events to upgrade:
      | sequencenumber | stream                       | version | type                                | payload                                                                                                                                                                                                                          | metadata                                                                                                                                                                              | id                                   | correlationid                           | recordedat          |
      | 9              | ContentStream:cs-user-second | 0       | ContentStreamWasForked              | {"newContentStreamId":"cs-user-second","sourceContentStreamId":"cs-review","versionOfSourceContentStream":0}                                                                                                                     | {"debug_reason": "Change base workspace of jasmin-lehmann to review", "initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:30+02:00"} | 0e27354f-2bd8-47fe-bfe3-2ab6e7ace856 | ChangeBaseWorkspace_f9819ad4fd7ff5defd  | 2026-04-30 09:59:30 |
      | 10             | Workspace:user               | 1       | WorkspaceBaseWorkspaceWasChanged    | {"workspaceName":"user","baseWorkspaceName":"review","newContentStreamId":"cs-user-second"}                                                                                                                                      | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:30+02:00"}                                                                      | 01df0edb-6412-4144-92a3-013ef5ec1af4 | ChangeBaseWorkspace_f9819ad4fd7ff5defd  | 2026-04-30 09:59:30 |
      | 11             | ContentStream:cs-user-first  | 1       | ContentStreamWasRemoved             | {"contentStreamId":"cs-user-first"}                                                                                                                                                                                              | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:30+02:00"}                                                                      | a81386a2-4a81-42c0-87cc-68795a7e0e62 | ChangeBaseWorkspace_f9819ad4fd7ff5defd  | 2026-04-30 09:59:30 |
      | 12             | ContentStream:cs-review      | 1       | RootNodeAggregateWithNodeWasCreated | {"workspaceName":"review","contentStreamId":"cs-review","nodeAggregateId":"some-new-node","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[{"language":"en"},{"language":"de"}],"nodeAggregateClassification":"root"} | []                                                                                                                                                                                    | 2c8b9d29-c3fd-44e0-b275-15d336dc38ab | CreateNodeAggregateW_e00b8ac2a08c7fbb9b | 2025-06-11 20:36:33 |
      | 13             | ContentStream:cs-user-third  | 0       | ContentStreamWasForked              | {"newContentStreamId":"cs-user-third","sourceContentStreamId":"cs-review","versionOfSourceContentStream":1}                                                                                                                      | {"debug_reason": "Change base workspace of user to review", "initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:48+02:00"}           | c5edb512-6226-4bdb-8052-8f01e20d6ab9 | ChangeBaseWorkspace_2871e0770793478646  | 2026-04-30 09:59:48 |
      | 14             | Workspace:user               | 2       | WorkspaceBaseWorkspaceWasChanged    | {"workspaceName":"user","baseWorkspaceName":"review","newContentStreamId":"cs-user-third"}                                                                                                                                       | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:52+02:00"}                                                                      | 1eb6730e-da97-4858-bd3e-3ce785103c54 | ChangeBaseWorkspace_2871e0770793478646  | 2026-04-30 09:59:52 |
      # illegal second removal
      | 15             | ContentStream:cs-user-first  | 2       | ContentStreamWasRemoved             | {"contentStreamId":"cs-user-first"}                                                                                                                                                                                              | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:52+02:00"}                                                                      | 794bd543-4cb1-4dac-aa62-de5b280d88d9 | ChangeBaseWorkspace_2871e0770793478646  | 2026-04-30 09:59:52 |

    And I upgrade the events to deduplicate base-workspace-changes
    Then I expect the following upgrade output:
      """
      1 content streams were removed more than once:

      -
          stream: ContentStream:cs-user-first
          sequenceNumbers: 11, 15
          correlationIds: ChangeBaseWorkspace_f9819ad4fd7ff5defd, ChangeBaseWorkspace_2871e0770793478646
          removals: 2

      Found 3 events to be removed
          Debug: 9,10,11
      Backup: copying events table to cr_default_events_bkp_2024_09_22_12_00_00

      Migration applied to 3 events. Please replay the content graph via `./flow crupgrade:resetupandreplaycontentgraph`
      Done.
      """

    Then I expect exactly 2 events to be published on stream with prefix "ContentStream:cs-user-first"
    And event at index 1 is of type "ContentStreamWasRemoved" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-user-first" |

    # replay works
    When I replay the contentGraph projection
    When I am in workspace "live" and dimension space point {"language": "de"}
    Then I expect the node aggregate "some-new-node" to not exist
    When I am in workspace "review" and dimension space point {"language": "de"}
    Then I expect the node aggregate "some-new-node" to exist
    When I am in workspace "user" and dimension space point {"language": "de"}
    Then I expect the node aggregate "some-new-node" to exist

    Then I expect the following workspaces to exist:
      | name     | base workspace | status       | content stream  | publishable changes |
      | "live"   | null           | "UP_TO_DATE" | "cs-identifier" | false               |
      | "review" | "live"         | "UP_TO_DATE" | "cs-review"     | true                |
      | "user"   | "review"       | "UP_TO_DATE" | "cs-user-third" | false               |

  Scenario: Intersecting duplicate base workspace change (race condition)
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | workspaceName   | "live"                        |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | workspaceName             | "live"                                |
      | originDimensionSpacePoint | {"language": "de"}                    |
      | nodeAggregateId           | "nody-mc-nodeface"                    |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value       |
      | workspaceName      | "review"    |
      | baseWorkspaceName  | "live"      |
      | newContentStreamId | "cs-review" |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "user"          |
      | baseWorkspaceName  | "live"          |
      | newContentStreamId | "cs-user-first" |

    Given I have the following additional raw events to upgrade:
      | sequencenumber | stream                       | version | type                             | payload                                                                                                      | metadata                                                                                                                                                                              | id                                   | correlationid                          | recordedat          |
      # absolute race condition!
      | 9              | ContentStream:cs-user-second | 0       | ContentStreamWasForked           | {"newContentStreamId":"cs-user-second","sourceContentStreamId":"cs-review","versionOfSourceContentStream":0} | {"debug_reason": "Change base workspace of jasmin-lehmann to review", "initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:30+02:00"} | 0e27354f-2bd8-47fe-bfe3-2ab6e7ace856 | ChangeBaseWorkspace_f9819ad4fd7ff5defd | 2026-04-30 09:59:30 |
      | 10             | ContentStream:cs-user-third  | 0       | ContentStreamWasForked           | {"newContentStreamId":"cs-user-third","sourceContentStreamId":"cs-review","versionOfSourceContentStream":0}  | {"debug_reason": "Change base workspace of user to review", "initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:48+02:00"}           | c5edb512-6226-4bdb-8052-8f01e20d6ab9 | ChangeBaseWorkspace_2871e0770793478646 | 2026-04-30 09:59:48 |
      | 11             | Workspace:user               | 1       | WorkspaceBaseWorkspaceWasChanged | {"workspaceName":"user","baseWorkspaceName":"review","newContentStreamId":"cs-user-second"}                  | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:30+02:00"}                                                                      | 01df0edb-6412-4144-92a3-013ef5ec1af4 | ChangeBaseWorkspace_f9819ad4fd7ff5defd | 2026-04-30 09:59:30 |
      | 12             | ContentStream:cs-user-first  | 1       | ContentStreamWasRemoved          | {"contentStreamId":"cs-user-first"}                                                                          | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:30+02:00"}                                                                      | a81386a2-4a81-42c0-87cc-68795a7e0e62 | ChangeBaseWorkspace_f9819ad4fd7ff5defd | 2026-04-30 09:59:30 |
      | 13             | Workspace:user               | 2       | WorkspaceBaseWorkspaceWasChanged | {"workspaceName":"user","baseWorkspaceName":"review","newContentStreamId":"cs-user-third"}                   | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:52+02:00"}                                                                      | 1eb6730e-da97-4858-bd3e-3ce785103c54 | ChangeBaseWorkspace_2871e0770793478646 | 2026-04-30 09:59:52 |
      # illegal second removal
      | 14             | ContentStream:cs-user-first  | 2       | ContentStreamWasRemoved          | {"contentStreamId":"cs-user-first"}                                                                          | {"initiatingUserId": "328b8e87-17d8-4c10-ad5a-91e8c0485077", "initiatingTimestamp": "2026-04-30T09:59:52+02:00"}                                                                      | 794bd543-4cb1-4dac-aa62-de5b280d88d9 | ChangeBaseWorkspace_2871e0770793478646 | 2026-04-30 09:59:52 |

    # Two removals
    Then I expect exactly 3 events to be published on stream with prefix "ContentStream:cs-user-first"
    And event at index 2 is of type "ContentStreamWasRemoved" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-user-first" |
    And event at index 1 is of type "ContentStreamWasRemoved" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-user-first" |

    And I upgrade the events to deduplicate base-workspace-changes
    Then I expect the following upgrade output:
      """
      1 content streams were removed more than once:

      -
          stream: ContentStream:cs-user-first
          sequenceNumbers: 12, 14
          correlationIds: ChangeBaseWorkspace_f9819ad4fd7ff5defd, ChangeBaseWorkspace_2871e0770793478646
          removals: 2

      Found 3 events to be removed
          Debug: 9,11,12
      Backup: copying events table to cr_default_events_bkp_2024_09_22_12_00_00

      Migration applied to 3 events. Please replay the content graph via `./flow crupgrade:resetupandreplaycontentgraph`
      Done.
      """

    Then I expect exactly 2 events to be published on stream with prefix "ContentStream:cs-user-first"
    And event at index 1 is of type "ContentStreamWasRemoved" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-user-first" |

    # replay works
    When I replay the contentGraph projection
    Then I expect the following workspaces to exist:
      | name     | base workspace | status       | content stream  | publishable changes |
      | "live"   | null           | "UP_TO_DATE" | "cs-identifier" | false               |
      | "review" | "live"         | "UP_TO_DATE" | "cs-review"     | false               |
      | "user"   | "review"       | "UP_TO_DATE" | "cs-user-third" | false               |
