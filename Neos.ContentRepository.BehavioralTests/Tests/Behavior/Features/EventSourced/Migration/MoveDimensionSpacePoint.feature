@contentrepository @adapters=DoctrineDBAL
Feature: Move dimension space point

  basically "renames" a dimension space point; needed if:
  - the dimension value should be changed: {language: de} -> {language: de_DE}
  - there were no dimensions beforehand, and now there are: {} -> {language: de}
  - ... or the opposite: {language: de} -> {}
  - new dimensions are introduced; so the existing DimensionSpacePoints need an additional value.

  !! Constraint: the Target Dimension Space should be empty.

  Background:
    ########################
    # SETUP
    ########################
    Given using the following content dimensions:
      | Identifier | Values          | Generalizations      |
      | language   | mul, de, en, ch | ch->de->mul, en->mul |
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:Document': true
          'Neos.ContentRepository.Testing:OtherDocument': true

    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:OtherDocument': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | contentStreamId | "cs-identifier"               |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    # Node /document
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | contentStreamId           | "cs-identifier"                           |
      | nodeAggregateId           | "sir-david-nodenborough"                  |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {"language": "de"}                        |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |
    And the graph projection is fully up to date


  Scenario: Success Case - simple
    # we change the dimension configuration
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values     | Generalizations |
      | language   | mul, de_DE | de_DE->mul      |

    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """yaml
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
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:Document"


    # we find the node underneath the new DimensionSpacePoint, but not underneath the old.
    When I am in content stream "migration-cs" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    When I am in content stream "migration-cs" and dimension space point {"language": "de_DE"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de_DE"}
    And I expect this node to be of type "Neos.ContentRepository.Testing:Document"

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors


  Scenario: Success Case - disabled nodes stay disabled

    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | contentStreamId              | "cs-identifier"          |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"language": "de"}       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the graph projection is fully up to date

    # ensure the node is disabled
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}
    When VisibilityConstraints are set to "frontend"

    # we change the dimension configuration
    When I change the content dimensions in content repository "default" to:
      | Identifier | Values     | Generalizations |
      | language   | mul, de_DE | de_DE->mul      |

    When I run the following node migration for workspace "live", creating content streams "migration-cs":
    """yaml
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
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language": "de"}
    When VisibilityConstraints are set to "frontend"

    # The visibility edges were modified
    When I am in content stream "migration-cs" and dimension space point {"language": "de_DE"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to no node
    When VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node migration-cs;sir-david-nodenborough;{"language": "de_DE"}
    When VisibilityConstraints are set to "frontend"

    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors

  Scenario: Error case - there's already an edge in the target dimension
    # we change the dimension configuration
    When I change the content dimensions in content repository "default" to:
      | Identifier | Values  | Generalizations |
      | language   | mul, ch | ch->mul         |

    When I run the following node migration for workspace "live", creating content streams "migration-cs" and exceptions are caught:
    """yaml
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
    """yaml
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

