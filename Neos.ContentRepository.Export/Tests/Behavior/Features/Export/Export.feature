@contentrepository
Feature: As a user of the CR I want to export the event stream

  Background:
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
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                    |
      | workspaceName               | "live"                                                   |
      | contentStreamId             | "cs-identifier"                                          |
      | nodeAggregateId             | "nody-mc-nodeface"                                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint   | {"language":"de"}                                        |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                 |
      | nodeName                    | "child-document"                                         |
      | nodeAggregateClassification | "regular"                                                |

  Scenario: Export the event stream
    Then I expect exactly 3 events to be published on stream with prefix "ContentStream:cs-identifier"
    When the events are exported
    Then I expect the following jsonl:
      """
      {"identifier":"random-event-uuid","type":"RootNodeAggregateWithNodeWasCreated","payload":{"workspaceName":"live","contentStreamId":"cs-identifier","nodeAggregateId":"lady-eleonode-rootford","nodeTypeName":"Neos.ContentRepository:Root","coveredDimensionSpacePoints":[{"language":"de"},{"language":"gsw"},{"language":"fr"}],"nodeAggregateClassification":"root"},"metadata":{"commandClass":"Neos\\ContentRepository\\Core\\Feature\\RootNodeCreation\\Command\\CreateRootNodeAggregateWithNode","commandPayload":{"workspaceName":"live","nodeAggregateId":"lady-eleonode-rootford","nodeTypeName":"Neos.ContentRepository:Root","tetheredDescendantNodeAggregateIds":[]},"initiatingUserId":"system","initiatingTimestamp":"random-time"}}
      {"identifier":"random-event-uuid","type":"NodeAggregateWithNodeWasCreated","payload":{"workspaceName":"live","contentStreamId":"cs-identifier","nodeAggregateId":"nody-mc-nodeface","nodeTypeName":"Neos.ContentRepository.Testing:Document","originDimensionSpacePoint":{"language":"de"},"succeedingSiblingsForCoverage":[{"dimensionSpacePoint":{"language":"de"},"nodeAggregateId":null},{"dimensionSpacePoint":{"language":"gsw"},"nodeAggregateId":null},{"dimensionSpacePoint":{"language":"fr"},"nodeAggregateId":null}],"parentNodeAggregateId":"lady-eleonode-rootford","nodeName":"child-document","initialPropertyValues":[],"nodeAggregateClassification":"regular","nodeReferences":[]},"metadata":{"initiatingTimestamp":"random-time"}}

      """
