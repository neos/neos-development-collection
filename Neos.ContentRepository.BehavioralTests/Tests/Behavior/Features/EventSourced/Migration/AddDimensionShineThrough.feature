@contentrepository @adapters=DoctrineDBAL
Feature: Add Dimension Specialization

  This is needed if "de" exists, and you want to create a "de_CH" specialization:
  - there, you want to create EDGES for de_CH, without materializing NODES (so that the shine-through works as expected)

  !! NOTE: We can NOT trigger SPECIALIZATION of all nodes using the existing events; because this materializes the nodes.

  !! Constraint: the Target Dimension Space should be empty.

  Background:
    ########################
    # SETUP
    ########################
    Given using the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | mul, de, en | de->mul         |
    And using the following node types:
    """yaml
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
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
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
      | originDimensionSpacePoint | {"language": "de"}                        |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |
      | initialPropertyValues     | {"text": "hello" }                        |

  Scenario: Success Case - simple
    # we change the dimension configuration
    ########################
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values      | Generalizations |
      | language   | mul, de, ch | ch->de->mul     |
    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs", without publishing on success:
    """yaml
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
    When I am in workspace "live"
    And I am in dimension space point {"language": "de"}
    Then I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"de"} to exist in the content graph
    And I expect this node to be of type "Neos.ContentRepository.Testing:Document"
    And I expect this node to have the following properties:
      | Key  | Value   |
      | text | "hello" |
    When I am in dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node


    # now, we find the node underneath both DimensionSpacePoints
    When I am in workspace "migration-workspace" and dimension space point {"language": "de"}
    Then I expect a node identified by migration-cs;sir-david-nodenborough;{"language":"de"} to exist in the content graph
    And I expect this node to have the following properties:
      | Key  | Value   |
      | text | "hello" |
    When I am in workspace "migration-workspace" and dimension space point {"language": "ch"}
    # shine through added
    Then I expect a node identified by migration-cs;sir-david-nodenborough;{"language":"de"} to exist in the content graph
    And I expect this node to be of type "Neos.ContentRepository.Testing:Document"
    And I expect this node to have the following properties:
      | Key  | Value   |
      | text | "hello" |

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors


    # finally, we MODIFY the node and ensure that the modification is visible in both DSPs (as otherwise the shine through would not have worked
    # as expected)
    # migration-cs is the actual name of the temporary workspace
    And I am in workspace "migration-workspace"
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                    |
      | nodeAggregateId           | "sir-david-nodenborough" |
      | originDimensionSpacePoint | {"language": "de"}       |
      | propertyValues            | {"text": "changed"}      |
    When I am in workspace "migration-workspace" and dimension space point {"language": "de"}
    Then I expect a node identified by migration-cs;sir-david-nodenborough;{"language":"de"} to exist in the content graph
    And I expect this node to have the following properties:
      | Key  | Value     |
      | text | "changed" |
    When I am in workspace "migration-workspace" and dimension space point {"language": "ch"}
    # ch shines through to the DE node
    Then I expect a node identified by migration-cs;sir-david-nodenborough;{"language":"de"} to exist in the content graph
    And I expect this node to have the following properties:
      | Key  | Value     |
      | text | "changed" |

    # the original content stream was untouched
    When I am in workspace "live" and dimension space point {"language": "de"}
    Then I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"de"} to exist in the content graph
    And I expect this node to have the following properties:
      | Key  | Value   |
      | text | "hello" |
    When I am in workspace "live" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors

  Scenario: Success Case - disabled nodes stay disabled

    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"language": "de"}       |
      | nodeVariantSelectionStrategy | "allVariants"            |

    # ensure the node is disabled
    When I am in workspace "live" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"de"} to exist in the content graph
    When VisibilityConstraints are set to "frontend"

    # we change the dimension configuration
    When I change the content dimensions in content repository "default" to:
      | Identifier | Values      | Generalizations |
      | language   | mul, de, ch | ch->de->mul     |

    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs", without publishing on success:
    """yaml
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
    When I am in workspace "migration-workspace" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"de"} to exist in the content graph
    When VisibilityConstraints are set to "frontend"

    # The visibility edges were modified
    When I am in workspace "migration-workspace" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"de"} to exist in the content graph
    When VisibilityConstraints are set to "frontend"

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors


  Scenario: Error case - there's already an edge in the target dimension
    # we change the dimension configuration
    When I change the content dimensions in content repository "default" to:
      | Identifier | Values          | Generalizations |
      | language   | mul, de, ch, en | ch->de->mul     |

    # we create a node in CH
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"de"}        |
      | targetOrigin    | {"language":"en"}        |

    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs" and exceptions are caught:
    """yaml
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
    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs" and exceptions are caught:
    """yaml
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
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values       | Generalizations |
      | language   | mul, de, foo | de->mul         |

    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs" and exceptions are caught:
    """yaml
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
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values       | Generalizations   |
      | language   | mul, de, foo | de->mul, foo->mul |

    When I run the following node migration for workspace "live", creating target workspace "migration-workspace" on contentStreamId "migration-cs" and exceptions are caught:
    """yaml
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


