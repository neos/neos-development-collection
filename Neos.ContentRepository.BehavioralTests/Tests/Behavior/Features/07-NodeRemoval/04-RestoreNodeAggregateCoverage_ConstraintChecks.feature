@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Restore NodeAggregate coverage

  As a user of the CR I want to be able to restore coverage of a NodeAggregate or parts of it.

  These are the base test cases for the NodeAggregateCommandHandler to block invalid commands.

  Background:
    Given I have the following content dimensions:
      | Identifier | Values          | Generalizations |
      | language   | en, de, gsw, fr | gsw->de->en     |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Tethered': []
    'Neos.ContentRepository.Testing:LeafDocument': []
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
    """
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {"language":"en"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeTypeName                                | parentNodeAggregateId  | nodeName | tetheredDescendantNodeAggregateIds |
      | sir-david-nodenborough | Neos.ContentRepository.Testing:Document     | lady-eleonode-rootford | document | {"tethered":"nodewyn-tetherton"}   |
      | nody-mc-nodeface       | Neos.ContentRepository.Testing:LeafDocument | lady-eleonode-rootford | document | []                                 |
      | nodimus-mediocre       | Neos.ContentRepository.Testing:LeafDocument | nody-mc-nodeface       | document | []                                 |
    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"language":"de"}    |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date


  Scenario: Try to restore coverage of a node aggregate in a non-existing content stream
    When the command RestoreNodeAggregateCoverage is executed with payload and exceptions are caught:
      | Key                        | Value                     |
      | contentStreamId            | "i-do-not-exist"          |
      | nodeAggregateId            | "sir-david-nodenborough"  |
      | dimensionSpacePointToCover | {"language":"de"}         |
      | withSpecializations        | false                     |
      | recursionMode              | "onlyTetheredDescendants" |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to restore coverage of a non-existing node aggregate
    When the command RestoreNodeAggregateCoverage is executed with payload and exceptions are caught:
      | Key                        | Value                     |
      | nodeAggregateId            | "i-do-not-exist"          |
      | dimensionSpacePointToCover | {"language":"de"}         |
      | withSpecializations        | false                     |
      | recursionMode              | "onlyTetheredDescendants" |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to restore coverage of a tethered node aggregate
    When the command RestoreNodeAggregateCoverage is executed with payload and exceptions are caught:
      | Key                        | Value                     |
      | nodeAggregateId            | "nodewyn-tetherton"       |
      | dimensionSpacePointToCover | {"language":"de"}         |
      | withSpecializations        | false                     |
      | recursionMode              | "onlyTetheredDescendants" |
    Then the last command should have thrown an exception of type "NodeAggregateIsTethered"

  Scenario: Try to restore coverage of a root node aggregate
    When the command RestoreNodeAggregateCoverage is executed with payload and exceptions are caught:
      | Key                        | Value                     |
      | nodeAggregateId            | "lady-eleonode-rootford"  |
      | dimensionSpacePointToCover | {"language":"de"}         |
      | withSpecializations        | false                     |
      | recursionMode              | "onlyTetheredDescendants" |
    Then the last command should have thrown an exception of type "NodeAggregateIsRoot"

  Scenario: Try to restore coverage of a node aggregate in a non-existing dimension space point
    When the command RestoreNodeAggregateCoverage is executed with payload and exceptions are caught:
      | Key                        | Value                       |
      | nodeAggregateId            | "sir-david-nodenborough"    |
      | dimensionSpacePointToCover | {"undeclared": "undefined"} |
      | withSpecializations        | false                       |
      | recursionMode              | "onlyTetheredDescendants"   |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to restore coverage of a node aggregate in a dimension space point that has no (primary) generalization to restore coverage from
    When the command RestoreNodeAggregateCoverage is executed with payload and exceptions are caught:
      | Key                        | Value                     |
      | nodeAggregateId            | "sir-david-nodenborough"  |
      | dimensionSpacePointToCover | {"language": "fr"}        |
      | withSpecializations        | false                     |
      | recursionMode              | "onlyTetheredDescendants" |
    Then the last command should have thrown an exception of type "DimensionSpacePointHasNoPrimaryGeneralization"

  Scenario: Try to restore coverage of a node aggregate in a dimension space point whose primary generalization is not covered by the node aggregate
    When the command RestoreNodeAggregateCoverage is executed with payload and exceptions are caught:
      | Key                        | Value                     |
      | nodeAggregateId            | "nody-mc-nodeface"        |
      | dimensionSpacePointToCover | {"language":"gsw"}        |
      | withSpecializations        | false                     |
      | recursionMode              | "onlyTetheredDescendants" |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint"

  Scenario: Try to restore coverage of a node aggregate in a dimension space point the node aggregate already covers
    When the command RestoreNodeAggregateCoverage is executed with payload and exceptions are caught:
      | Key                        | Value                     |
      | nodeAggregateId            | "sir-david-nodenborough"  |
      | dimensionSpacePointToCover | {"language":"gsw"}        |
      | withSpecializations        | false                     |
      | recursionMode              | "onlyTetheredDescendants" |
    Then the last command should have thrown an exception of type "NodeAggregateAlreadyCoversDimensionSpacePoint"

  Scenario: Try to restore coverage of a node aggregate in a dimension space point the node aggregate's parent does not cover
    When the command RestoreNodeAggregateCoverage is executed with payload and exceptions are caught:
      | Key                        | Value                     |
      | nodeAggregateId            | "nodimus-mediocre"        |
      | dimensionSpacePointToCover | {"language":"de"}         |
      | withSpecializations        | false                     |
      | recursionMode              | "onlyTetheredDescendants" |
    Then the last command should have thrown an exception of type "ParentsNodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint"
