@contentrepository @adapters=DoctrineDBAL
Feature: Node Property Conversion

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        dateProperty:
          type: DateTime
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
      | initiatingUserIdentifier    | "initiating-user-identifier"  |
      | nodeAggregateClassification | "root"                        |
    And the graph projection is fully up to date

  Scenario: DateTime objects at Node Creation
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                              |
      | contentStreamIdentifier       | "cs-identifier"                                    |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"           |
      | originDimensionSpacePoint     | {}                                                 |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"             |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                           |
      | initialPropertyValues         | {"dateProperty": "Date:1997-07-16T19:20:30+05:00"} |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key          | Value                          |
      | dateProperty | Date:1997-07-16T19:20:30+05:00 |

  Scenario: DateTime objects at Node Property Updating
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                              |
      | contentStreamIdentifier       | "cs-identifier"                                    |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"           |
      | originDimensionSpacePoint     | {}                                                 |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"             |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                           |
      | initialPropertyValues         | {"dateProperty": "Date:1997-07-16T19:20:30+05:00"} |
    And the graph projection is fully up to date

    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                              |
      | contentStreamIdentifier   | "cs-identifier"                                    |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                                 |
      | originDimensionSpacePoint | {}                                                 |
      | propertyValues            | {"dateProperty": "Date:1997-07-19T19:20:30+05:00"} |
      | initiatingUserIdentifier  | "initiating-user-identifier"                       |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key          | Value                          |
      | dateProperty | Date:1997-07-19T19:20:30+05:00 |
