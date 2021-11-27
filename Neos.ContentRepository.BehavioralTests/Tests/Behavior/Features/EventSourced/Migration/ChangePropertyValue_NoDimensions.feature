@fixtures
Feature: Change Property

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
            type: 'ChangePropertyValue'
            settings:
              property: 'text'
              newSerializedValue: 'fixed value'
    """
    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key  | Value         | Type   |
      | text | Original text | string |

    # the node type was changed inside the new content stream
    When I am in content stream "migration-cs" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key  | Value       | Type   |
      | text | fixed value | string |

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
            type: 'ChangePropertyValue'
            settings:
              property: 'notExisting'
              newSerializedValue: 'fixed value'
    """
    # we did not change anything because notExisting does not exist
    When I am in content stream "migration-cs" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key  | Value         | Type   |
      | text | Original text | string |

  Scenario: replacement using default currentValuePlaceholder
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
            type: 'ChangePropertyValue'
            settings:
              property: 'text'
              newSerializedValue: 'bla {current}'
    """
    When I am in content stream "migration-cs" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key  | Value             | Type   |
      | text | bla Original text | string |

  Scenario: replacement using alternative currentValuePlaceholder
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
            type: 'ChangePropertyValue'
            settings:
              property: 'text'
              currentValuePlaceholder: '{otherPlaceholder}'
              newSerializedValue: 'bla {otherPlaceholder}'
    """
    When I am in content stream "migration-cs" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key  | Value             | Type   |
      | text | bla Original text | string |

  Scenario: using search/replace
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
            type: 'ChangePropertyValue'
            settings:
              property: 'text'
              search: 'Original'
              replace: 'alternative'
    """
    When I am in content stream "migration-cs" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key  | Value            | Type   |
      | text | alternative text | string |

  Scenario: using search/replace including placeholder (all options)
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
            type: 'ChangePropertyValue'
            settings:
              property: 'text'
              newSerializedValue: 'bla {current}'
              search: 'Original'
              replace: 'alternative'
    """
    When I am in content stream "migration-cs" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{}
    And I expect this node to have the following properties:
      | Key  | Value                | Type   |
      | text | bla alternative text | string |
