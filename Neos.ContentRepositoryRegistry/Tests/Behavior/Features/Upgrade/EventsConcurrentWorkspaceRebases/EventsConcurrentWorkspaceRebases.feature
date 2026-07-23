Feature: As a user of the CR I want to upgrade my events

  Background:
    And the current date and time is "2026-04-30T09:00:00+00:00"
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

    And the command CreateWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "review"          |
      | baseWorkspaceName  | "live"          |
      | newContentStreamId | "cs-review-first" |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "user"          |
      | baseWorkspaceName  | "review"          |
      | newContentStreamId | "cs-user-first" |

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | workspaceName             | "live"                                |
      | originDimensionSpacePoint | {"language": "de"}                    |
      | nodeAggregateId           | "nody-mc-nodeface"                    |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | workspaceName             | "review"                                |
      | originDimensionSpacePoint | {"language": "de"}                    |
      | nodeAggregateId           | "sir-david-nodenborough"              |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |

    And the command PublishWorkspace is executed with payload:
      | Key                | Value              |
      | workspaceName      | "review"           |
      | newContentStreamId | "cs-review-second" |

    And the command RebaseWorkspace is executed with payload:
      | Key                    | Value            |
      | workspaceName          | "user"           |
      | rebasedContentStreamId | "cs-user-second" |

    When I upgrade the events to concurrent workspace-rebases
    Then I expect the following upgrade output:
      """
      Migration was not necessary. No forks on already removed content streams.
      """

    # replay works
    When I replay the contentGraph projection
    Then I expect the following workspaces to exist:
      | name     | base workspace | status       | content stream     | publishable changes |
      | "live"   | null           | "UP_TO_DATE" | "cs-identifier"    | false               |
      | "review" | "live"         | "UP_TO_DATE" | "cs-review-second" | false               |
      | "user"   | "review"       | "UP_TO_DATE" | "cs-user-second"   | false               |

  Scenario: Workspace rebase during publishing
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | workspaceName   | "live"                        |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "review"          |
      | baseWorkspaceName  | "live"          |
      | newContentStreamId | "cs-review-first" |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "user"          |
      | baseWorkspaceName  | "review"          |
      | newContentStreamId | "cs-user-first" |

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | workspaceName             | "live"                                |
      | originDimensionSpacePoint | {"language": "de"}                    |
      | nodeAggregateId           | "nody-mc-nodeface"                    |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | workspaceName             | "review"                                |
      | originDimensionSpacePoint | {"language": "de"}                    |
      | nodeAggregateId           | "sir-david-nodenborough"              |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |

    And the command PublishWorkspace is executed with payload:
      | Key                | Value              |
      | workspaceName      | "review"           |
      | newContentStreamId | "cs-review-second" |

    Given I have the following additional raw events to upgrade:
      | sequencenumber | stream                       | version | type                   | payload                                                                                                            | metadata                                                                         | id                                   | correlationid       | recordedat          |
      # illegal rebase workspace on already removed content stream (should be cs-review-second)
      | 100            | ContentStream:cs-user-first  | 4       | ContentStreamWasClosed | {"contentStreamId":"cs-user-first"}                                                                                | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 19d9edb6-bb47-464e-ace9-64e3d80dbe92 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      | 101            | ContentStream:cs-user-second | 0       | ContentStreamWasForked | {"newContentStreamId":"cs-user-second","sourceContentStreamId":"cs-review-first","versionOfSourceContentStream":1} | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 0e27354f-2bd8-47fe-bfe3-2ab6e7ace856 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      | 102            | Workspace:user               | 2       | WorkspaceWasRebased    | {"workspaceName":"user","previousContentStreamId": "cs-user-first", "newContentStreamId":"cs-user-second"}         | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 01df0edb-6412-4144-92a3-013ef5ec1af4 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |

    When I upgrade the events to concurrent workspace-rebases
    Then I expect the following upgrade output:
      """
      Migration was not necessary. No forks on already removed content streams.
      """

    # todo assert which events are remaining
    # Then I expect exactly 2 events to be published on stream with prefix "ContentStream:cs-user-first"

    # replay works
    When I replay the contentGraph projection
    Then I expect the following workspaces to exist:
      | name     | base workspace | status       | content stream     | publishable changes |
      | "live"   | null           | "UP_TO_DATE" | "cs-identifier"    | false               |
      | "review" | "live"         | "UP_TO_DATE" | "cs-review-second" | false               |
      | "user"   | "review"       | "UP_TO_DATE" | "cs-user-second"   | false               |

  Scenario: Duplicate workspace rebase during publishing only second attempt correct
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | workspaceName   | "live"                        |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "review"          |
      | baseWorkspaceName  | "live"          |
      | newContentStreamId | "cs-review-first" |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "user"          |
      | baseWorkspaceName  | "review"          |
      | newContentStreamId | "cs-user-first" |

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | workspaceName             | "live"                                |
      | originDimensionSpacePoint | {"language": "de"}                    |
      | nodeAggregateId           | "nody-mc-nodeface"                    |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | workspaceName             | "review"                                |
      | originDimensionSpacePoint | {"language": "de"}                    |
      | nodeAggregateId           | "sir-david-nodenborough"              |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |

    And the command PublishWorkspace is executed with payload:
      | Key                | Value              |
      | workspaceName      | "review"           |
      | newContentStreamId | "cs-review-second" |

    Given I have the following additional raw events to upgrade:
      | sequencenumber | stream                       | version | type                   | payload                                                                                                            | metadata                                                                         | id                                   | correlationid       | recordedat          |
      # illegal rebase workspace on already removed content stream (should be cs-review-second)
      | 100            | ContentStream:cs-user-first  | 4       | ContentStreamWasClosed | {"contentStreamId":"cs-user-first"}                                                                                | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 19d9edb6-bb47-464e-ace9-64e3d80dbe92 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      | 101            | ContentStream:cs-user-second | 0       | ContentStreamWasForked | {"newContentStreamId":"cs-user-second","sourceContentStreamId":"cs-review-first","versionOfSourceContentStream":1} | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 0e27354f-2bd8-47fe-bfe3-2ab6e7ace856 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      | 102            | Workspace:user               | 2       | WorkspaceWasRebased    | {"workspaceName":"user","previousContentStreamId": "cs-user-first", "newContentStreamId":"cs-user-second"}         | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 01df0edb-6412-4144-92a3-013ef5ec1af4 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |

      # second correct rebase, but it refers to illegal forked content stream "cs-user-second"
      | 103            | ContentStream:cs-user-second | 1       | ContentStreamWasClosed | {"contentStreamId":"cs-user-second"}                                                                               | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | 2f89545a-7464-47a6-97cf-767fe403987a | RebaseWorkspace_456 | 2026-04-30 11:00:00 |
      | 104            | ContentStream:cs-user-third  | 0       | ContentStreamWasForked | {"newContentStreamId":"cs-user-third","sourceContentStreamId":"cs-review-second","versionOfSourceContentStream":0} | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | 6b6ba121-a7c0-4983-89b6-d26801a9ee89 | RebaseWorkspace_456 | 2026-04-30 11:00:00 |
      | 105            | Workspace:user               | 3       | WorkspaceWasRebased    | {"workspaceName":"user","previousContentStreamId": "cs-user-first", "newContentStreamId":"cs-user-second"}         | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | 727e396a-feec-4fe3-be95-7a1e5ef1fb26 | RebaseWorkspace_456 | 2026-04-30 11:00:00 |

    When I upgrade the events to concurrent workspace-rebases
    Then I expect the following upgrade output:
      """
      Migration was not necessary. No forks on already removed content streams.
      """

    # todo assert which events are remaining
    # Then I expect exactly 2 events to be published on stream with prefix "ContentStream:cs-user-first"

    # replay works
    When I replay the contentGraph projection
    Then I expect the following workspaces to exist:
      | name     | base workspace | status       | content stream     | publishable changes |
      | "live"   | null           | "UP_TO_DATE" | "cs-identifier"    | false               |
      | "review" | "live"         | "UP_TO_DATE" | "cs-review-second" | false               |
      | "user"   | "review"       | "UP_TO_DATE" | "cs-user-third"    | false               |
