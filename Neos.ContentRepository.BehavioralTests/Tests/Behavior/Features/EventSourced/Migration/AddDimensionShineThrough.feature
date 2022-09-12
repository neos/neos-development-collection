@contentrepository @adapters=DoctrineDBAL
Feature: Add Dimension Specialization

  This is needed if "de" exists, and you want to create a "de_CH" specialization:
  - there, you want to create EDGES for de_CH, without materializing NODES (so that the shine-through works as expected)

  !! NOTE: We can NOT trigger SPECIALIZATION of all nodes using the existing events; because this materializes the nodes.

  !! Constraint: the Target Dimension Space should be empty.

  Background:
    Given I have the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | mul, de, en | de->mul         |

    ########################
    # SETUP
    ########################
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:Document': true
          'Neos.ContentRepository.Testing:OtherDocument': true

    'Neos.ContentRepository.Testing:Document':
      properties:
        text:
          type: string
    'Neos.ContentRepository.Testing:OtherDocument': []
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
      | Key                         | Value                                                    |
      | contentStreamId     | "cs-identifier"                                          |
      | nodeAggregateId     | "lady-eleonode-rootford"                                 |
      | nodeTypeName                | "Neos.ContentRepository:Root"                            |
      | coveredDimensionSpacePoints | [{"language":"mul"},{"language":"de"},{"language":"en"}] |
      | initiatingUserId    | "system-user"                                            |
      | nodeAggregateClassification | "root"                                                   |
    And the graph projection is fully up to date
    # Node /document
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language": "de"}                        |
      | initiatingUserId      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateId | "lady-eleonode-rootford"                  |
      | initialPropertyValues         | {"text": "hello" }                        |
    And the graph projection is fully up to date


  Scenario: Success Case - simple
    # we change the dimension configuration
    When I have the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | mul, de, ch | ch->de->mul     |

    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """
    migration:
      -
        transformations:
          -
            type: 'AddDimensionShineThrough'
            settings:
              from: { language: 'de' }
              to: { language: 'ch' }
    """
    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:Document"
    And I expect this node to have the following properties:
      | Key  | Value   |
      | text | "hello" |
    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node


    # now, we find the node underneath both DimensionSpacePoints
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}
    And I expect this node to have the following properties:
      | Key  | Value   |
      | text | "hello" |
    When I am in content stream "migration-cs" and dimension space point {"language": "ch"}
    # shine through added
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:Document"
    And I expect this node to have the following properties:
      | Key  | Value   |
      | text | "hello" |

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors


    # finally, we MODIFY the node and ensure that the modification is visible in both DSPs (as otherwise the shine through would not have worked
    # as expected)
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | contentStreamId   | "migration-cs"               |
      | nodeAggregateId   | "sir-david-nodenborough"     |
      | originDimensionSpacePoint | {"language": "de"}           |
      | propertyValues            | {"text": "changed"}          |
      | initiatingUserId  | "initiating-user-identifier" |
    And the graph projection is fully up to date
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}
    And I expect this node to have the following properties:
      | Key  | Value     |
      | text | "changed" |
    When I am in content stream "migration-cs" and dimension space point {"language": "ch"}
    # ch shines through to the DE node
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}
    And I expect this node to have the following properties:
      | Key  | Value     |
      | text | "changed" |

    # the original content stream was untouched
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}
    And I expect this node to have the following properties:
      | Key  | Value   |
      | text | "hello" |
    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors

  Scenario: Success Case - disabled nodes stay disabled

    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                                  |
      | contentStreamId      | "cs-identifier"                        |
      | nodeAggregateId      | "sir-david-nodenborough"               |
      | coveredDimensionSpacePoint   | {"language": "de"}                     |
      | nodeVariantSelectionStrategy | "allVariants"                          |
      | initiatingUserId     | "00000000-0000-0000-0000-000000000000" |
    And the graph projection is fully up to date

    # ensure the node is disabled
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}
    When VisibilityConstraints are set to "frontend"

    # we change the dimension configuration
    When I have the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | mul, de, ch | ch->de->mul     |

    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """
    migration:
      -
        transformations:
          -
            type: 'AddDimensionShineThrough'
            settings:
              from: { language: 'de' }
              to: { language: 'ch' }
    """

    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}
    When VisibilityConstraints are set to "frontend"

    # The visibility edges were modified
    When I am in content stream "migration-cs" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de"}
    When VisibilityConstraints are set to "frontend"

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors


  Scenario: Error case - there's already an edge in the target dimension
    # we change the dimension configuration
    When I have the following content dimensions:
      | Identifier | Values          | Generalizations |
      | language   | mul, de, ch, en | ch->de->mul     |

    # we create a node in CH
    When the command CreateNodeVariant is executed with payload:
      | Key                      | Value                    |
      | contentStreamId  | "cs-identifier"          |
      | nodeAggregateId  | "sir-david-nodenborough" |
      | sourceOrigin             | {"language":"de"}        |
      | targetOrigin             | {"language":"en"}        |
      | initiatingUserId | "foo"                    |


    When I run the following node migration for workspace "live", creating content streams "migration-cs" and exceptions are caught:
    """
    migration:
      -
        transformations:
          -
            type: 'AddDimensionShineThrough'
            settings:
              from: { language: 'de' }
              to: { language: 'en' }
    """
    Then the last command should have thrown an exception of type "DimensionSpacePointAlreadyExists"

  Scenario: Error case - the target dimension is not configured
    When I run the following node migration for workspace "live", creating content streams "migration-cs" and exceptions are caught:
    """
    migration:
      -
        transformations:
          -
            type: 'AddDimensionShineThrough'
            settings:
              from: { language: 'de' }
              to: { language: 'notexisting' }
    """
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"


  Scenario: Error case - the target dimension is not a specialization of the source dimension (1)
    Given I have the following content dimensions:
      | Identifier | Values       | Generalizations |
      | language   | mul, de, foo | de->mul         |

    When I run the following node migration for workspace "live", creating content streams "migration-cs" and exceptions are caught:
    """
    migration:
      -
        transformations:
          -
            type: 'AddDimensionShineThrough'
            settings:
              from: { language: 'de' }
              to: { language: 'foo' }
    """
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNoSpecialization"


  Scenario: Error case - the target dimension is not a specialization of the source dimension (2)
    Given I have the following content dimensions:
      | Identifier | Values       | Generalizations   |
      | language   | mul, de, foo | de->mul, foo->mul |

    When I run the following node migration for workspace "live", creating content streams "migration-cs" and exceptions are caught:
    """
    migration:
      -
        transformations:
          -
            type: 'AddDimensionShineThrough'
            settings:
              from: { language: 'de' }
              to: { language: 'foo' }
    """
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNoSpecialization"


