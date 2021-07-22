@fixtures
Feature: Disable a node

  As a user of the CR I want to disable a node aggregate and expect its descendants to also be disabled.

  These are the base test cases for the NodeAggregateCommandHandler to block invalid commands.

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    """
    And I am in content stream "cs-identifier"
    And I am user identified by "initiating-user-identifier"
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "cs-identifier"                        |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                         |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
      | coveredDimensionSpacePoints | [{}]                          |
      | nodeAggregateClassification | "root"                        |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                     |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | nodeName                      | "document"                                |
      | nodeAggregateClassification   | "regular"                                 |
    And the graph projection is fully up to date

  Scenario: Try to disable a node aggregate in a non-existing content stream
    When the command DisableNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                    |
      | contentStreamIdentifier      | "i-do-not-exist"         |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to disable a node aggregate in a non-existing dimension space point
    When the command DisableNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                       |
      | nodeAggregateIdentifier      | "sir-david-nodenborough"    |
      | coveredDimensionSpacePoint   | {"undeclared": "undefined"} |
      | nodeVariantSelectionStrategy | "allVariants"               |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to disable a non-existing node aggregate
    When the command DisableNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value            |
      | nodeAggregateIdentifier      | "i-do-not-exist" |
      | coveredDimensionSpacePoint   | {}               |
      | nodeVariantSelectionStrategy | "allVariants"    |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to disable an already disabled node aggregate
    Given the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                    |
      | contentStreamIdentifier      | "cs-identifier"          |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [{}]                     |
    And the graph projection is fully up to date

    When the command DisableNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                    |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDisablesDimensionSpacePoint"

  Scenario: Try to disable a node aggregate in a dimension space point it does not cover
    Given I have the following content dimensions:
      | Identifier | Default | Values       | Generalizations |
      | language   | mul     | mul, de, gsw | gsw->de->mul    |
    When the command DisableNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                    |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"language": "de"}       |
      | nodeVariantSelectionStrategy | "allVariants"            |

    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint"
