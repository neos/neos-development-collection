@fixtures
Feature: Filter - Property not empty

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:Document': true

    'Neos.ContentRepository.Testing:Document': []
    """

    ########################
    # SETUP
    ########################
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Document':
      properties:
        text:
          type: string
    """

    And the event RootWorkspaceWasCreated was published with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
      | initiatingUserIdentifier   | "system-user"        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                         |
      | contentStreamIdentifier     | "cs-identifier"               |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
      | coveredDimensionSpacePoints | [{}]                          |
      | initiatingUserIdentifier    | "system-user"                 |
      | nodeAggregateClassification | "root"                        |
    And the graph projection is fully up to date
    # Node /name1 (has text value set)
    When the intermediary command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "na-name1"                                |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | nodeName                      | "name1"                                   |
      | originDimensionSpacePoint     | {}                                        |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | initialPropertyValues         | {"text": "Original name1"}                |
    And the graph projection is fully up to date

    # Node /name2 (has text value empty)
    When the intermediary command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "na-name2"                                |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | nodeName                      | "name2"                                   |
      | originDimensionSpacePoint     | {}                                        |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | initialPropertyValues         | {"text": ""}                              |
    And the graph projection is fully up to date

      # no node name (has text value not set)
    When the intermediary command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "na-null-value"                           |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | initialPropertyValues         | {"text": null}                            |
    And the graph projection is fully up to date

    # no node name (has text value not set)
    When the intermediary command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "na-no-text"                              |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | initialPropertyValues         | {}                                        |
    And the graph projection is fully up to date


  Scenario: PropertyNotEmpty
    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """
    migration:
      -
        filters:
          -
            type: 'PropertyNotEmpty'
            settings:
              propertyName: 'text'
        transformations:
          -
            type: 'ChangePropertyValue'
            settings:
              property: 'text'
              newSerializedValue: 'fixed value'
    """
    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "na-name1" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value          | Type   |
      | text | Original name1 | string |
    Then I expect a node identified by aggregate identifier "na-name2" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value | Type   |
      | text |       | string |
    Then I expect a node identified by aggregate identifier "na-null-value" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value | Type   |
      | text |       | string |

    Then I expect a node identified by aggregate identifier "na-no-text" to exist in the subgraph
    And I expect this node to not have the property "text"

    # we filter based on the node name
    When I am in content stream "migration-cs" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "na-name1" to exist in the subgraph
    # only changed here
    And I expect this node to have the properties:
      | Key  | Value       | Type   |
      | text | fixed value | string |
    Then I expect a node identified by aggregate identifier "na-name2" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value | Type   |
      | text |       | string |
    Then I expect a node identified by aggregate identifier "na-null-value" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value | Type   |
      | text |       | string |

    Then I expect a node identified by aggregate identifier "na-no-text" to exist in the subgraph
    And I expect this node to not have the property "text"

