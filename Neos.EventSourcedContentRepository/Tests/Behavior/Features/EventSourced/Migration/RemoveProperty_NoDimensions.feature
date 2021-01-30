@fixtures
Feature: Remove Property

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
    # Node /document
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | initialPropertyValues         | {"text": "Original text"}                 |
    And the graph projection is fully up to date


  Scenario: Fixed newValue
    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """
    migration:
      -
        filters:
          -
            type: 'NodeType'
            settings:
              nodeType: 'Neos.ContentRepository.Testing:Document'
        transformations:
          -
            type: 'RemoveProperty'
            settings:
              property: 'text'
    """
    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "sir-david-nodenborough" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value         | Type   |
      | text | Original text | string |

    # the node type was changed inside the new content stream
    When I am in content stream "migration-cs" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "sir-david-nodenborough" to exist in the subgraph
    And I expect this node to have no properties

  Scenario: Ignoring transformation if property does not exist on node
    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """
    migration:
      -
        filters:
          -
            type: 'NodeType'
            settings:
              nodeType: 'Neos.ContentRepository.Testing:Document'
        transformations:
          -
            type: 'RemoveProperty'
            settings:
              property: 'notExisting'
    """
    # we did not change anything because notExisting does not exist
    When I am in content stream "migration-cs" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "sir-david-nodenborough" to exist in the subgraph
    And I expect this node to have the properties:
      | Key  | Value         | Type   |
      | text | Original text | string |
