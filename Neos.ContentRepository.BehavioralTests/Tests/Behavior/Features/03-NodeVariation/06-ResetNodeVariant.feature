@fixtures @adapters=DoctrineDBAL,Postgres
Feature: Reset a node variant

  As a user of the CR I want to reset a variant to its closest generalization

  Background:
    Given I have the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | en, de, gsw | gsw->de->en     |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:Tethered': []
    'Neos.ContentRepository.Testing:TetheringDocument':
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
    And I am in content stream "cs-identifier" and dimension space point {"language":"en"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier    | originDimensionSpacePoint | nodeName | parentNodeAggregateIdentifier | nodeTypeName                                     | tetheredDescendantNodeAggregateIdentifiers |
      | nody-mc-nodeface           | {"language":"en"}         | document | lady-eleonode-rootford        | Neos.ContentRepository.Testing:TetheringDocument | {"tethered": "lady-nodewyn-tetherton"}     |
      | sir-nodeward-nodington-iii | {"language":"en"}         | esquire  | nody-mc-nodeface              | Neos.ContentRepository.Testing:Document          | {}                                         |
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateIdentifier | "nody-mc-nodeface" |
      | sourceOrigin            | {"language":"en"}  |
      | targetOrigin            | {"language":"de"}  |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateIdentifier | "nody-mc-nodeface" |
      | sourceOrigin            | {"language":"de"}  |
      | targetOrigin            | {"language":"gsw"} |
    And the graph projection is fully up to date

  Scenario: Reset a node variant to its origin
    When the command ResetNodeVariant is executed with payload:
      | Key                       | Value              |
      | contentStreamIdentifier   | "cs-identifier"    |
      | nodeAggregateIdentifier   | "nody-mc-nodeface" |
      | originDimensionSpacePoint | {"language":"gsw"} |
    Then I expect exactly 10 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 9 is of type "NodeVariantWasReset" with payload:
      | Key                     | Expected           |
      | contentStreamIdentifier | "cs-identifier"    |
      | nodeAggregateIdentifier | "nody-mc-nodeface" |
      | sourceOrigin            | {"language":"gsw"} |
      | generalizationOrigin    | {"language":"de"}  |
