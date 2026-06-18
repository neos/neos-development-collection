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

    When I upgrade events duplicated content stream removals
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

    And I upgrade events duplicated content stream removals
    Then I expect the following upgrade output:
      """
      1 content streams were removed more than once:

      -
          stream: 'ContentStream:cs-user-first'
          sequenceNumbers: '11,14'
          correlationIds: 'ChangeBaseWorkspace_f9819ad4fd7ff5defd,ChangeBaseWorkspace_2871e0770793478646'
          removals: 2


      Found 3 events to be removed
          Debug: 9,10,11
      Backup: copying events table to cr_default_events_bkp_2024_09_22_12_00_00

      Migration applied to 3 events. Please replay the projections `./flow subscription:replayall`
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
