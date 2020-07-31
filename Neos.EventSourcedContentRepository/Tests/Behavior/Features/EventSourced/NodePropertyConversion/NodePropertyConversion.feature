@fixtures
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
      | Key                                | Value                                    |
      | contentStreamIdentifier            | "cs-identifier"                          |
      | nodeAggregateIdentifier            | "nody-mc-nodeface"                       |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint          | {}                                       |
      | initiatingUserIdentifier           | "00000000-0000-0000-0000-000000000000"   |
      | parentNodeAggregateIdentifier      | "lady-eleonode-rootford"                 |
      | initialPropertyValues.dateProperty | "1997-07-16T19:20:30+05:00"              |

    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the subgraph
    And I expect this node to have the properties:
      | Key          | Value                     | Type     |
      | dateProperty | 1997-07-16T19:20:30+05:00 | DateTime |

    # we have a real date object, so we can take the same timestamp from another timezone and it matches as well.
    And I expect this node to have the properties:
      | Key          | Value                     | Type     |
      | dateProperty | 1997-07-16T18:20:30+04:00 | DateTime |


  Scenario: DateTime objects at Node Property Updating
    Given the command CreateNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                    |
      | contentStreamIdentifier            | "cs-identifier"                          |
      | nodeAggregateIdentifier            | "nody-mc-nodeface"                       |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint          | {}                                       |
      | initiatingUserIdentifier           | "00000000-0000-0000-0000-000000000000"   |
      | parentNodeAggregateIdentifier      | "lady-eleonode-rootford"                 |
      | initialPropertyValues.dateProperty | "1997-07-16T19:20:30+05:00"              |
    And the graph projection is fully up to date

    When the command "SetNodeProperties" is executed with payload:
      | Key                         | Value                       |
      | contentStreamIdentifier     | "cs-identifier"             |
      | nodeAggregateIdentifier     | "nody-mc-nodeface"          |
      | originDimensionSpacePoint   | {}                          |
      | propertyValues.dateProperty | "1997-07-19T19:20:30+05:00" |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the subgraph
    And I expect this node to have the properties:
      | Key          | Value                     | Type     |
      | dateProperty | 1997-07-19T19:20:30+05:00 | DateTime |

    # we have a real date object, so we can take the same timestamp from another timezone and it matches as well.
    And I expect this node to have the properties:
      | Key          | Value                     | Type     |
      | dateProperty | 1997-07-19T18:20:30+04:00 | DateTime |

