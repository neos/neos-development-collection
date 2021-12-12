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
    When the command CreateNodeAggregateWithNode is executed with payload:
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
    When the command CreateNodeAggregateWithNode is executed with payload:
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
    When the command CreateNodeAggregateWithNode is executed with payload:
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
              newSerializedValue: 'fixed value'
    """
    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {}
    Then I expect node aggregate identifier "na-name1" to lead to node cs-identifier;na-name1;{}
    And I expect this node to have the following properties:
      | Key  | Value            |
      | text | "Original name1" |
    Then I expect node aggregate identifier "na-name2" to lead to node cs-identifier;na-name2;{}
    And I expect this node to have the following properties:
      | Key  | Value            |
      | text | "Original name2" |
    Then I expect node aggregate identifier "na-without-name" to lead to node cs-identifier;na-without-name;{}
    And I expect this node to have the following properties:
      | Key  | Value          |
      | text | "no node name" |

    # we filter based on the node name
    When I am in content stream "migration-cs" and dimension space point {}
    Then I expect node aggregate identifier "na-name1" to lead to node migration-cs;na-name1;{}
    # only changed here
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "fixed value" |
    Then I expect node aggregate identifier "na-name2" to lead to node migration-cs;na-name2;{}
    And I expect this node to have the following properties:
      | Key  | Value            |
      | text | "Original name2" |
    Then I expect node aggregate identifier "na-without-name" to lead to node migration-cs;na-without-name;{}
    And I expect this node to have the following properties:
      | Key  | Value          |
      | text | "no node name" |
