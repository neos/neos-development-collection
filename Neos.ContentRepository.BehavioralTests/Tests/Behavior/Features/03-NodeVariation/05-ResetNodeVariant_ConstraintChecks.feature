@fixtures @adapters=DoctrineDBAL,Postgres
Feature: Reset a node variant

  As a user of the CR I want to reset a variant to its closest generalization

  These are the test cases for the command handler to block invalid commands

  Background:
    Given I have the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | en, de, gsw | gsw->de->en     |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:Tethered': []
    'Neos.ContentRepository.Testing:TetheredDocument':
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
      | initiatingUserIdentifier   | "user-id"            |
    And I am in content stream "cs-identifier" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | originDimensionSpacePoint | nodeName | parentNodeAggregateIdentifier | nodeTypeName                            | tetheredDescendantNodeAggregateIdentifiers |
      | nody-mc-nodeface        | {"language":"de"}         | document | lady-eleonode-rootford        | Neos.ContentRepository.Testing:Document | {"tethered": "lady-nodewyn-tetherton"}     |

  Scenario: Try to reset a variant in a content stream that does not exist yet
    When the command ResetNodeVariant is executed with payload and exceptions are caught:
      | Key                       | Value                |
      | contentStreamIdentifier   | "i-do-not-exist-yet" |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"   |
      | originDimensionSpacePoint | {"language":"de"}    |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to reset a variant in a node aggregate that does currently not exist
    When the command ResetNodeVariant is executed with payload and exceptions are caught:
      | Key                       | Value             |
      | nodeAggregateIdentifier   | "i-do-not-exist"  |
      | originDimensionSpacePoint | {"language":"de"} |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to reset a variant in a root node aggregate
    When the command ResetNodeVariant is executed with payload and exceptions are caught:
      | Key                       | Value                    |
      | nodeAggregateIdentifier   | "lady-eleonode-rootford" |
      | originDimensionSpacePoint | {"language":"de"}        |
    Then the last command should have thrown an exception of type "NodeAggregateIsRoot"

  Scenario: Try to reset a variant from a dimension space point that does not exist
    When the command ResetNodeVariant is executed with payload and exceptions are caught:
      | Key                       | Value                      |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"         |
      | originDimensionSpacePoint | {"undeclared":"undefined"} |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to reset a variant from a dimension space point that the node aggregate does not occupy
    When the command ResetNodeVariant is executed with payload and exceptions are caught:
      | Key                       | Value              |
      | nodeAggregateIdentifier   | "nody-mc-nodeface" |
      | originDimensionSpacePoint | {"language":"gsw"} |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNotYetOccupied"

  Scenario: Try to reset a variant from a dimension space point that has no generalization occupied by the node aggregate
    When the command ResetNodeVariant is executed with payload and exceptions are caught:
      | Key                       | Value              |
      | nodeAggregateIdentifier   | "nody-mc-nodeface" |
      | originDimensionSpacePoint | {"language":"de"}  |
    Then the last command should have thrown an exception of type "NodeAggregateOccupiesNoGeneralization"
