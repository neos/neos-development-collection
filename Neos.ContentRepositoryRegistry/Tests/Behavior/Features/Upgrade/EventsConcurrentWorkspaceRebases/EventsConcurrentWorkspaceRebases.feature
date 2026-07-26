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

  Scenario: Constraint Invalid workspace rebase sequence because of wrong correlation ids
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
      | Key                | Value             |
      | workspaceName      | "review"          |
      | baseWorkspaceName  | "live"            |
      | newContentStreamId | "cs-review-first" |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "user"          |
      | baseWorkspaceName  | "review"        |
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
      | workspaceName             | "review"                              |
      | originDimensionSpacePoint | {"language": "de"}                    |
      | nodeAggregateId           | "sir-david-nodenborough"              |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"              |

    And the command PublishWorkspace is executed with payload:
      | Key                | Value              |
      | workspaceName      | "review"           |
      | newContentStreamId | "cs-review-second" |

    Given I have the following additional raw events to upgrade:
      | sequencenumber | stream                       | version | type                    | payload                                                                                                            | metadata                                                                         | id                                   | correlationid       | recordedat          |
      # illegal rebase workspace on already removed content stream (should be cs-review-second)
      | 100            | ContentStream:cs-user-first  | 4       | ContentStreamWasClosed  | {"contentStreamId":"cs-user-first"}                                                                                | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 19d9edb6-bb47-464e-ace9-64e3d80dbe92 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      | 101            | ContentStream:cs-user-second | 0       | ContentStreamWasForked  | {"newContentStreamId":"cs-user-second","sourceContentStreamId":"cs-review-first","versionOfSourceContentStream":1} | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 0e27354f-2bd8-47fe-bfe3-2ab6e7ace856 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      | 102            | Workspace:user               | 2       | WorkspaceWasRebased     | {"workspaceName":"user","previousContentStreamId": "cs-user-first", "newContentStreamId":"cs-user-second"}         | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 01df0edb-6412-4144-92a3-013ef5ec1af4 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      # wrong correlation id, must be _123
      | 103            | ContentStream:cs-user-first  | 5       | ContentStreamWasRemoved | {"contentStreamId": "cs-user-first"}                                                                               | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | a49edee3-6022-4834-b9d2-59bde62be391 | RebaseWorkspace_456 | 2026-04-30 10:00:00 |

    When I upgrade the events to concurrent workspace-rebases
    Then I expect the following upgrade output:
      """
      Error: Invalid end of rebase workspace sequence RebaseWorkspace_123 expected ContentStreamWasRemoved
          Debug: [{"event":{"id":{"value":"19d9edb6-bb47-464e-ace9-64e3d80dbe92"},"type":{"value":"ContentStreamWasClosed"},"data":{"value":"{\"contentStreamId\":\"cs-user-first\"}"},"metadata":{"value":{"initiatingUserId":"user","initiatingTimestamp":"2026-04-30T10:00:00+00:00"}},"causationId":null,"correlationId":{"value":"RebaseWorkspace_123"}},"streamName":{"value":"ContentStream:cs-user-first"},"version":{"value":4},"sequenceNumber":{"value":100},"recordedAt":{"date":"2026-04-30 10:00:00.000000","timezone_type":3,"timezone":"UTC"}},{"event":{"id":{"value":"0e27354f-2bd8-47fe-bfe3-2ab6e7ace856"},"type":{"value":"ContentStreamWasForked"},"data":{"value":"{\"newContentStreamId\":\"cs-user-second\",\"sourceContentStreamId\":\"cs-review-first\",\"versionOfSourceContentStream\":1}"},"metadata":{"value":{"initiatingUserId":"user","initiatingTimestamp":"2026-04-30T10:00:00+00:00"}},"causationId":null,"correlationId":{"value":"RebaseWorkspace_123"}},"streamName":{"value":"ContentStream:cs-user-second"},"version":{"value":0},"sequenceNumber":{"value":101},"recordedAt":{"date":"2026-04-30 10:00:00.000000","timezone_type":3,"timezone":"UTC"}},{"event":{"id":{"value":"01df0edb-6412-4144-92a3-013ef5ec1af4"},"type":{"value":"WorkspaceWasRebased"},"data":{"value":"{\"workspaceName\":\"user\",\"previousContentStreamId\": \"cs-user-first\", \"newContentStreamId\":\"cs-user-second\"}"},"metadata":{"value":{"initiatingUserId":"user","initiatingTimestamp":"2026-04-30T10:00:00+00:00"}},"causationId":null,"correlationId":{"value":"RebaseWorkspace_123"}},"streamName":{"value":"Workspace:user"},"version":{"value":2},"sequenceNumber":{"value":102},"recordedAt":{"date":"2026-04-30 10:00:00.000000","timezone_type":3,"timezone":"UTC"}}]
      """

  Scenario: Constraint Invalid workspace rebase sequence because new content stream event
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
      | sequencenumber | stream                       | version | type                                | payload                                                                                                                                                                                                                                | metadata                                                                         | id                                   | correlationid                           | recordedat          |
      # illegal rebase workspace on already removed content stream (should be cs-review-second)
      | 100            | ContentStream:cs-user-first  | 4       | ContentStreamWasClosed              | {"contentStreamId":"cs-user-first"}                                                                                                                                                                                                    | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 19d9edb6-bb47-464e-ace9-64e3d80dbe92 | RebaseWorkspace_123                     | 2026-04-30 10:00:00 |
      | 101            | ContentStream:cs-user-second | 0       | ContentStreamWasForked              | {"newContentStreamId":"cs-user-second","sourceContentStreamId":"cs-review-first","versionOfSourceContentStream":1}                                                                                                                     | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 0e27354f-2bd8-47fe-bfe3-2ab6e7ace856 | RebaseWorkspace_123                     | 2026-04-30 10:00:00 |
      | 102            | Workspace:user               | 2       | WorkspaceWasRebased                 | {"workspaceName":"user","previousContentStreamId": "cs-user-first", "newContentStreamId":"cs-user-second"}                                                                                                                             | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 01df0edb-6412-4144-92a3-013ef5ec1af4 | RebaseWorkspace_123                     | 2026-04-30 10:00:00 |
      | 103            | ContentStream:cs-user-first  | 5       | ContentStreamWasRemoved             | {"contentStreamId": "cs-user-first"}                                                                                                                                                                                                   | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | a49edee3-6022-4834-b9d2-59bde62be391 | RebaseWorkspace_123                     | 2026-04-30 10:00:00 |
      | 104            | ContentStream:cs-user-second | 1       | RootNodeAggregateWithNodeWasCreated | {"workspaceName":"user","contentStreamId":"cs-user-second","nodeAggregateId":"illegal-node","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[{"language":"en"},{"language":"de"}],"nodeAggregateClassification":"root"} | []                                                                               | 2c8b9d29-c3fd-44e0-b275-15d336dc38ab | CreateNodeAggregateW_e00b8ac2a08c7fbb9b | 2026-04-30 11:00:00 |

    When I upgrade the events to concurrent workspace-rebases
    Then I expect the following upgrade output:
      """
      Error: Expected no events or another RebaseWorkspace sequence. Got at 104 type RootNodeAggregateWithNodeWasCreated with CreateNodeAggregateW_e00b8ac2a08c7fbb9b
          Debug: [{"event":{"id":{"value":"2c8b9d29-c3fd-44e0-b275-15d336dc38ab"},"type":{"value":"RootNodeAggregateWithNodeWasCreated"},"data":{"value":"{\"workspaceName\":\"user\",\"contentStreamId\":\"cs-user-second\",\"nodeAggregateId\":\"illegal-node\",\"nodeTypeName\":\"Neos.Neos:Sites\",\"coveredDimensionSpacePoints\":[{\"language\":\"en\"},{\"language\":\"de\"}],\"nodeAggregateClassification\":\"root\"}"},"metadata":{"value":[]},"causationId":null,"correlationId":{"value":"CreateNodeAggregateW_e00b8ac2a08c7fbb9b"}},"streamName":{"value":"ContentStream:cs-user-second"},"version":{"value":1},"sequenceNumber":{"value":104},"recordedAt":{"date":"2026-04-30 11:00:00.000000","timezone_type":3,"timezone":"UTC"}}]
      """

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
      | sequencenumber | stream                       | version | type                    | payload                                                                                                            | metadata                                                                         | id                                   | correlationid       | recordedat          |
      # illegal rebase workspace on already removed content stream (should be cs-review-second)
      | 100            | ContentStream:cs-user-first  | 4       | ContentStreamWasClosed  | {"contentStreamId":"cs-user-first"}                                                                                | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 19d9edb6-bb47-464e-ace9-64e3d80dbe92 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      | 101            | ContentStream:cs-user-second | 0       | ContentStreamWasForked  | {"newContentStreamId":"cs-user-second","sourceContentStreamId":"cs-review-first","versionOfSourceContentStream":1} | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 0e27354f-2bd8-47fe-bfe3-2ab6e7ace856 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      | 102            | Workspace:user               | 2       | WorkspaceWasRebased     | {"workspaceName":"user","previousContentStreamId": "cs-user-first", "newContentStreamId":"cs-user-second"}         | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 01df0edb-6412-4144-92a3-013ef5ec1af4 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      | 103            | ContentStream:cs-user-first  | 5       | ContentStreamWasRemoved | {"contentStreamId": "cs-user-first"}                                                                               | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | a49edee3-6022-4834-b9d2-59bde62be391 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |

    When I upgrade the events to concurrent workspace-rebases
    Then I expect the following upgrade output:
      """
      Found 4 events to be removed
          Debug: 100,101,102,103
      Backup: copying events table to cr_default_events_bkp_2026_04_30_09_00_00

      Migration applied to 4 events. Please replay the content graph via `./flow crupgrade:resetupandreplaycontentgraph`
      Done.
      """

    Then I expect exactly 1 events to be published on stream with prefix "ContentStream:cs-user-first"
    And event at index 0 is of type "ContentStreamWasForked" with payload:
      | Key                   | Expected          |
      | newContentStreamId    | "cs-user-first"   |
      | sourceContentStreamId | "cs-review-first" |
    Then I expect exactly 0 events to be published on stream with prefix "ContentStream:cs-user-second"
    Then I expect exactly 1 events to be published on stream with prefix "Workspace:user"
    And event at index 0 is of type "WorkspaceWasCreated" with payload:
      | Key                | Expected        |
      | workspaceName      | "user"          |
      | baseWorkspaceName  | "review"        |
      | newContentStreamId | "cs-user-first" |

    # replay works
    When I replay the contentGraph projection
    Then I expect the following workspaces to exist:
      | name     | base workspace | status       | content stream     | publishable changes |
      | "live"   | null           | "UP_TO_DATE" | "cs-identifier"    | false               |
      | "review" | "live"         | "UP_TO_DATE" | "cs-review-second" | false               |
      | "user"   | "review"       | "OUTDATED"   | "cs-user-first"    | false               |

  Scenario: Duplicate workspace rebase during publishing but second attempt is not correct
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
      | sequencenumber | stream                       | version | type                    | payload                                                                                                            | metadata                                                                         | id                                   | correlationid       | recordedat          |
      # illegal rebase workspace on already removed content stream (should be cs-review-second)
      | 100            | ContentStream:cs-user-first  | 4       | ContentStreamWasClosed  | {"contentStreamId":"cs-user-first"}                                                                                | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 19d9edb6-bb47-464e-ace9-64e3d80dbe92 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      | 101            | ContentStream:cs-user-second | 0       | ContentStreamWasForked  | {"newContentStreamId":"cs-user-second","sourceContentStreamId":"cs-review-first","versionOfSourceContentStream":1} | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 0e27354f-2bd8-47fe-bfe3-2ab6e7ace856 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      | 102            | Workspace:user               | 2       | WorkspaceWasRebased     | {"workspaceName":"user","previousContentStreamId": "cs-user-first", "newContentStreamId":"cs-user-second"}         | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 01df0edb-6412-4144-92a3-013ef5ec1af4 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      | 103            | ContentStream:cs-user-first  | 5       | ContentStreamWasRemoved | {"contentStreamId": "cs-user-first"}                                                                               | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | a49edee3-6022-4834-b9d2-59bde62be391 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |

      # second correct rebase on already removed content stream (should be cs-review-second)
      | 104            | ContentStream:cs-user-second | 1       | ContentStreamWasClosed  | {"contentStreamId":"cs-user-second"}                                                                               | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | 2f89545a-7464-47a6-97cf-767fe403987a | RebaseWorkspace_456 | 2026-04-30 11:00:00 |
      | 105            | ContentStream:cs-user-third  | 0       | ContentStreamWasForked  | {"newContentStreamId":"cs-user-third","sourceContentStreamId":"cs-review-first","versionOfSourceContentStream":1}  | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | 6b6ba121-a7c0-4983-89b6-d26801a9ee89 | RebaseWorkspace_456 | 2026-04-30 11:00:00 |
      | 106            | Workspace:user               | 3       | WorkspaceWasRebased     | {"workspaceName":"user","previousContentStreamId": "cs-user-second", "newContentStreamId":"cs-user-third"}         | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | 727e396a-feec-4fe3-be95-7a1e5ef1fb26 | RebaseWorkspace_456 | 2026-04-30 11:00:00 |
      | 107            | ContentStream:cs-user-second | 2       | ContentStreamWasRemoved | {"contentStreamId": "cs-user-second"}                                                                              | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | cbd8c63f-259f-45c4-b8a4-ee480b0fd0d2 | RebaseWorkspace_456 | 2026-04-30 11:00:00 |

    When I upgrade the events to concurrent workspace-rebases
    Then I expect the following upgrade output:
      """
      Found 8 events to be removed
          Debug: 100,101,102,103,104,105,106,107
      Backup: copying events table to cr_default_events_bkp_2026_04_30_09_00_00

      Migration applied to 8 events. Please replay the content graph via `./flow crupgrade:resetupandreplaycontentgraph`
      Done.
      """

    Then I expect exactly 1 events to be published on stream with prefix "ContentStream:cs-user-first"
    And event at index 0 is of type "ContentStreamWasForked" with payload:
      | Key                   | Expected          |
      | newContentStreamId    | "cs-user-first"   |
      | sourceContentStreamId | "cs-review-first" |
    Then I expect exactly 0 events to be published on stream with prefix "ContentStream:cs-user-second"
    Then I expect exactly 0 events to be published on stream with prefix "ContentStream:cs-user-third"
    Then I expect exactly 1 events to be published on stream with prefix "Workspace:user"
    And event at index 0 is of type "WorkspaceWasCreated" with payload:
      | Key                | Expected        |
      | workspaceName      | "user"          |
      | baseWorkspaceName  | "review"        |
      | newContentStreamId | "cs-user-first" |

    # replay works
    When I replay the contentGraph projection
    Then I expect the following workspaces to exist:
      | name     | base workspace | status       | content stream     | publishable changes |
      | "live"   | null           | "UP_TO_DATE" | "cs-identifier"    | false               |
      | "review" | "live"         | "UP_TO_DATE" | "cs-review-second" | false               |
      | "user"   | "review"       | "OUTDATED"   | "cs-user-first"    | false               |

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
      | sequencenumber | stream                       | version | type                    | payload                                                                                                            | metadata                                                                         | id                                   | correlationid       | recordedat          |
      # illegal rebase workspace on already removed content stream (should be cs-review-second)
      | 100            | ContentStream:cs-user-first  | 4       | ContentStreamWasClosed  | {"contentStreamId":"cs-user-first"}                                                                                | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 19d9edb6-bb47-464e-ace9-64e3d80dbe92 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      | 101            | ContentStream:cs-user-second | 0       | ContentStreamWasForked  | {"newContentStreamId":"cs-user-second","sourceContentStreamId":"cs-review-first","versionOfSourceContentStream":1} | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 0e27354f-2bd8-47fe-bfe3-2ab6e7ace856 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      | 102            | Workspace:user               | 2       | WorkspaceWasRebased     | {"workspaceName":"user","previousContentStreamId": "cs-user-first", "newContentStreamId":"cs-user-second"}         | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 01df0edb-6412-4144-92a3-013ef5ec1af4 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      | 103            | ContentStream:cs-user-first  | 5       | ContentStreamWasRemoved | {"contentStreamId": "cs-user-first"}                                                                               | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | a49edee3-6022-4834-b9d2-59bde62be391 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |

      # second correct rebase, but it refers to illegal forked content stream "cs-user-second"
      | 104            | ContentStream:cs-user-second | 1       | ContentStreamWasClosed  | {"contentStreamId":"cs-user-second"}                                                                               | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | 2f89545a-7464-47a6-97cf-767fe403987a | RebaseWorkspace_456 | 2026-04-30 11:00:00 |
      | 105            | ContentStream:cs-user-third  | 0       | ContentStreamWasForked  | {"newContentStreamId":"cs-user-third","sourceContentStreamId":"cs-review-second","versionOfSourceContentStream":0} | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | 6b6ba121-a7c0-4983-89b6-d26801a9ee89 | RebaseWorkspace_456 | 2026-04-30 11:00:00 |
      | 106            | Workspace:user               | 3       | WorkspaceWasRebased     | {"workspaceName":"user","previousContentStreamId": "cs-user-second", "newContentStreamId":"cs-user-third"}         | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | 727e396a-feec-4fe3-be95-7a1e5ef1fb26 | RebaseWorkspace_456 | 2026-04-30 11:00:00 |
      | 107            | ContentStream:cs-user-second | 2       | ContentStreamWasRemoved | {"contentStreamId": "cs-user-second"}                                                                              | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | cbd8c63f-259f-45c4-b8a4-ee480b0fd0d2 | RebaseWorkspace_456 | 2026-04-30 11:00:00 |

    When I upgrade the events to concurrent workspace-rebases
    Then I expect the following upgrade output:
      """
      Found 4 events to be removed
          Debug: 100,101,102,103
      Found 1 remaining workspace rebases to be adjusted after the deletion
          Debug: RebaseWorkspace_456
      Backup: copying events table to cr_default_events_bkp_2026_04_30_09_00_00

      Migration applied to 7 events. Please replay the content graph via `./flow crupgrade:resetupandreplaycontentgraph`
      Done.
      """

    Then I expect exactly 0 events to be published on stream with prefix "ContentStream:cs-user-second"

    Then I expect exactly 3 events to be published on stream with prefix "ContentStream:cs-user-first"
    And event at index 0 is of type "ContentStreamWasForked" with payload:
      | Key                   | Expected          |
      | newContentStreamId    | "cs-user-first"   |
      | sourceContentStreamId | "cs-review-first" |
    And event at index 1 is of type "ContentStreamWasClosed" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-user-first" |
    And event at index 2 is of type "ContentStreamWasRemoved" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-user-first" |
    Then I expect exactly 1 events to be published on stream with prefix "ContentStream:cs-user-third"
    And event at index 0 is of type "ContentStreamWasForked" with payload:
      | Key                   | Expected           |
      | newContentStreamId    | "cs-user-third"    |
      | sourceContentStreamId | "cs-review-second" |
    Then I expect exactly 2 events to be published on stream with prefix "Workspace:user"
    And event at index 0 is of type "WorkspaceWasCreated" with payload:
      | Key                | Expected        |
      | workspaceName      | "user"          |
      | baseWorkspaceName  | "review"        |
      | newContentStreamId | "cs-user-first" |
    And event at index 1 is of type "WorkspaceWasRebased" with payload:
      | Key                | Expected        |
      | workspaceName      | "user"          |
      | newContentStreamId | "cs-user-third" |

    # replay works
    When I replay the contentGraph projection
    Then I expect the following workspaces to exist:
      | name     | base workspace | status       | content stream     | publishable changes |
      | "live"   | null           | "UP_TO_DATE" | "cs-identifier"    | false               |
      | "review" | "live"         | "UP_TO_DATE" | "cs-review-second" | false               |
      | "user"   | "review"       | "UP_TO_DATE" | "cs-user-third"    | false               |

  Scenario: Duplicate workspace rebase during publishing only third attempt correct
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
      | sequencenumber | stream                       | version | type                    | payload                                                                                                            | metadata                                                                         | id                                   | correlationid       | recordedat          |
      # illegal rebase workspace on already removed content stream (should be cs-review-second)
      | 100            | ContentStream:cs-user-first  | 4       | ContentStreamWasClosed  | {"contentStreamId":"cs-user-first"}                                                                                | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 19d9edb6-bb47-464e-ace9-64e3d80dbe92 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      | 101            | ContentStream:cs-user-second | 0       | ContentStreamWasForked  | {"newContentStreamId":"cs-user-second","sourceContentStreamId":"cs-review-first","versionOfSourceContentStream":1} | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 0e27354f-2bd8-47fe-bfe3-2ab6e7ace856 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      | 102            | Workspace:user               | 2       | WorkspaceWasRebased     | {"workspaceName":"user","previousContentStreamId": "cs-user-first", "newContentStreamId":"cs-user-second"}         | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 01df0edb-6412-4144-92a3-013ef5ec1af4 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |
      | 103            | ContentStream:cs-user-first  | 5       | ContentStreamWasRemoved | {"contentStreamId": "cs-user-first"}                                                                               | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | a49edee3-6022-4834-b9d2-59bde62be391 | RebaseWorkspace_123 | 2026-04-30 10:00:00 |

      # second correct rebase on already removed content stream (should be cs-review-second)
      | 200            | ContentStream:cs-user-second | 1       | ContentStreamWasClosed  | {"contentStreamId":"cs-user-second"}                                                                               | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | 2f89545a-7464-47a6-97cf-767fe403987a | RebaseWorkspace_456 | 2026-04-30 11:00:00 |
      | 201            | ContentStream:cs-user-third  | 0       | ContentStreamWasForked  | {"newContentStreamId":"cs-user-third","sourceContentStreamId":"cs-review-first","versionOfSourceContentStream":1}  | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | 6b6ba121-a7c0-4983-89b6-d26801a9ee89 | RebaseWorkspace_456 | 2026-04-30 11:00:00 |
      | 202            | Workspace:user               | 3       | WorkspaceWasRebased     | {"workspaceName":"user","previousContentStreamId": "cs-user-second", "newContentStreamId":"cs-user-third"}         | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | 727e396a-feec-4fe3-be95-7a1e5ef1fb26 | RebaseWorkspace_456 | 2026-04-30 11:00:00 |
      | 203            | ContentStream:cs-user-second | 2       | ContentStreamWasRemoved | {"contentStreamId": "cs-user-second"}                                                                              | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | cbd8c63f-259f-45c4-b8a4-ee480b0fd0d2 | RebaseWorkspace_456 | 2026-04-30 11:00:00 |

      # third correct rebase, but it refers to illegal forked content stream "cs-user-second"
      | 300            | ContentStream:cs-user-third  | 1       | ContentStreamWasClosed  | {"contentStreamId":"cs-user-third"}                                                                                | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | 05135d7c-9619-412e-b16f-305c696e4374 | RebaseWorkspace_789 | 2026-04-30 12:00:00 |
      | 301            | ContentStream:cs-user-forth  | 0       | ContentStreamWasForked  | {"newContentStreamId":"cs-user-forth","sourceContentStreamId":"cs-review-second","versionOfSourceContentStream":0} | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | 13dafeff-2aa1-4d51-84b6-3bdcd954956d | RebaseWorkspace_789 | 2026-04-30 12:00:00 |
      | 302            | Workspace:user               | 4       | WorkspaceWasRebased     | {"workspaceName":"user","previousContentStreamId": "cs-user-third", "newContentStreamId":"cs-user-forth"}          | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | 6663874d-516c-4267-a7d3-9aeba334fdf8 | RebaseWorkspace_789 | 2026-04-30 12:00:00 |
      | 303            | ContentStream:cs-user-third  | 2       | ContentStreamWasRemoved | {"contentStreamId": "cs-user-third"}                                                                               | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | fe992949-efcb-4042-8a1f-1d90aee4f22c | RebaseWorkspace_789 | 2026-04-30 12:00:00 |

    When I upgrade the events to concurrent workspace-rebases
    Then I expect the following upgrade output:
      """
      Found 8 events to be removed
          Debug: 100,101,102,103,200,201,202,203
      Found 1 remaining workspace rebases to be adjusted after the deletion
          Debug: RebaseWorkspace_789
      Backup: copying events table to cr_default_events_bkp_2026_04_30_09_00_00

      Migration applied to 11 events. Please replay the content graph via `./flow crupgrade:resetupandreplaycontentgraph`
      Done.
      """

    Then I expect exactly 0 events to be published on stream with prefix "ContentStream:cs-user-second"
    Then I expect exactly 0 events to be published on stream with prefix "ContentStream:cs-user-third"

    Then I expect exactly 3 events to be published on stream with prefix "ContentStream:cs-user-first"
    And event at index 0 is of type "ContentStreamWasForked" with payload:
      | Key                   | Expected          |
      | newContentStreamId    | "cs-user-first"   |
      | sourceContentStreamId | "cs-review-first" |
    And event at index 1 is of type "ContentStreamWasClosed" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-user-first" |
    And event at index 2 is of type "ContentStreamWasRemoved" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-user-first" |
    Then I expect exactly 1 events to be published on stream with prefix "ContentStream:cs-user-forth"
    And event at index 0 is of type "ContentStreamWasForked" with payload:
      | Key                   | Expected           |
      | newContentStreamId    | "cs-user-forth"    |
      | sourceContentStreamId | "cs-review-second" |
    Then I expect exactly 2 events to be published on stream with prefix "Workspace:user"
    And event at index 0 is of type "WorkspaceWasCreated" with payload:
      | Key                | Expected        |
      | workspaceName      | "user"          |
      | baseWorkspaceName  | "review"        |
      | newContentStreamId | "cs-user-first" |
    And event at index 1 is of type "WorkspaceWasRebased" with payload:
      | Key                | Expected        |
      | workspaceName      | "user"          |
      | newContentStreamId | "cs-user-forth" |

    # replay works
    When I replay the contentGraph projection
    Then I expect the following workspaces to exist:
      | name     | base workspace | status       | content stream     | publishable changes |
      | "live"   | null           | "UP_TO_DATE" | "cs-identifier"    | false               |
      | "review" | "live"         | "UP_TO_DATE" | "cs-review-second" | false               |
      | "user"   | "review"       | "UP_TO_DATE" | "cs-user-forth"    | false               |

  Scenario: Duplicate workspace rebase during publishing only second attempt correct with other allowed content stream events
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
      | sequencenumber | stream                         | version | type                                | payload                                                                                                                                                                                                                                        | metadata                                                                         | id                                   | correlationid                           | recordedat          |
      # illegal rebase workspace on already removed content stream (should be cs-review-second)
      | 100            | ContentStream:cs-user-first    | 4       | ContentStreamWasClosed              | {"contentStreamId":"cs-user-first"}                                                                                                                                                                                                            | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 19d9edb6-bb47-464e-ace9-64e3d80dbe92 | RebaseWorkspace_123                     | 2026-04-30 10:00:00 |
      # some other stream
      | 101            | ContentStream:cs-review-second | 1       | RootNodeAggregateWithNodeWasCreated | {"workspaceName":"review","contentStreamId":"cs-review-second","nodeAggregateId":"unrelated-node-1","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[{"language":"en"},{"language":"de"}],"nodeAggregateClassification":"root"} | []                                                                               | 2c8b9d29-c3fd-44e0-b275-15d336dc38ab | CreateNodeAggregateW_e00b8ac2a08c7fbb9b | 2026-04-30 11:00:00 |
      | 102            | ContentStream:cs-user-second   | 0       | ContentStreamWasForked              | {"newContentStreamId":"cs-user-second","sourceContentStreamId":"cs-review-first","versionOfSourceContentStream":1}                                                                                                                             | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 0e27354f-2bd8-47fe-bfe3-2ab6e7ace856 | RebaseWorkspace_123                     | 2026-04-30 10:00:00 |
      | 103            | Workspace:user                 | 2       | WorkspaceWasRebased                 | {"workspaceName":"user","previousContentStreamId": "cs-user-first", "newContentStreamId":"cs-user-second"}                                                                                                                                     | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | 01df0edb-6412-4144-92a3-013ef5ec1af4 | RebaseWorkspace_123                     | 2026-04-30 10:00:00 |
      | 104            | ContentStream:cs-user-first    | 5       | ContentStreamWasRemoved             | {"contentStreamId": "cs-user-first"}                                                                                                                                                                                                           | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T10:00:00+00:00"} | a49edee3-6022-4834-b9d2-59bde62be391 | RebaseWorkspace_123                     | 2026-04-30 10:00:00 |

      # some other stream
      | 200            | ContentStream:cs-identifier    | 4       | RootNodeAggregateWithNodeWasCreated | {"workspaceName":"live","contentStreamId":"cs-identifier","nodeAggregateId":"unrelated-node-2","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[{"language":"en"},{"language":"de"}],"nodeAggregateClassification":"root"}      | []                                                                               | 57b715a1-3bec-409f-8253-88c9b5e5b7db | CreateNodeAggregateW_57b715a13bec       | 2026-04-30 11:00:00 |

      # second correct rebase, but it refers to illegal forked content stream "cs-user-second"
      | 301            | ContentStream:cs-user-second   | 1       | ContentStreamWasClosed              | {"contentStreamId":"cs-user-second"}                                                                                                                                                                                                           | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | 2f89545a-7464-47a6-97cf-767fe403987a | RebaseWorkspace_456                     | 2026-04-30 11:00:00 |
      | 302            | ContentStream:cs-user-third    | 0       | ContentStreamWasForked              | {"newContentStreamId":"cs-user-third","sourceContentStreamId":"cs-review-second","versionOfSourceContentStream":1}                                                                                                                             | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | 6b6ba121-a7c0-4983-89b6-d26801a9ee89 | RebaseWorkspace_456                     | 2026-04-30 11:00:00 |
      | 303            | Workspace:user                 | 3       | WorkspaceWasRebased                 | {"workspaceName":"user","previousContentStreamId": "cs-user-second", "newContentStreamId":"cs-user-third"}                                                                                                                                     | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | 727e396a-feec-4fe3-be95-7a1e5ef1fb26 | RebaseWorkspace_456                     | 2026-04-30 11:00:00 |
      # some other stream
      | 304            | ContentStream:cs-identifier    | 5       | RootNodeAggregateWithNodeWasCreated | {"workspaceName":"live","contentStreamId":"cs-identifier","nodeAggregateId":"unrelated-node-3","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[{"language":"en"},{"language":"de"}],"nodeAggregateClassification":"root"}      | []                                                                               | a0cd6fef-8bdb-4003-a754-b8a0c507005b | CreateNodeAggregateW_a0cd6fef8bdb       | 2026-04-30 11:00:00 |
      | 305            | ContentStream:cs-user-second   | 2       | ContentStreamWasRemoved             | {"contentStreamId": "cs-user-second"}                                                                                                                                                                                                          | {"initiatingUserId": "user", "initiatingTimestamp": "2026-04-30T11:00:00+00:00"} | cbd8c63f-259f-45c4-b8a4-ee480b0fd0d2 | RebaseWorkspace_456                     | 2026-04-30 11:00:00 |
      | 306            | ContentStream:cs-user-third    | 1       | RootNodeAggregateWithNodeWasCreated | {"workspaceName":"user","contentStreamId":"cs-user-third","nodeAggregateId":"new-node","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[{"language":"en"},{"language":"de"}],"nodeAggregateClassification":"root"}              | []                                                                               | c152253a-0a35-4b4f-a689-2a3440b6f01e | CreateNodeAggregateW_c152253a0a35       | 2026-04-30 11:00:00 |

    When I upgrade the events to concurrent workspace-rebases
    Then I expect the following upgrade output:
      """
      Found 4 events to be removed
          Debug: 100,102,103,104
      Found 1 remaining workspace rebases to be adjusted after the deletion
          Debug: RebaseWorkspace_456
      Backup: copying events table to cr_default_events_bkp_2026_04_30_09_00_00

      Migration applied to 7 events. Please replay the content graph via `./flow crupgrade:resetupandreplaycontentgraph`
      Done.
      """

    Then I expect exactly 0 events to be published on stream with prefix "ContentStream:cs-user-second"

    Then I expect exactly 3 events to be published on stream with prefix "ContentStream:cs-user-first"
    And event at index 0 is of type "ContentStreamWasForked" with payload:
      | Key                   | Expected          |
      | newContentStreamId    | "cs-user-first"   |
      | sourceContentStreamId | "cs-review-first" |
    And event at index 1 is of type "ContentStreamWasClosed" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-user-first" |
    And event at index 2 is of type "ContentStreamWasRemoved" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-user-first" |
    Then I expect exactly 2 events to be published on stream with prefix "ContentStream:cs-user-third"
    And event at index 0 is of type "ContentStreamWasForked" with payload:
      | Key                   | Expected           |
      | newContentStreamId    | "cs-user-third"    |
      | sourceContentStreamId | "cs-review-second" |
    And event at index 1 is of type "RootNodeAggregateWithNodeWasCreated" with payload:
      | Key             | Expected        |
      | contentStreamId | "cs-user-third" |
      | nodeAggregateId | "new-node"      |

    Then I expect exactly 2 events to be published on stream with prefix "Workspace:user"
    And event at index 0 is of type "WorkspaceWasCreated" with payload:
      | Key                | Expected        |
      | workspaceName      | "user"          |
      | baseWorkspaceName  | "review"        |
      | newContentStreamId | "cs-user-first" |
    And event at index 1 is of type "WorkspaceWasRebased" with payload:
      | Key                | Expected        |
      | workspaceName      | "user"          |
      | newContentStreamId | "cs-user-third" |

    # replay works
    When I replay the contentGraph projection
    Then I expect the following workspaces to exist:
      | name     | base workspace | status       | content stream     | publishable changes |
      | "live"   | null           | "UP_TO_DATE" | "cs-identifier"    | false               |
      | "review" | "live"         | "OUTDATED"   | "cs-review-second" | true                |
      | "user"   | "review"       | "UP_TO_DATE" | "cs-user-third"    | true                |
