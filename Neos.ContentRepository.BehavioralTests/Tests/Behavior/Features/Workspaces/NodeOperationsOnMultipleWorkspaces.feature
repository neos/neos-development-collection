@contentrepository @adapters=DoctrineDBAL
Feature: Single Node operations on multiple workspaces/content streams; e.g. copy on write!

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value           |
      | workspaceName              | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in the active content stream of workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                         |
      | nodeAggregateId     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
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
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamId       | "cs-identifier"                          |
      | nodeAggregateId       | "nodingers-cat"                          |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | coveredDimensionSpacePoints   | [{}]                                     |
      | parentNodeAggregateId | "nody-mc-nodeface"                       |
      | nodeName                      | "pet"                                    |
      | nodeAggregateClassification   | "regular"                                |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | nodeAggregateId   | "nody-mc-nodeface"           |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"text": "Original"}         |
    And the command CreateWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "user-test"          |
      | baseWorkspaceName          | "live"               |
      | newContentStreamId | "user-cs-identifier" |

  Scenario: Set property of a node
    Given the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | workspaceName              | "user-test"          |
      | nodeAggregateId   | "nody-mc-nodeface"           |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"text": "Changed"}          |

    Then I expect exactly 2 events to be published on stream with prefix "ContentStream:user-cs-identifier"
    And event at index 1 is of type "NodePropertiesWereSet" with payload:
      | Key                       | Expected                     |
      | contentStreamId   | "user-cs-identifier"         |
      | nodeAggregateId   | "nody-mc-nodeface"           |
      | originDimensionSpacePoint | []                           |
      | propertyValues.text.value | "Changed"                    |
    And I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value      |
      | text | "Original" |

    When I am in the active content stream of workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value     |
      | text | "Changed" |

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nodingers-cat" and node path "child/pet" to lead to node cs-identifier;nodingers-cat;{}
    When I go to the parent node of node aggregate "nodingers-cat"
    Then I expect this node to have the following properties:
      | Key  | Value      |
      | text | "Original" |

    When I am in the active content stream of workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nodingers-cat" and node path "child/pet" to lead to node user-cs-identifier;nodingers-cat;{}
    When I go to the parent node of node aggregate "nodingers-cat"
    Then I expect this node to have the following properties:
      | Key  | Value     |
      | text | "Changed" |
