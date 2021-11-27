@fixtures
Feature: Remove Nodes

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values          | Generalizations      |
      | language   | mul     | mul, de, en, ch | ch->de->mul, en->mul |

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
      | originDimensionSpacePoint     | {"language": "de"}                        |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | initialPropertyValues         | {"text": "Original text"}                 |
    And the graph projection is fully up to date

    # Node /document (in "en")
    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value                    |
      | contentStreamIdentifier | "cs-identifier"          |
      | nodeAggregateIdentifier | "sir-david-nodenborough" |
      | sourceOrigin            | {"language":"de"}        |
      | targetOrigin            | {"language":"en"}        |
    And the graph projection is fully up to date


  Scenario: Remove nodes in a given dimension space point removes the node with all shine-throughs
    When I run the following node migration for workspace "live", creating content streams "migration-cs":
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
            type: 'RemoveNode'
    """

    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "ch"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "en"}

    # the node was removed inside the new content stream, but only in DE and CH (shine-through)
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "en"}

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors


  Scenario: Remove nodes in a given dimension space point removes the node without shine-throughs with strategy "onlyGivenVariant"
    When I run the following node migration for workspace "live", creating content streams "migration-cs":
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
            type: 'RemoveNode'
            settings:
              strategy: 'onlyGivenVariant'
    """

    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "ch"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "en"}

    # the node was removed inside the new content stream, but only in DE
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "ch"}

    When I am in content stream "migration-cs" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "en"}

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors


  Scenario: allVariants is not supported in RemoveNode, as this would violate the filter configuration potentially
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
            type: 'RemoveNode'
            settings:
              strategy: 'allVariants'
    """
    Then the last command should have thrown an exception of type "InvalidMigrationConfiguration"


  Scenario: Remove nodes in a shine-through dimension space point (CH)
    When I run the following node migration for workspace "live", creating content streams "migration-cs":
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
            type: 'RemoveNode'
            settings:
              overriddenDimensionSpacePoint: {"language": "ch"}
    """

    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "ch"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "en"}

    # the node was removed inside the new content stream, but only in CH
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "migration-cs" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "en"}

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors


  Scenario: Remove nodes in a shine-through dimension space point (CH)
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
            type: 'RemoveNode'
            settings:
              overriddenDimensionSpacePoint: {"language": "ch"}
    """

    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "ch

    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "en"}

    # the node was removed inside the new content stream, but only in CH
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "migration-cs" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "en"}

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors



  Scenario: Remove nodes in a shine-through dimension space point (DE,CH)
    When I run the following node migration for workspace "live", creating content streams "migration-cs":
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
                - {"language": "ch"}
                - {"language": "en"}
        transformations:
          -
            type: 'RemoveNode'
    """

    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "ch"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "en"}

    # the node was removed inside the new content stream, but only in CH
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors

  Scenario: Remove nodes in a shine-through dimension space point (DE,CH) - variant 2
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
            type: 'RemoveNode'
    """

    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "ch"}

    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "en"}

    # the node was removed inside the new content stream, but only in CH
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I am in content stream "migration-cs" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors
