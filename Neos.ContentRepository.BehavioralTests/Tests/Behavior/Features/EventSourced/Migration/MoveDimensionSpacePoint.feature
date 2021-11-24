@fixtures
Feature: Move dimension space point

  basically "renames" a dimension space point; needed if:
  - the dimension value should be changed: {language: de} -> {language: de_DE}
  - there were no dimensions beforehand, and now there are: {} -> {language: de}
  - ... or the opposite: {language: de} -> {}
  - new dimensions are introduced; so the existing DimensionSpacePoints need an additional value.

  !! Constraint: the Target Dimension Space should be empty.

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values          | Generalizations      |
      | language   | mul     | mul, de, en, ch | ch->de->mul, en->mul |

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

    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:OtherDocument': []
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
      | initiatingUserIdentifier   | "system-user"        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                    |
      | contentStreamIdentifier     | "cs-identifier"                                          |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"                                 |
      | nodeTypeName                | "Neos.ContentRepository:Root"                            |
      | coveredDimensionSpacePoints | [{"language":"mul"},{"language":"ch"},{"language":"de"}] |
      | initiatingUserIdentifier    | "system-user"                                            |
      | nodeAggregateClassification | "root"                                                   |
    And the graph projection is fully up to date
    # Node /document
    When the intermediary command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language": "de"}                        |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
    And the graph projection is fully up to date


  Scenario: Success Case - simple
    # we change the dimension configuration
    When I have the following content dimensions:
      | Identifier | Default | Values     | Generalizations |
      | language   | mul     | mul, de_DE | de_DE->mul      |

    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """
    migration:
      -
        transformations:
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: { language: 'de' }
              to: { language: 'de_DE' }
    """
    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and Dimension Space Point {"language": "de"}
    Then I expect a node identified by aggregate identifier "sir-david-nodenborough" to exist in the subgraph
    And I expect this node to be of type "Neos.ContentRepository.Testing:Document"


    # we find the node underneath the new DimensionSpacePoint, but not underneath the old.
    When I am in content stream "migration-cs" and Dimension Space Point {"language": "de"}
    Then I expect a node identified by aggregate identifier "sir-david-nodenborough" not to exist in the subgraph
    When I am in content stream "migration-cs" and Dimension Space Point {"language": "de_DE"}
    Then I expect a node identified by aggregate identifier "sir-david-nodenborough" to exist in the subgraph
    And I expect this node to be of type "Neos.ContentRepository.Testing:Document"

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors


  Scenario: Success Case - disabled nodes stay disabled

    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | contentStreamIdentifier      | "cs-identifier"          |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"language": "de"}       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the graph projection is fully up to date

    # ensure the node is disabled
    When I am in content stream "cs-identifier" and Dimension Space Point {"language": "de"}
    Then I expect a node identified by aggregate identifier "sir-david-nodenborough" not to exist in the subgraph
    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect a node identified by aggregate identifier "sir-david-nodenborough" to exist in the subgraph
    When VisibilityConstraints are set to "frontend"

    # we change the dimension configuration
    When I have the following content dimensions:
      | Identifier | Default | Values     | Generalizations |
      | language   | mul     | mul, de_DE | de_DE->mul      |

    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """
    migration:
      -
        transformations:
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: { language: 'de' }
              to: { language: 'de_DE' }
    """

    # the original content stream has not been touched
    When I am in content stream "cs-identifier" and Dimension Space Point {"language": "de"}
    Then I expect a node identified by aggregate identifier "sir-david-nodenborough" not to exist in the subgraph
    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect a node identified by aggregate identifier "sir-david-nodenborough" to exist in the subgraph
    When VisibilityConstraints are set to "frontend"

    # The visibility edges were modified
    When I am in content stream "migration-cs" and Dimension Space Point {"language": "de_DE"}
    Then I expect a node identified by aggregate identifier "sir-david-nodenborough" not to exist in the subgraph
    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect a node identified by aggregate identifier "sir-david-nodenborough" to exist in the subgraph
    When VisibilityConstraints are set to "frontend"

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors

  Scenario: Error case - there's already an edge in the target dimension
    # we change the dimension configuration
    When I have the following content dimensions:
      | Identifier | Default | Values  | Generalizations |
      | language   | mul     | mul, ch | ch->mul         |

    When I run the following node migration for workspace "live", creating content streams "migration-cs" and exceptions are caught:
    """
    migration:
      -
        transformations:
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: { language: 'de' }
              to: { language: 'ch' }
    """
    Then the last command should have thrown an exception of type "DimensionSpacePointAlreadyExists"

  Scenario: Error case - the target dimension is not configured
    When I run the following node migration for workspace "live", creating content streams "migration-cs" and exceptions are caught:
    """
    migration:
      -
        transformations:
          -
            type: 'MoveDimensionSpacePoint'
            settings:
              from: { language: 'de' }
              to: { language: 'notexisting' }
    """
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

