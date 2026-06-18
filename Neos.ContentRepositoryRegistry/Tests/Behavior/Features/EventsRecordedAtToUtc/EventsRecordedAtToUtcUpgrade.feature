Feature: As a user of the CR I want to upgrade my events

  Background:
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
    And the current date and time is "2024-09-22T12:00:00+00:00"
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

    When I am in workspace "live" and dimension space point {"language": "de"}
    Then I expect the node "nody-mc-nodeface" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2024-09-22 12:00:00 | 2024-09-22 12:00:00 |              |                      |

    Then I expect exactly 3 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 2 is of type "NodeAggregateWithNodeWasCreated" with payload:
      | Key             | Expected           |
      | workspaceName   | "live"             |
      | contentStreamId | "cs-identifier"    |
      | nodeAggregateId | "nody-mc-nodeface" |
    And event data at index 2 is:
      | Key                          | Expected                    |
      | recordedAt                   | "2024-09-22T12:00:00+00:00" |
      | metadata.initiatingTimestamp | "2024-09-22T12:00:00+00:00" |

    And I upgrade events recordedAt to utc
    Then I expect the following upgrade output:
      """
      Migration was not necessary. All dates are UTC. Nothing was changed.
      """
    # replay works
    When I replay the contentGraph projection
    # node is unchanged
    Then I expect the node "nody-mc-nodeface" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2024-09-22 12:00:00 | 2024-09-22 12:00:00 |              |                      |

  Scenario: Non utc dates in events

    Given I have the following raw events to upgrade:
      | sequencenumber | stream                                             | version | type                                | payload                                                                                                                                                                                                                                                                              | metadata                                                                           | id                                   | correlationid                          | recordedat          |
      | 1              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 0       | ContentStreamWasCreated             | {"contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f"}                                                                                                                                                                                                                           | {"initiatingUserId": "system", "initiatingTimestamp": "2025-06-11T20:36:31+02:00"} | c546edf2-8c0a-4cf6-9fce-d97fe0671be6 | CreateRootWorkspace_43ddfa553532efb671 | 2025-06-11 20:36:31 |
      | 2              | Workspace:live                                     | 0       | RootWorkspaceWasCreated             | {"workspaceName":"live","newContentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f"}                                                                                                                                                                                                 | {"initiatingUserId": "system", "initiatingTimestamp": "2025-06-11T20:36:31+02:00"} | 0e6097f3-cc22-44f9-a6f8-81c1f31f7116 | CreateRootWorkspace_43ddfa553532efb671 | 2025-06-11 20:36:31 |
      | 3              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 1       | RootNodeAggregateWithNodeWasCreated | {"workspaceName":"live","contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f","nodeAggregateId":"fa0affac-0baa-a530-84d4-58adb5900f93","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[{"language":"en"},{"language":"de"}],"nodeAggregateClassification":"root"} | []                                                                                 | 2c8b9d29-c3fd-44e0-b275-15d336dc38ab | EventStoreImporter_60275c85c589c36a02  | 2025-06-11 20:36:33 |

    And I upgrade events recordedAt to utc
    Then I expect the following upgrade output:
      """
      Migration necessary. Found following non UTC offsets [+02:00]
          Debug: [{"sequenceNumber":1,"tzoffset":"+02:00"}]
      Backup: copying events table to cr_default_events_bkp_2024_09_22_12_00_00

      Migration applied to 3 events. Please replay the projections `./flow subscription:replayall` to see the new adjusted UTC dates in the node timestamps
      Done. Please dont re-rerun the migration.
      """

    Then I expect exactly 2 events to be published on stream with prefix "ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f"
    And event data at index 0 is:
      | Key                          | Expected                    |
      | recordedAt                   | "2025-06-11T18:36:31+00:00" |
      # we dont rewrite the "initiatingTimestamp" as its atom with offset
      | metadata.initiatingTimestamp | "2025-06-11T20:36:31+02:00" |
    And event data at index 1 is:
      | Key                          | Expected                    |
      | recordedAt                   | "2025-06-11T18:36:33+00:00" |
      | metadata.initiatingTimestamp | null                        |

    # replay works
    When I replay the contentGraph projection
    When I am in workspace "live" and dimension space point {"language": "de"}
    Then I expect the node "fa0affac-0baa-a530-84d4-58adb5900f93" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2025-06-11 18:36:33 | 2025-06-11 18:36:33 |              |                      |

    And I upgrade events recordedAt to utc
    Then I expect the following upgrade output:
      """
      Migration necessary. Found following non UTC offsets [+02:00]
          Debug: [{"sequenceNumber":1,"tzoffset":"+02:00"}]
      Warning event 2 already migrated
          Debug: RecordedAt 2025-06-11 18:36:31, Initiating 2025-06-11 18:36:31, Difference 0 (s)
          Debug: {"sequencenumber":2,"recordedat":"2025-06-11 18:36:31","initiatingtimestampatom":"2025-06-11T20:36:31+02:00"}
      Nothing was migrated. If you know what you are doing try again by using a bit more force.
      """
