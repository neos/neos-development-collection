@contentrepository @adapters=DoctrineDBAL
Feature: Filter - Property not empty

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:Document': true
    'Neos.ContentRepository.Testing:Document':
      properties:
        text:
          type: string
    """

    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                         |
      | nodeAggregateId             | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
    # Node /name1 (has text value set)
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "na-name1"                                |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | nodeName                  | "name1"                                   |
      | originDimensionSpacePoint | {}                                        |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |
      | initialPropertyValues     | {"text": "Original name1"}                |

    # Node /name2 (has text value empty)
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "na-name2"                                |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | nodeName                  | "name2"                                   |
      | originDimensionSpacePoint | {}                                        |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |
      | initialPropertyValues     | {"text": ""}                              |

      # no node name (has text value not set)
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "na-null-value"                           |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {}                                        |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |
      | initialPropertyValues     | {"text": null}                            |

    # no node name (has text value not set, and null will be ignored as unset)
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "na-no-text"                              |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {}                                        |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |
      | initialPropertyValues     | {}                                        |


  Scenario: PropertyNotEmpty
    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """yaml
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
    When I am in workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "na-name1" to lead to node cs-identifier;na-name1;{}
    And I expect this node to have the following properties:
      | Key  | Value            |
      | text | "Original name1" |
    Then I expect node aggregate identifier "na-name2" to lead to node cs-identifier;na-name2;{}
    And I expect this node to have the following properties:
      | Key  | Value |
      | text | ""    |
    Then I expect node aggregate identifier "na-null-value" to lead to node cs-identifier;na-null-value;{}
    And I expect this node to have no properties

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
      | Key  | Value |
      | text | ""    |
    Then I expect node aggregate identifier "na-null-value" to lead to node migration-cs;na-null-value;{}
    And I expect this node to have no properties

    Then I expect node aggregate identifier "na-no-text" to lead to node migration-cs;na-no-text;{}
    And I expect this node to not have the property "text"

