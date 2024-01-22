@contentrepository @adapters=DoctrineDBAL
Feature: Node Property Conversion

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Content':
      properties:
        dateProperty:
          type: DateTime
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value           |
      | workspaceName              | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                         |
      | contentStreamId     | "cs-identifier"               |
      | nodeAggregateId     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date

  Scenario: DateTime objects at Node Creation
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                              |
      | contentStreamId       | "cs-identifier"                                    |
      | nodeAggregateId       | "nody-mc-nodeface"                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"           |
      | originDimensionSpacePoint     | {}                                                 |
      | parentNodeAggregateId | "lady-eleonode-rootford"                           |
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
      | contentStreamId       | "cs-identifier"                                    |
      | nodeAggregateId       | "nody-mc-nodeface"                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"           |
      | originDimensionSpacePoint     | {}                                                 |
      | parentNodeAggregateId | "lady-eleonode-rootford"                           |
      | initialPropertyValues         | {"dateProperty": "Date:1997-07-16T19:20:30+05:00"} |
    And the graph projection is fully up to date

    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                                              |
      | contentStreamId   | "cs-identifier"                                    |
      | nodeAggregateId   | "nody-mc-nodeface"                                 |
      | originDimensionSpacePoint | {}                                                 |
      | propertyValues            | {"dateProperty": "Date:1997-07-19T19:20:30+05:00"} |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key          | Value                          |
      | dateProperty | Date:1997-07-19T19:20:30+05:00 |
