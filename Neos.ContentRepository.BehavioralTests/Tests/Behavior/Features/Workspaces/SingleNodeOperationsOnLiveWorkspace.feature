@contentrepository @adapters=DoctrineDBAL
Feature: Single Node operations on live workspace

  As a user of the CR I want to execute operations on a node in live workspace.

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value           |
      | workspaceName              | "live"          |
      | newContentStreamId | "cs-identifier" |
      | initiatingUserId   | "user-id"       |
    And the graph projection is fully up to date
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                         |
      | contentStreamId     | "cs-identifier"               |
      | nodeAggregateId     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
      | coveredDimensionSpacePoints | [{}]                          |
      | initiatingUserId    | "user-identifier"             |
      | nodeAggregateClassification | "root"                        |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamId       | "cs-identifier"                          |
      | nodeAggregateId       | "nody-mc-nodeface"                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | coveredDimensionSpacePoints   | [{}]                                     |
      | parentNodeAggregateId | "lady-eleonode-rootford"                 |
      | nodeName                      | "child"                                  |
      | nodeAggregateClassification   | "regular"                                |
    And the graph projection is fully up to date

  Scenario: Set property of a node
    Given the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | contentStreamId   | "cs-identifier"              |
      | nodeAggregateId   | "nody-mc-nodeface"           |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"text": "Hello"}            |
      | initiatingUserId  | "initiating-user-identifier" |

    Then I expect exactly 4 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 3 is of type "NodePropertiesWereSet" with payload:
      | Key                       | Expected                     |
      | contentStreamId   | "cs-identifier"              |
      | nodeAggregateId   | "nody-mc-nodeface"           |
      | originDimensionSpacePoint | []                           |
      | propertyValues.text.value | "Hello"                      |
      | initiatingUserId  | "initiating-user-identifier" |

    When the graph projection is fully up to date
    And I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value   |
      | text | "Hello" |

  Scenario: Error on invalid dimension space point
    Given the command SetNodeProperties is executed with payload and exceptions are caught:
      | Key                       | Value               |
      | contentStreamId   | "cs-identifier"     |
      | nodeAggregateId   | "nody-mc-nodeface"  |
      | originDimensionSpacePoint | {"not": "existing"} |
      | propertyValues            | {"text": "Hello"}   |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"
