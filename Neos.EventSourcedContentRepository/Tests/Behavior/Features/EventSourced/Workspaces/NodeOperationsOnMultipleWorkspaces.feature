@fixtures
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
    And the command "SetNodeProperties" is executed with payload:
      | Key                       | Value                                             |
      | contentStreamIdentifier   | "cs-identifier"                                   |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                                |
      | originDimensionSpacePoint | {}                                                |
      | propertyValues            | {"text": {"type": "string", "value": "Original"}} |
      | initiatingUserIdentifier  | "initiating-user-identifier"                      |
    And the graph projection is fully up to date
    And the command CreateWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "user-test"          |
      | baseWorkspaceName          | "live"               |
      | newContentStreamIdentifier | "user-cs-identifier" |
    And the graph projection is fully up to date

  Scenario: Set property of a node
    Given the command "SetNodeProperties" is executed with payload:
      | Key                       | Value                                            |
      | contentStreamIdentifier   | "user-cs-identifier"                             |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                               |
      | originDimensionSpacePoint | {}                                               |
      | propertyValues            | {"text": {"type": "string", "value": "Changed"}} |
      | initiatingUserIdentifier  | "initiating-user-identifier"                     |

    Then I expect exactly 2 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:user-cs-identifier"
    And event at index 1 is of type "Neos.EventSourcedContentRepository:NodePropertiesWereSet" with payload:
      | Key                       | Expected                     |
      | contentStreamIdentifier   | "user-cs-identifier"         |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"           |
      | originDimensionSpacePoint | []                           |
      | propertyValues.text.value | "Changed"                    |
      | initiatingUserIdentifier  | "initiating-user-identifier" |

    When the graph projection is fully up to date
    And I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value    |
      | text | Original |

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value   |
      | text | Changed |

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect node aggregate identifier "nodingers-cat" and path "child/pet" to lead to node {"contentStreamIdentifier": "cs-identifier", "nodeAggregateIdentifier": "nodingers-cat", "originDimensionSpacePoint": {}}
    When I go to the parent node of node aggregate "nodingers-cat"
    Then I expect the current Node to have the properties:
      | Key  | Value    |
      | text | Original |

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect node aggregate identifier "nodingers-cat" and path "child/pet" to lead to node {"contentStreamIdentifier": "user-cs-identifier", "nodeAggregateIdentifier": "nodingers-cat", "originDimensionSpacePoint": {}}
    When I go to the parent node of node aggregate "nodingers-cat"
    Then I expect the current Node to have the properties:
      | Key  | Value   |
      | text | Changed |
