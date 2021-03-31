@fixtures
Feature: Filter - Node Name

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
    # Node /name1
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

    # Node /name2
    When the intermediary command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "na-name2"                                |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | nodeName                      | "name2"                                   |
      | originDimensionSpacePoint     | {}                                        |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | initialPropertyValues         | {"text": "Original name2"}                |
    And the graph projection is fully up to date

    # no node name
    When the intermediary command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "na-without-name"                         |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | initialPropertyValues         | {"text": "no node name"}                  |
    And the graph projection is fully up to date


  Scenario: Fixed newValue
    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """
    migration:
      -
        filters:
          -
            type: 'NodeName'
            settings:
              nodeName: 'name1'
        transformations:
          -
            type: 'ChangePropertyValue'
            settings:
              property: 'text'
              newValue: 'fixed value'
    """
    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "na-name1" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value          | Type   |
      | text | Original name1 | string |
    Then I expect a node identified by aggregate identifier "na-name2" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value          | Type   |
      | text | Original name2 | string |
    Then I expect a node identified by aggregate identifier "na-without-name" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value        | Type   |
      | text | no node name | string |

    # we filter based on the node name
    When I am in content stream "migration-cs" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "na-name1" to exist in the subgraph
    # only changed here
    And I expect this node to have the properties:
      | Key  | Value       | Type   |
      | text | fixed value | string |
    Then I expect a node identified by aggregate identifier "na-name2" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value          | Type   |
      | text | Original name2 | string |
    Then I expect a node identified by aggregate identifier "na-without-name" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value        | Type   |
      | text | no node name | string |
