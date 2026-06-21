Feature: As a user of the CR I want to upgrade my events

  Background:
    Given using the following content dimensions:
      | Identifier | Values | Generalizations |
      | language   | de, en | de, en          |
    And using the following node types:
    """yaml
    Neos.ContentRepository.Testing:Node: []
    """
    And the current date and time is "2024-09-22T12:00:00+00:00"
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

  Scenario: Cannot determine if safe to migrate
    Given I have the following raw events to upgrade:
      | sequencenumber | stream                                             | version | type                                | payload                                                                                                                                                                                                                                                                              | metadata                                                                           | id                                   | correlationid                          | recordedat          |
      | 1              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 0       | ContentStreamWasCreated             | {"contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f"}                                                                                                                                                                                                                           | {"initiatingUserId": "system", "initiatingTimestamp": "2025-06-11T20:36:31+00:00"} | c546edf2-8c0a-4cf6-9fce-d97fe0671be6 | CreateRootWorkspace_43ddfa553532efb671 | 2025-06-11 20:36:31 |
      | 2              | Workspace:live                                     | 0       | RootWorkspaceWasCreated             | {"workspaceName":"live","newContentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f"}                                                                                                                                                                                                 | {"initiatingUserId": "system", "initiatingTimestamp": "2025-06-11T20:36:31+00:00"} | 0e6097f3-cc22-44f9-a6f8-81c1f31f7116 | CreateRootWorkspace_43ddfa553532efb671 | 2025-06-11 20:36:31 |
      # would need migration as +2h
      | 3              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 1       | RootNodeAggregateWithNodeWasCreated | {"workspaceName":"live","contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f","nodeAggregateId":"fa0affac-0baa-a530-84d4-58adb5900f93","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[{"language":"en"},{"language":"de"}],"nodeAggregateClassification":"root"} | {"initiatingUserId": "system", "initiatingTimestamp": "2025-06-11T21:36:31+02:00"} | 2c8b9d29-c3fd-44e0-b275-15d336dc38ab | NodeCreate_XXX                         | 2025-06-11 21:36:31 |

    And I upgrade events recordedAt to utc
    Then I expect the following upgrade output:
      """
      Migration necessary. Found following non UTC offsets [+02:00]
          Debug: [{"sequenceNumber":1,"tzoffset":"+00:00"},{"sequenceNumber":3,"tzoffset":"+02:00"}]
      Could not find a single non publishable event with non UTC date to validate if migration was run before.
      Nothing was migrated. If you know what you are doing try again by using a bit more force.
      """

    # unchanged event though it needs migration
    Then I expect exactly 2 events to be published on stream with prefix "ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f"
    And event data at index 1 is:
      | Key                          | Expected                    |
      | recordedAt                   | "2025-06-11T21:36:31+00:00" |
      | metadata.initiatingTimestamp | "2025-06-11T21:36:31+02:00" |

    And I upgrade events recordedAt to utc with force
    Then I expect the following upgrade output:
      """
      Migration necessary. Found following non UTC offsets [+02:00]
          Debug: [{"sequenceNumber":1,"tzoffset":"+00:00"},{"sequenceNumber":3,"tzoffset":"+02:00"}]
      Could not find a single non publishable event with non UTC date to validate if migration was run before.
      Backup: copying events table to cr_default_events_bkp_2024_09_22_12_00_00

      Migration applied to 1 events. Please replay the projections `./flow subscription:replayall` to see the new adjusted UTC dates in the node timestamps
      Done. Please dont re-rerun the migration.
      """

    Then I expect exactly 2 events to be published on stream with prefix "ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f"
    And event data at index 1 is:
      | Key                          | Expected                    |
      | recordedAt                   | "2025-06-11T19:36:31+00:00" |
      | metadata.initiatingTimestamp | "2025-06-11T21:36:31+02:00" |

    When I replay the contentGraph projection
    When I am in workspace "live" and dimension space point {"language": "de"}
    Then I expect the node "fa0affac-0baa-a530-84d4-58adb5900f93" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2025-06-11 19:36:31 | 2025-06-11 19:36:31 |              |                      |

  Scenario: No offset for first batch of events
    Given I have the following raw events to upgrade:
      | sequencenumber | stream                                             | version | type                                | payload                                                                                                                                                                                                                                                                              | metadata                                                                           | id                                   | correlationid                          | recordedat          |
      # no offset specified
      | 1              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 0       | ContentStreamWasCreated             | {"contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f"}                                                                                                                                                                                                                           | []                                                                                 | c546edf2-8c0a-4cf6-9fce-d97fe0671be6 | CreateRootWorkspace_43ddfa553532efb671 | 2025-05-11 20:36:31 |
      | 2              | Workspace:live                                     | 0       | RootWorkspaceWasCreated             | {"workspaceName":"live","newContentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f"}                                                                                                                                                                                                 | []                                                                                 | 0e6097f3-cc22-44f9-a6f8-81c1f31f7116 | CreateRootWorkspace_43ddfa553532efb671 | 2025-05-11 20:36:31 |
      | 3              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 1       | RootNodeAggregateWithNodeWasCreated | {"workspaceName":"live","contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f","nodeAggregateId":"fa0affac-0baa-a530-84d4-58adb5900f93","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[{"language":"en"},{"language":"de"}],"nodeAggregateClassification":"root"} | []                                                                                 | 2c8b9d29-c3fd-44e0-b275-15d336dc38ab | NodeCreate_XXX                         | 2025-05-11 21:36:31 |
      # +2h offset specified
      | 4              | ContentStream:cs-new                               | 0       | ContentStreamWasForked              | {"newContentStreamId":"cs-new", "sourceContentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f", "versionOfSourceContentStream": 1}                                                                                                                                                   | {"initiatingUserId": "system", "initiatingTimestamp": "2025-06-11T20:36:31+02:00"} | 140c7987-f768-4ccd-9e1a-4f521deb3ff9 | CreateRootWorkspace_43ddfa553532efb671 | 2025-06-11 20:36:31 |
      | 5              | Workspace:ws-new                                   | 0       | WorkspaceWasCreated                 | {"workspaceName":"ws-new","newContentStreamId":"cs-new", "baseWorkspaceName": "live"}                                                                                                                                                                                                | {"initiatingUserId": "system", "initiatingTimestamp": "2025-06-11T20:36:31+02:00"} | ceb6eaa6-a91f-4890-a386-5f3449055e00 | CreateRootWorkspace_43ddfa553532efb671 | 2025-06-11 20:36:31 |
      # would need migration as +2h
      | 6              | ContentStream:cs-new                               | 1       | RootNodeAggregateWithNodeWasCreated | {"workspaceName":"ws-new","contentStreamId":"cs-new","nodeAggregateId":"new-node-id","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[{"language":"en"},{"language":"de"}],"nodeAggregateClassification":"root"}                                                      | []                                                                                 | 1d216fda-c386-4161-bcba-6678042e55f5 | NodeCreate_XXX                         | 2025-06-11 21:36:31 |

    And I upgrade events recordedAt to utc
    Then I expect the following upgrade output:
      """
      Migration necessary. Found following non UTC offsets [+02:00]
          Debug: [{"sequenceNumber":4,"tzoffset":"+02:00"}]
      Backup: copying events table to cr_default_events_bkp_2024_09_22_12_00_00

      Migration applied to 3 events. Please replay the projections `./flow subscription:replayall` to see the new adjusted UTC dates in the node timestamps
      Done. Please dont re-rerun the migration.
      """

    When I replay the contentGraph projection
    When I am in workspace "live" and dimension space point {"language": "de"}
    # first events are unchanged
    Then I expect the node "fa0affac-0baa-a530-84d4-58adb5900f93" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2025-05-11 21:36:31 | 2025-05-11 21:36:31 |              |                      |

    When I am in workspace "ws-new" and dimension space point {"language": "de"}
    Then I expect the node "new-node-id" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2025-06-11 19:36:31 | 2025-06-11 19:36:31 |              |                      |

  Scenario: Non utc dates in events

    Given I have the following raw events to upgrade:
      | sequencenumber | stream                                             | version | type                                | payload                                                                                                                                                                                                                                                                              | metadata                                                                           | id                                   | correlationid                          | recordedat          |
      | 1              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 0       | ContentStreamWasCreated             | {"contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f"}                                                                                                                                                                                                                           | {"initiatingUserId": "system", "initiatingTimestamp": "2025-06-11T20:36:31+02:00"} | c546edf2-8c0a-4cf6-9fce-d97fe0671be6 | CreateRootWorkspace_43ddfa553532efb671 | 2025-06-11 20:36:31 |
      | 2              | Workspace:live                                     | 0       | RootWorkspaceWasCreated             | {"workspaceName":"live","newContentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f"}                                                                                                                                                                                                 | {"initiatingUserId": "system", "initiatingTimestamp": "2025-06-11T20:36:31+02:00"} | 0e6097f3-cc22-44f9-a6f8-81c1f31f7116 | CreateRootWorkspace_43ddfa553532efb671 | 2025-06-11 20:36:31 |
      | 3              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 1       | RootNodeAggregateWithNodeWasCreated | {"workspaceName":"live","contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f","nodeAggregateId":"fa0affac-0baa-a530-84d4-58adb5900f93","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[{"language":"en"},{"language":"de"}],"nodeAggregateClassification":"root"} | []                                                                                 | 2c8b9d29-c3fd-44e0-b275-15d336dc38ab | EventStoreImporter_60275c85c589c36a02  | 2025-06-11 20:36:33 |

    # replay works with the old data - just with wrong dates
    When I replay the contentGraph projection
    When I am in workspace "live" and dimension space point {"language": "de"}
    Then I expect the node "fa0affac-0baa-a530-84d4-58adb5900f93" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2025-06-11 20:36:33 | 2025-06-11 20:36:33 |              |                      |

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

    # rerunning again prevents an invalid duplicate upgrade
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

  Scenario: Multiple different utc dates in events
    Given I have the following raw events to upgrade:
      | sequencenumber | stream                                             | version | type                                | payload                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     | metadata                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 | id                                   | correlationid                           | recordedat          |
      # +02:00 offset
      | 1              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 0       | ContentStreamWasCreated             | {"contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f"}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  | {"initiatingUserId": "system", "initiatingTimestamp": "2025-06-11T20:36:31+02:00"}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | c546edf2-8c0a-4cf6-9fce-d97fe0671be6 | CreateRootWorkspace_43ddfa553532efb671  | 2025-06-11 20:36:31 |
      | 2              | Workspace:live                                     | 0       | RootWorkspaceWasCreated             | {"workspaceName":"live","newContentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f"}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        | {"initiatingUserId": "system", "initiatingTimestamp": "2025-06-11T20:36:31+02:00"}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | 0e6097f3-cc22-44f9-a6f8-81c1f31f7116 | CreateRootWorkspace_43ddfa553532efb671  | 2025-06-11 20:36:31 |
      | 3              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 1       | RootNodeAggregateWithNodeWasCreated | {"workspaceName":"live","contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f","nodeAggregateId":"fa0affac-0baa-a530-84d4-58adb5900f93","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[{"language":"en"},{"language":"de"}],"nodeAggregateClassification":"root"}                                                                                                                                                                                                                                                                                                                                                        | {"initiatingUserId": "system", "initiatingTimestamp": "2025-06-11T20:36:33+02:00"}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | 2c8b9d29-c3fd-44e0-b275-15d336dc38ab | EventStoreImporter_60275c85c589c36a02   | 2025-06-11 20:36:33 |
      # correct UTC timestamps
      | 4              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 2       | NodeAggregateWithNodeWasCreated     | {"workspaceName":"live","contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f","nodeAggregateId":"fd298b7f-9cdf-46dd-b30b-dad85c4abf94","nodeTypeName":"Neos.NeosIo.ServiceOfferings:Document.ServiceProvider","originDimensionSpacePoint":{"language":"en"},"succeedingSiblingsForCoverage":[{"dimensionSpacePoint":{"language":"en"},"nodeAggregateId":"28e91088-9cba-41f3-ab39-15d06c04c483"},{"dimensionSpacePoint":{"language":"de"},"nodeAggregateId":null}],"parentNodeAggregateId":"fa0affac-0baa-a530-84d4-58adb5900f93","nodeName":null,"initialPropertyValues":{},"nodeAggregateClassification":"regular","nodeReferences":[]} | {"commandClass": "NeosContentRepositoryCoreFeatureNodeCreationCommandCreateNodeAggregateWithNodeAndSerializedProperties", "debug_reason": "Rebased from 220608", "commandPayload": {"nodeName": null, "references": [], "nodeTypeName": "Neos.NeosIo.ServiceOfferings:Document.ServiceProvider", "workspaceName": "live", "nodeAggregateId": "fd298b7f-9cdf-46dd-b30b-dad85c4abf94", "initialPropertyValues": {}, "parentNodeAggregateId": "fa0affac-0baa-a530-84d4-58adb5900f93", "originDimensionSpacePoint": {"language": "en"}, "succeedingSiblingNodeAggregateId": null, "tetheredDescendantNodeAggregateIds": {"awards": "ae226bc0-8686-422b-8cb9-e2eb5a3fc14e"}}, "initiatingUserId": "06f2368b-7685-408c-8a36-4c2f5862528a", "initiatingTimestamp": "2025-06-12T06:25:17+00:00"} | c1310e79-3b4f-487e-858c-db38617f8873 | PublishIndividualNod_8d87d725d71dd47d86 | 2025-06-12 06:28:46 |
      | 5              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 3       | NodeAggregateWithNodeWasCreated     | {"workspaceName":"live","contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f","nodeAggregateId":"ae226bc0-8686-422b-8cb9-e2eb5a3fc14e","nodeTypeName":"Neos.Neos:ContentCollection","originDimensionSpacePoint":{"language":"en"},"succeedingSiblingsForCoverage":[{"dimensionSpacePoint":{"language":"en"},"nodeAggregateId":null},{"dimensionSpacePoint":{"language":"de"},"nodeAggregateId":null}],"parentNodeAggregateId":"fd298b7f-9cdf-46dd-b30b-dad85c4abf94","nodeName":"awards","initialPropertyValues":[],"nodeAggregateClassification":"tethered","nodeReferences":[]}                                                        | {"initiatingUserId": "system", "initiatingTimestamp": "2025-06-11T06:28:46+00:00"}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | ceb68f3c-f616-49d2-9790-480939509dc6 | PublishIndividualNod_8d87d725d71dd47d86 | 2025-06-12 06:28:46 |
      # +01:00 offset
      | 6              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 4       | NodeAggregateWithNodeWasCreated     | {"workspaceName":"live","contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f","nodeAggregateId":"node-id-offset-utc-2h","nodeTypeName":"Neos.NeosIo.ServiceOfferings:Document.ServiceProvider","originDimensionSpacePoint":{"language":"en"},"succeedingSiblingsForCoverage":[{"dimensionSpacePoint":{"language":"en"},"nodeAggregateId":"28e91088-9cba-41f3-ab39-15d06c04c483"},{"dimensionSpacePoint":{"language":"de"},"nodeAggregateId":null}],"parentNodeAggregateId":"fa0affac-0baa-a530-84d4-58adb5900f93","nodeName":null,"initialPropertyValues":{},"nodeAggregateClassification":"regular","nodeReferences":[]}                | {"commandClass": "NeosContentRepositoryCoreFeatureNodeCreationCommandCreateNodeAggregateWithNodeAndSerializedProperties", "debug_reason": "Rebased from 220608", "commandPayload": {"nodeName": null, "references": [], "nodeTypeName": "Neos.NeosIo.ServiceOfferings:Document.ServiceProvider", "workspaceName": "live", "nodeAggregateId": "node-id-offset-utc-2h", "initialPropertyValues": {}, "parentNodeAggregateId": "fa0affac-0baa-a530-84d4-58adb5900f93", "originDimensionSpacePoint": {"language": "en"}, "succeedingSiblingNodeAggregateId": null, "tetheredDescendantNodeAggregateIds": {"awards": "child-node-id-offset-utc-2h"}}, "initiatingUserId": "06f2368b-7685-408c-8a36-4c2f5862528a", "initiatingTimestamp": "2025-07-12T07:28:46+01:00"}                         | 8722af37-82df-4bf6-9fe9-664b9591a314 | CreateNode_XXX                          | 2025-07-12 07:28:46 |
      | 7              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 5       | NodeAggregateWithNodeWasCreated     | {"workspaceName":"live","contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f","nodeAggregateId":"child-node-id-offset-utc-2h","nodeTypeName":"Neos.Neos:ContentCollection","originDimensionSpacePoint":{"language":"en"},"succeedingSiblingsForCoverage":[{"dimensionSpacePoint":{"language":"en"},"nodeAggregateId":null},{"dimensionSpacePoint":{"language":"de"},"nodeAggregateId":null}],"parentNodeAggregateId":"node-id-offset-utc-2h","nodeName":"awards","initialPropertyValues":[],"nodeAggregateClassification":"tethered","nodeReferences":[]}                                                                                | {"initiatingUserId": "system", "initiatingTimestamp": "2025-07-12T07:28:46+01:00"}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | d6cbaa95-52a0-446f-ab5d-3a99940ba220 | CreateNode_XXX                          | 2025-07-12 07:28:46 |
      | 8              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 6       | NodeAggregateWithNodeWasCreated     | {"workspaceName":"live","contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f","nodeAggregateId":"second-child-node-id-offset-utc-2h","nodeTypeName":"Neos.Neos:Test","originDimensionSpacePoint":{"language":"en"},"succeedingSiblingsForCoverage":[{"dimensionSpacePoint":{"language":"en"},"nodeAggregateId":null},{"dimensionSpacePoint":{"language":"de"},"nodeAggregateId":null}],"parentNodeAggregateId":"node-id-offset-utc-2h","nodeName":null,"initialPropertyValues":[],"nodeAggregateClassification":"regular","nodeReferences":[]}                                                                                           | {"initiatingUserId": "system", "initiatingTimestamp": "2025-07-12T12:00:00+01:00"}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | cde1f20b-0891-464a-ba16-df38e30de1b0 | CreateNode_XXX                          | 2025-07-12 12:00:00 |

    And I upgrade events recordedAt to utc
    Then I expect the following upgrade output:
      """
      Migration necessary. Found following non UTC offsets [+02:00, +01:00]
          Debug: [{"sequenceNumber":1,"tzoffset":"+02:00"},{"sequenceNumber":4,"tzoffset":"+00:00"},{"sequenceNumber":6,"tzoffset":"+01:00"}]
      Backup: copying events table to cr_default_events_bkp_2024_09_22_12_00_00

      Migration applied to 6 events. Please replay the projections `./flow subscription:replayall` to see the new adjusted UTC dates in the node timestamps
      Done. Please dont re-rerun the migration.
      """

    # replay works
    When I replay the contentGraph projection
    When I am in workspace "live" and dimension space point {"language": "de"}
    # +2h is normalised
    Then I expect the node "fa0affac-0baa-a530-84d4-58adb5900f93" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2025-06-11 18:36:33 | 2025-06-11 18:36:33 |              |                      |

    # unchanged
    Then I expect the node "fd298b7f-9cdf-46dd-b30b-dad85c4abf94" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2025-06-12 06:28:46 | 2025-06-12 06:25:17 |              |                      |

    # +1h is normalised
    Then I expect the node "node-id-offset-utc-2h" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2025-07-12 06:28:46 | 2025-07-12 06:28:46 |              |                      |
    Then I expect the node "child-node-id-offset-utc-2h" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2025-07-12 06:28:46 | 2025-07-12 06:28:46 |              |                      |
    Then I expect the node "second-child-node-id-offset-utc-2h" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2025-07-12 11:00:00 | 2025-07-12 11:00:00 |              |                      |

  Scenario: Multiple different utc dates in events (with missing initiatingTimestamp)
    Given I have the following raw events to upgrade:
      | sequencenumber | stream                                             | version | type                                | payload                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     | metadata                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 | id                                   | correlationid                           | recordedat          |
      # +02:00 offset
      | 1              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 0       | ContentStreamWasCreated             | {"contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f"}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  | {"initiatingUserId": "system", "initiatingTimestamp": "2025-06-11T20:36:31+02:00"}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | c546edf2-8c0a-4cf6-9fce-d97fe0671be6 | CreateRootWorkspace_43ddfa553532efb671  | 2025-06-11 20:36:31 |
      | 2              | Workspace:live                                     | 0       | RootWorkspaceWasCreated             | {"workspaceName":"live","newContentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f"}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        | {"initiatingUserId": "system", "initiatingTimestamp": "2025-06-11T20:36:31+02:00"}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | 0e6097f3-cc22-44f9-a6f8-81c1f31f7116 | CreateRootWorkspace_43ddfa553532efb671  | 2025-06-11 20:36:31 |
      | 3              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 1       | RootNodeAggregateWithNodeWasCreated | {"workspaceName":"live","contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f","nodeAggregateId":"fa0affac-0baa-a530-84d4-58adb5900f93","nodeTypeName":"Neos.Neos:Sites","coveredDimensionSpacePoints":[{"language":"en"},{"language":"de"}],"nodeAggregateClassification":"root"}                                                                                                                                                                                                                                                                                                                                                        | []                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | 2c8b9d29-c3fd-44e0-b275-15d336dc38ab | EventStoreImporter_60275c85c589c36a02   | 2025-06-11 20:36:33 |
      # correct UTC timestamps
      | 4              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 2       | NodeAggregateWithNodeWasCreated     | {"workspaceName":"live","contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f","nodeAggregateId":"fd298b7f-9cdf-46dd-b30b-dad85c4abf94","nodeTypeName":"Neos.NeosIo.ServiceOfferings:Document.ServiceProvider","originDimensionSpacePoint":{"language":"en"},"succeedingSiblingsForCoverage":[{"dimensionSpacePoint":{"language":"en"},"nodeAggregateId":"28e91088-9cba-41f3-ab39-15d06c04c483"},{"dimensionSpacePoint":{"language":"de"},"nodeAggregateId":null}],"parentNodeAggregateId":"fa0affac-0baa-a530-84d4-58adb5900f93","nodeName":null,"initialPropertyValues":{},"nodeAggregateClassification":"regular","nodeReferences":[]} | {"commandClass": "NeosContentRepositoryCoreFeatureNodeCreationCommandCreateNodeAggregateWithNodeAndSerializedProperties", "debug_reason": "Rebased from 220608", "commandPayload": {"nodeName": null, "references": [], "nodeTypeName": "Neos.NeosIo.ServiceOfferings:Document.ServiceProvider", "workspaceName": "live", "nodeAggregateId": "fd298b7f-9cdf-46dd-b30b-dad85c4abf94", "initialPropertyValues": {}, "parentNodeAggregateId": "fa0affac-0baa-a530-84d4-58adb5900f93", "originDimensionSpacePoint": {"language": "en"}, "succeedingSiblingNodeAggregateId": null, "tetheredDescendantNodeAggregateIds": {"awards": "ae226bc0-8686-422b-8cb9-e2eb5a3fc14e"}}, "initiatingUserId": "06f2368b-7685-408c-8a36-4c2f5862528a", "initiatingTimestamp": "2025-06-12T06:25:17+00:00"} | c1310e79-3b4f-487e-858c-db38617f8873 | PublishIndividualNod_8d87d725d71dd47d86 | 2025-06-12 06:28:46 |
      # missing initiatingTimestamp
      | 5              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 3       | NodeAggregateWithNodeWasCreated     | {"workspaceName":"live","contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f","nodeAggregateId":"ae226bc0-8686-422b-8cb9-e2eb5a3fc14e","nodeTypeName":"Neos.Neos:ContentCollection","originDimensionSpacePoint":{"language":"en"},"succeedingSiblingsForCoverage":[{"dimensionSpacePoint":{"language":"en"},"nodeAggregateId":null},{"dimensionSpacePoint":{"language":"de"},"nodeAggregateId":null}],"parentNodeAggregateId":"fd298b7f-9cdf-46dd-b30b-dad85c4abf94","nodeName":"awards","initialPropertyValues":[],"nodeAggregateClassification":"tethered","nodeReferences":[]}                                                        | []                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | ceb68f3c-f616-49d2-9790-480939509dc6 | PublishIndividualNod_8d87d725d71dd47d86 | 2025-06-12 06:28:46 |
      # +01:00 offset
      | 6              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 4       | NodeAggregateWithNodeWasCreated     | {"workspaceName":"live","contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f","nodeAggregateId":"node-id-offset-utc-2h","nodeTypeName":"Neos.NeosIo.ServiceOfferings:Document.ServiceProvider","originDimensionSpacePoint":{"language":"en"},"succeedingSiblingsForCoverage":[{"dimensionSpacePoint":{"language":"en"},"nodeAggregateId":"28e91088-9cba-41f3-ab39-15d06c04c483"},{"dimensionSpacePoint":{"language":"de"},"nodeAggregateId":null}],"parentNodeAggregateId":"fa0affac-0baa-a530-84d4-58adb5900f93","nodeName":null,"initialPropertyValues":{},"nodeAggregateClassification":"regular","nodeReferences":[]}                | {"commandClass": "NeosContentRepositoryCoreFeatureNodeCreationCommandCreateNodeAggregateWithNodeAndSerializedProperties", "debug_reason": "Rebased from 220608", "commandPayload": {"nodeName": null, "references": [], "nodeTypeName": "Neos.NeosIo.ServiceOfferings:Document.ServiceProvider", "workspaceName": "live", "nodeAggregateId": "node-id-offset-utc-2h", "initialPropertyValues": {}, "parentNodeAggregateId": "fa0affac-0baa-a530-84d4-58adb5900f93", "originDimensionSpacePoint": {"language": "en"}, "succeedingSiblingNodeAggregateId": null, "tetheredDescendantNodeAggregateIds": {"awards": "child-node-id-offset-utc-2h"}}, "initiatingUserId": "06f2368b-7685-408c-8a36-4c2f5862528a", "initiatingTimestamp": "2025-07-12T07:28:46+01:00"}                         | 8722af37-82df-4bf6-9fe9-664b9591a314 | CreateNode_XXX                          | 2025-07-12 07:28:46 |
      # missing initiatingTimestamp
      | 7              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 5       | NodeAggregateWithNodeWasCreated     | {"workspaceName":"live","contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f","nodeAggregateId":"child-node-id-offset-utc-2h","nodeTypeName":"Neos.Neos:ContentCollection","originDimensionSpacePoint":{"language":"en"},"succeedingSiblingsForCoverage":[{"dimensionSpacePoint":{"language":"en"},"nodeAggregateId":null},{"dimensionSpacePoint":{"language":"de"},"nodeAggregateId":null}],"parentNodeAggregateId":"node-id-offset-utc-2h","nodeName":"awards","initialPropertyValues":[],"nodeAggregateClassification":"tethered","nodeReferences":[]}                                                                                | []                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | d6cbaa95-52a0-446f-ab5d-3a99940ba220 | CreateNode_XXX                          | 2025-07-12 07:28:46 |
      # ALSO missing initiatingTimestamp
      | 8              | ContentStream:8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f | 6       | NodeAggregateWithNodeWasCreated     | {"workspaceName":"live","contentStreamId":"8bbf8603-c7cb-4a2a-9e49-34f4e1f6782f","nodeAggregateId":"second-child-node-id-offset-utc-2h","nodeTypeName":"Neos.Neos:Test","originDimensionSpacePoint":{"language":"en"},"succeedingSiblingsForCoverage":[{"dimensionSpacePoint":{"language":"en"},"nodeAggregateId":null},{"dimensionSpacePoint":{"language":"de"},"nodeAggregateId":null}],"parentNodeAggregateId":"node-id-offset-utc-2h","nodeName":null,"initialPropertyValues":[],"nodeAggregateClassification":"regular","nodeReferences":[]}                                                                                           | []                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | cde1f20b-0891-464a-ba16-df38e30de1b0 | CreateNode_XXX                          | 2025-07-12 12:00:00 |

    And I upgrade events recordedAt to utc
    Then I expect the following upgrade output:
      """
      Migration necessary. Found following non UTC offsets [+02:00, +01:00]
          Debug: [{"sequenceNumber":1,"tzoffset":"+02:00"},{"sequenceNumber":4,"tzoffset":"+00:00"},{"sequenceNumber":6,"tzoffset":"+01:00"}]
      Backup: copying events table to cr_default_events_bkp_2024_09_22_12_00_00

      Migration applied to 6 events. Please replay the projections `./flow subscription:replayall` to see the new adjusted UTC dates in the node timestamps
      Done. Please dont re-rerun the migration.
      """

    # replay works
    When I replay the contentGraph projection
    When I am in workspace "live" and dimension space point {"language": "de"}
    # +2h is normalised
    Then I expect the node "fa0affac-0baa-a530-84d4-58adb5900f93" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2025-06-11 18:36:33 | 2025-06-11 18:36:33 |              |                      |

    # unchanged
    Then I expect the node "fd298b7f-9cdf-46dd-b30b-dad85c4abf94" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2025-06-12 06:28:46 | 2025-06-12 06:25:17 |              |                      |

    # +1h is normalised
    Then I expect the node "node-id-offset-utc-2h" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2025-07-12 06:28:46 | 2025-07-12 06:28:46 |              |                      |
    Then I expect the node "child-node-id-offset-utc-2h" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2025-07-12 06:28:46 | 2025-07-12 06:28:46 |              |                      |
    Then I expect the node "second-child-node-id-offset-utc-2h" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2025-07-12 11:00:00 | 2025-07-12 11:00:00 |              |                      |
