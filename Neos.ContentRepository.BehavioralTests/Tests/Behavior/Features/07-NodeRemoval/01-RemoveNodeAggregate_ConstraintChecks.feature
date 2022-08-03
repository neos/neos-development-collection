@fixtures @adapters=DoctrineDBAL,Postgres
Feature: Remove NodeAggregate

  As a user of the CR I want to be able to remove a NodeAggregate or parts of it.

  These are the base test cases for the NodeAggregateCommandHandler to block invalid commands.

  Background:
    Given I have the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | de, gsw, en | gsw->de, en     |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Tethered': []
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
    """
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | nodeTypeName                            | parentNodeAggregateIdentifier | nodeName | tetheredDescendantNodeAggregateIdentifiers |
      | sir-david-nodenborough  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | document | {"tethered":"nodewyn-tetherton"}           |

  Scenario: Try to remove a node aggregate in a non-existing content stream
    When the command RemoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                    |
      | contentStreamIdentifier      | "i-do-not-exist"         |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"language":"de"}        |
      | nodeVariantSelectionStrategy | "allVariants"            |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to remove a non-existing node aggregate
    When the command RemoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value             |
      | nodeAggregateIdentifier      | "i-do-not-exist"  |
      | coveredDimensionSpacePoint   | {"language":"de"} |
      | nodeVariantSelectionStrategy | "allVariants"     |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to remove a tethered node aggregate
    When the command RemoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value               |
      | nodeAggregateIdentifier      | "nodewyn-tetherton" |
      | nodeVariantSelectionStrategy | "allVariants"       |
      | coveredDimensionSpacePoint   | {"language":"de"}   |
    Then the last command should have thrown an exception of type "TetheredNodeAggregateCannotBeRemoved"

  Scenario: Try to remove a node aggregate in a non-existing dimension space point
    When the command RemoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                       |
      | nodeAggregateIdentifier      | "sir-david-nodenborough"    |
      | coveredDimensionSpacePoint   | {"undeclared": "undefined"} |
      | nodeVariantSelectionStrategy | "allVariants"               |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to remove a node aggregate in a dimension space point the node aggregate does not cover
    When the command RemoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                       |
      | nodeAggregateIdentifier      | "sir-david-nodenborough"    |
      | coveredDimensionSpacePoint   | {"language": "en"} |
      | nodeVariantSelectionStrategy | "allVariants"               |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint"

  Scenario: Try to remove a node aggregate using a non-existent removalAttachmentPoint
    When the command RemoveNodeAggregate is executed with payload and exceptions are caught:
      | Key                          | Value                    |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | coveredDimensionSpacePoint   | {"language":"de"}        |
      | removalAttachmentPoint       | "i-do-not-exist"         |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"
