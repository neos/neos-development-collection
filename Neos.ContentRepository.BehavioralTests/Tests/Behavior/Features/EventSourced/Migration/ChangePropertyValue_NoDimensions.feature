@contentrepository @adapters=DoctrineDBAL
Feature: Change Property

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
    # Node /document
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "sir-david-nodenborough"                  |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {}                                        |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |
      | initialPropertyValues     | {"text": "Original text"}                 |


  Scenario: Fixed newValue
    When I run the following node migration for workspace "live", creating target workspace "migration-workspace", without publishing on success:
    """yaml
    migration:
      -
        filters:
          -
            type: 'NodeType'
            settings:
              nodeType: 'Neos.ContentRepository.Testing:Document'
        transformations:
          -
            type: 'ChangePropertyValue'
            settings:
              property: 'text'
              newSerializedValue: 'fixed value'
    """
    # the original content stream has not been touched
    When I am in workspace "live" and dimension space point {}
    Then I get the node with id "sir-david-nodenborough"
    And I expect this node to have the following properties:
      | Key  | Value           |
      | text | "Original text" |

    # the node type was changed inside the new content stream
    When I am in workspace "migration-workspace" and dimension space point {}
    Then I get the node with id "sir-david-nodenborough"
    And I expect this node to have the following properties:
      | Key  | Value         |
      | text | "fixed value" |

  Scenario: Ignoring transformation if property does not exist on node
    When I run the following node migration for workspace "live", creating target workspace "migration-workspace", without publishing on success:
    """yaml
    migration:
      -
        filters:
          -
            type: 'NodeType'
            settings:
              nodeType: 'Neos.ContentRepository.Testing:Document'
        transformations:
          -
            type: 'ChangePropertyValue'
            settings:
              property: 'notExisting'
              newSerializedValue: 'fixed value'
    """
    # we did not change anything because notExisting does not exist
    When I am in workspace "migration-workspace" and dimension space point {}
    Then I get the node with id "sir-david-nodenborough"
    And I expect this node to have the following properties:
      | Key  | Value           |
      | text | "Original text" |

  Scenario: replacement using default currentValuePlaceholder
    When I run the following node migration for workspace "live", creating target workspace "migration-workspace", without publishing on success:
    """yaml
    migration:
      -
        filters:
          -
            type: 'NodeType'
            settings:
              nodeType: 'Neos.ContentRepository.Testing:Document'
        transformations:
          -
            type: 'ChangePropertyValue'
            settings:
              property: 'text'
              newSerializedValue: 'bla {current}'
    """
    When I am in workspace "migration-workspace" and dimension space point {}
    Then I get the node with id "sir-david-nodenborough"
    And I expect this node to have the following properties:
      | Key  | Value               |
      | text | "bla Original text" |

  Scenario: replacement using alternative currentValuePlaceholder
    When I run the following node migration for workspace "live", creating target workspace "migration-workspace", without publishing on success:
    """yaml
    migration:
      -
        filters:
          -
            type: 'NodeType'
            settings:
              nodeType: 'Neos.ContentRepository.Testing:Document'
        transformations:
          -
            type: 'ChangePropertyValue'
            settings:
              property: 'text'
              currentValuePlaceholder: '{otherPlaceholder}'
              newSerializedValue: 'bla {otherPlaceholder}'
    """
    When I am in workspace "migration-workspace" and dimension space point {}
    Then I get the node with id "sir-david-nodenborough"
    And I expect this node to have the following properties:
      | Key  | Value               |
      | text | "bla Original text" |

  Scenario: using search/replace
    When I run the following node migration for workspace "live", creating target workspace "migration-workspace", without publishing on success:
    """yaml
    migration:
      -
        filters:
          -
            type: 'NodeType'
            settings:
              nodeType: 'Neos.ContentRepository.Testing:Document'
        transformations:
          -
            type: 'ChangePropertyValue'
            settings:
              property: 'text'
              search: 'Original'
              replace: 'alternative'
    """
    When I am in workspace "migration-workspace" and dimension space point {}
    Then I get the node with id "sir-david-nodenborough"
    And I expect this node to have the following properties:
      | Key  | Value              |
      | text | "alternative text" |

  Scenario: using search/replace including placeholder (all options)
    When I run the following node migration for workspace "live", creating target workspace "migration-workspace", without publishing on success:
    """yaml
    migration:
      -
        filters:
          -
            type: 'NodeType'
            settings:
              nodeType: 'Neos.ContentRepository.Testing:Document'
        transformations:
          -
            type: 'ChangePropertyValue'
            settings:
              property: 'text'
              newSerializedValue: 'bla {current}'
              search: 'Original'
              replace: 'alternative'
    """
    When I am in workspace "migration-workspace" and dimension space point {}
    Then I get the node with id "sir-david-nodenborough"
    And I expect this node to have the following properties:
      | Key  | Value                  |
      | text | "bla alternative text" |
