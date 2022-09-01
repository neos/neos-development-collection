@contentrepository @adapters=DoctrineDBAL
Feature: Filter - Property Value

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

    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamId | "cs-identifier"      |
      | initiatingUserId   | "system-user"        |
    And the graph projection is fully up to date
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                         |
      | contentStreamId     | "cs-identifier"               |
      | nodeAggregateId     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
      | coveredDimensionSpacePoints | [{}]                          |
      | initiatingUserId    | "system-user"                 |
      | nodeAggregateClassification | "root"                        |
    And the graph projection is fully up to date
    # Node /name1 (has text value set)
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "na-name1"                                |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | nodeName                      | "name1"                                   |
      | originDimensionSpacePoint     | {}                                        |
      | initiatingUserId      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateId | "lady-eleonode-rootford"                  |
      | initialPropertyValues         | {"text": "Original name1"}                |
    And the graph projection is fully up to date

    # Node /name2 (has text value2)
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "na-name2"                                |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | nodeName                      | "name2"                                   |
      | originDimensionSpacePoint     | {}                                        |
      | initiatingUserId      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateId | "lady-eleonode-rootford"                  |
      | initialPropertyValues         | {"text": "value2"}                        |
    And the graph projection is fully up to date

      # no node name (has text value not set)
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "na-null-value"                           |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | initiatingUserId      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateId | "lady-eleonode-rootford"                  |
      | initialPropertyValues         | {"text": null}                            |
    And the graph projection is fully up to date

    # no node name (has text value not set)
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "na-no-text"                              |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | initiatingUserId      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateId | "lady-eleonode-rootford"                  |
      | initialPropertyValues         | {}                                        |
    And the graph projection is fully up to date


  Scenario: PropertyValue
    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """
    migration:
      -
        filters:
          -
            type: 'PropertyValue'
            settings:
              propertyName: 'text'
              serializedValue: 'Original name1'
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
      | Key  | Value    |
      | text | "value2" |
    Then I expect node aggregate identifier "na-null-value" to lead to node cs-identifier;na-null-value;{}
    And I expect this node to have the following properties:
      | Key  | Value |
      | text | ""    |

    Then I expect node aggregate identifier "na-no-text" to lead to node cs-identifier;na-no-text;{}
    And I expect this node to not have the property "text"

    # we filter based on the node name
    When I am in content stream "migration-cs" and dimension space point {}
    Then I expect node aggregate identifier "na-name1" to lead to node migration-cs;na-name1;{}
    # only changed here
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "fixed value" |
    Then I expect node aggregate identifier "na-name2" to lead to node migration-cs;na-name2;{}
    And I expect this node to have the following properties:
      | Key  | Value    |
      | text | "value2" |
    Then I expect node aggregate identifier "na-null-value" to lead to node migration-cs;na-null-value;{}
    And I expect this node to have the following properties:
      | Key  | Value |
      | text | ""    |

    Then I expect node aggregate identifier "na-no-text" to lead to node migration-cs;na-no-text;{}
    And I expect this node to not have the property "text"

