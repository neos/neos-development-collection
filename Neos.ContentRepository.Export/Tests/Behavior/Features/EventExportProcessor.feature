@contentrepository
Feature: As a user of the CR I want to export the event stream using the EventExportProcessor

  Background:
    Given the current date and time is "2023-03-16T12:00:00+01:00"
    Given using the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | de, gsw, fr | gsw->de         |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                    |
      | workspaceName               | "live"                                                   |
      | nodeAggregateId             | "nody-mc-nodeface"                                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint   | {"language":"de"}                                        |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                 |
      | nodeName                    | "child-document"                                         |
      | nodeAggregateClassification | "regular"                                                |

  Scenario: Export the event stream
    Then I expect exactly 3 events to be published on stream with prefix "ContentStream:cs-identifier"
    When the events are exported
    Then I expect the following jsonl:
      """
      {"identifier":"random-event-uuid","type":"RootNodeAggregateWithNodeWasCreated","payload":{"nodeAggregateId":"lady-eleonode-rootford","nodeTypeName":"Neos.ContentRepository:Root","coveredDimensionSpacePoints":[{"language":"de"},{"language":"gsw"},{"language":"fr"}],"nodeAggregateClassification":"root"},"metadata":{"initiatingUserId":"system","initiatingTimestamp":"2023-03-16T12:00:00+01:00"}}
      {"identifier":"random-event-uuid","type":"NodeAggregateWithNodeWasCreated","payload":{"nodeAggregateId":"nody-mc-nodeface","nodeTypeName":"Neos.ContentRepository.Testing:Document","originDimensionSpacePoint":{"language":"de"},"succeedingSiblingsForCoverage":[{"dimensionSpacePoint":{"language":"de"},"nodeAggregateId":null},{"dimensionSpacePoint":{"language":"gsw"},"nodeAggregateId":null}],"parentNodeAggregateId":"lady-eleonode-rootford","nodeName":"child-document","initialPropertyValues":[],"nodeAggregateClassification":"regular","nodeReferences":[]},"metadata":{"initiatingUserId":"system","initiatingTimestamp":"2023-03-16T12:00:00+01:00"}}

      """
