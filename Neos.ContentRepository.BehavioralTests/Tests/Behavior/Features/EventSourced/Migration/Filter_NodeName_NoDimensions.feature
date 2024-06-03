@contentrepository @adapters=DoctrineDBAL
Feature: Filter - Node Name

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
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    # Node /name1
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "na-name1"                                |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | nodeName                  | "name1"                                   |
      | originDimensionSpacePoint | {}                                        |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |
      | initialPropertyValues     | {"text": "Original name1"}                |

    # Node /name2
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "na-name2"                                |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | nodeName                  | "name2"                                   |
      | originDimensionSpacePoint | {}                                        |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |
      | initialPropertyValues     | {"text": "Original name2"}                |

    # no node name
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "na-without-name"                         |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {}                                        |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |
      | initialPropertyValues     | {"text": "no node name"}                  |


  Scenario: Fixed newValue
    When I run the following node migration for workspace "live", creating target workspace "migration-workspace", without publishing on success:
    """yaml
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
    When I am in workspace "live" and dimension space point {}
    Then I get the node with id "na-name1"
    And I expect this node to have the following properties:
      | Key  | Value            |
      | text | "Original name1" |
    Then I get the node with id "na-name2"
    And I expect this node to have the following properties:
      | Key  | Value            |
      | text | "Original name2" |
    Then I get the node with id "na-without-name"
    And I expect this node to have the following properties:
      | Key  | Value          |
      | text | "no node name" |

    # we filter based on the node name
    When I am in workspace "migration-workspace" and dimension space point {}
    Then I get the node with id "na-name1"
    # only changed here
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "fixed value" |
    Then I get the node with id "na-name2"
    And I expect this node to have the following properties:
      | Key  | Value            |
      | text | "Original name2" |
    Then I get the node with id "na-without-name"
    And I expect this node to have the following properties:
      | Key  | Value          |
      | text | "no node name" |
