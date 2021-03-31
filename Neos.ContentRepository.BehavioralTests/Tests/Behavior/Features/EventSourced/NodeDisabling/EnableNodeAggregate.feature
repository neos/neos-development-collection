@fixtures
Feature: Enable a node aggregate

  As a user of the CR I want to enable a disabled node and expect its descendants that have been directly disabled by this node to become enabled as well.

  These are the base test cases for the NodeAggregateCommandHandler to block invalid commands.

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                            | Value                                  |
      | workspaceName                  | "live"                                 |
      | workspaceTitle                 | "Live"                                 |
      | workspaceDescription           | "The live workspace"                   |
      | initiatingUserIdentifier       | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier     | "cs-identifier"                        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                  |
      | contentStreamIdentifier     | "cs-identifier"                        |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"               |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
      | coveredDimensionSpacePoints | [{}]                                   |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000" |
      | nodeAggregateClassification | "root"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | nodeName                      | "document"                                |
      | nodeAggregateClassification   | "regular"                                 |
    And the graph projection is fully up to date

  Scenario: Try to enable a node aggregate in a non-existing content stream
    When the command EnableNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                    |
      | contentStreamIdentifier      | "i-do-not-exist"         |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to enable a node aggregate in a non-existing dimension space point
    When the command EnableNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                       |
      | contentStreamIdentifier      | "cs-identifier"             |
      | nodeAggregateIdentifier      | "sir-david-nodenborough"    |
      | coveredDimensionSpacePoint   | {"undeclared": "undefined"} |
      | nodeVariantSelectionStrategy | "allVariants"               |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to enable a non-existing node aggregate
    When the command EnableNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value            |
      | contentStreamIdentifier      | "cs-identifier"  |
      | nodeAggregateIdentifier      | "i-do-not-exist" |
      | coveredDimensionSpacePoint   | {}               |
      | nodeVariantSelectionStrategy | "allVariants"    |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to enable an already enabled node aggregate

    When the command EnableNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                    |
      | contentStreamIdentifier      | "cs-identifier"          |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotDisableDimensionSpacePoint"
