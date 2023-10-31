@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Constraint checks on node aggregate disabling

  As a user of the CR I want to disable a node aggregate and expect its descendants to also be disabled.

  These are the base test cases for the NodeAggregateCommandHandler to block invalid commands.

  Background:
    Given using the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | de, gsw, en | gsw->de, en     |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamId | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId | nodeName |
      | sir-david-nodenborough  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | document |

  Scenario: Try to disable a node aggregate in a non-existing content stream
    When the command DisableNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                                  |
      | contentStreamId      | "i-do-not-exist"                       |
      | nodeAggregateId      | "sir-david-nodenborough"               |
      | nodeVariantSelectionStrategy | "allVariants"                          |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to disable a non-existing node aggregate
    When the command DisableNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                                  |
      | nodeAggregateId      | "i-do-not-exist"                       |
      | nodeVariantSelectionStrategy | "allVariants"                          |
      | tag                          | "disabled"                    |

  Scenario: Try to disable an already disabled node aggregate
    Given the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                                  |
      | nodeAggregateId      | "sir-david-nodenborough"               |
      | coveredDimensionSpacePoint   | {"language": "de"}            |
      | nodeVariantSelectionStrategy | "allVariants"                          |
    And the graph projection is fully up to date

      # Note: The behavior has been changed with https://github.com/neos/neos-development-collection/pull/4284 and the test was adjusted accordingly
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                                  |
      | nodeAggregateId      | "sir-david-nodenborough"               |
      | coveredDimensionSpacePoint   | {"language": "de"}                                     |
      | nodeVariantSelectionStrategy | "allVariants"                          |
    Then I expect exactly 4 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 3 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                               |
      | contentStreamId              | "cs-identifier"                        |
      | nodeAggregateId              | "sir-david-nodenborough"               |
      | affectedDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}] |
      | tag                          | "disabled"                             |


  Scenario: Try to disable a node aggregate in a non-existing dimension space point
    When the command DisableNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                                  |
      | nodeAggregateId      | "sir-david-nodenborough"               |
      | coveredDimensionSpacePoint   | {"undeclared": "undefined"}            |
      | nodeVariantSelectionStrategy | "allVariants"                          |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to disable a node aggregate in a dimension space point it does not cover
    When the command DisableNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                                  |
      | nodeAggregateId      | "sir-david-nodenborough"               |
      | coveredDimensionSpacePoint   | {"language": "en"}            |
      | nodeVariantSelectionStrategy | "allVariants"                          |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint"
