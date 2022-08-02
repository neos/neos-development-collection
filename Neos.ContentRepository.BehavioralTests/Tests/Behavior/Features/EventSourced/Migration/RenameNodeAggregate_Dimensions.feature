@contentrepository @adapters=DoctrineDBAL
Feature: Rename Node Aggregate

  Background:
    Given I have the following content dimensions:
      | Identifier | Values          | Generalizations      |
      | language   | mul, de, en, ch | ch->de->mul, en->mul |

    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:Document': true
          'Neos.ContentRepository.Testing:OtherDocument': true

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
      | Key                         | Value                                                                      |
      | contentStreamIdentifier     | "cs-identifier"                                                            |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"                                                   |
      | nodeTypeName                | "Neos.ContentRepository:Root"                                              |
      | coveredDimensionSpacePoints | [{"language":"mul"},{"language":"de"},{"language":"en"},{"language":"ch"}] |
      | initiatingUserIdentifier    | "system-user"                                                              |
      | nodeAggregateClassification | "root"                                                                     |
    And the graph projection is fully up to date
    # Node /document (in "de")
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | nodeName                      | "foo"                                     |
      | originDimensionSpacePoint     | {"language": "de"}                        |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | initialPropertyValues         | {"text": "Original text"}                 |
    And the graph projection is fully up to date

    # Node /document (in "en")
    When the command CreateNodeVariant is executed with payload:
      | Key                      | Value                    |
      | contentStreamIdentifier  | "cs-identifier"          |
      | nodeAggregateIdentifier  | "sir-david-nodenborough" |
      | sourceOrigin             | {"language":"de"}        |
      | targetOrigin             | {"language":"en"}        |
      | initiatingUserIdentifier | "system-user"            |
    And the graph projection is fully up to date


  Scenario: Rename Node Aggregate
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
            type: 'RenameNodeAggregate'
            settings:
              newNodeName: 'other'
    """


    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect the node "sir-david-nodenborough" to have the name "foo"

    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect the node "sir-david-nodenborough" to have the name "foo"

    # the node was changed inside the new content stream, across all dimensions
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect the node "sir-david-nodenborough" to have the name "other"

    When I am in content stream "migration-cs" and dimension space point {"language": "ch"}
    Then I expect the node "sir-david-nodenborough" to have the name "other"

    When I am in content stream "migration-cs" and dimension space point {"language": "en"}
    Then I expect the node "sir-david-nodenborough" to have the name "other"


  Scenario: Rename Node Aggregate will fail when restricted to a single Dimension
    When I run the following node migration for workspace "live", creating content streams "migration-cs" and exceptions are caught:
    """
    migration:
      -
        filters:
          -
            type: 'NodeType'
            settings:
              nodeType: 'Neos.ContentRepository.Testing:Document'
          -
            type: 'DimensionSpacePoints'
            settings:
              points:
                - {"language": "de"}
        transformations:
          -
            type: 'RenameNodeAggregate'
            settings:
              newNodeName: 'other'
    """
    Then the last command should have thrown an exception of type "InvalidMigrationFilterSpecified"
