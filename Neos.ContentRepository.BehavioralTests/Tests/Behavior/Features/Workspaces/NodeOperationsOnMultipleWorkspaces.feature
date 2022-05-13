@fixtures @adapters=DoctrineDBAL
Feature: Single Node operations on multiple workspaces/content streams; e.g. copy on write!

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value           |
      | workspaceName              | "live"          |
      | newContentStreamIdentifier | "cs-identifier" |
      | initiatingUserIdentifier   | "user-id"       |
    And the graph projection is fully up to date
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                         |
      | contentStreamIdentifier     | "cs-identifier"               |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
      | coveredDimensionSpacePoints | [{}]                          |
      | initiatingUserIdentifier    | "user-identifier"             |
      | nodeAggregateClassification | "root"                        |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "cs-identifier"                          |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | coveredDimensionSpacePoints   | [{}]                                     |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                 |
      | nodeName                      | "child"                                  |
      | nodeAggregateClassification   | "regular"                                |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "cs-identifier"                          |
      | nodeAggregateIdentifier       | "nodingers-cat"                          |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | coveredDimensionSpacePoints   | [{}]                                     |
      | parentNodeAggregateIdentifier | "nody-mc-nodeface"                       |
      | nodeName                      | "pet"                                    |
      | nodeAggregateClassification   | "regular"                                |
    And the graph projection is fully up to date
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | contentStreamIdentifier   | "cs-identifier"              |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"           |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"text": "Original"}         |
      | initiatingUserIdentifier  | "initiating-user-identifier" |
    And the graph projection is fully up to date
    And the command CreateWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "user-test"          |
      | baseWorkspaceName          | "live"               |
      | newContentStreamIdentifier | "user-cs-identifier" |
      | initiatingUserIdentifier   | "user"               |
    And the graph projection is fully up to date

  Scenario: Set property of a node
    Given the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | contentStreamIdentifier   | "user-cs-identifier"         |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"           |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"text": "Changed"}          |
      | initiatingUserIdentifier  | "initiating-user-identifier" |

    Then I expect exactly 2 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:user-cs-identifier"
    And event at index 1 is of type "NodePropertiesWereSet" with payload:
      | Key                       | Expected                     |
      | contentStreamIdentifier   | "user-cs-identifier"         |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"           |
      | originDimensionSpacePoint | []                           |
      | propertyValues.text.value | "Changed"                    |
      | initiatingUserIdentifier  | "initiating-user-identifier" |

    When the graph projection is fully up to date
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
