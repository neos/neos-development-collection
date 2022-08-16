@contentrepository
Feature: Run integrity violation detection regarding restriction relations

  As a user of the CR I want to know whether there are nodes with restriction relations missing from their ancestors

  Background:
    Given I have the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | de, gsw, fr | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "cs-identifier"                        |
    And the graph projection is fully up to date
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                    |
      | contentStreamIdentifier     | "cs-identifier"                                          |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"                                 |
      | nodeTypeName                | "Neos.ContentRepository:Root"                            |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000"                   |
      | nodeAggregateClassification | "root"                                                   |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                    |
      | contentStreamIdentifier       | "cs-identifier"                                          |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint     | {"language":"de"}                                        |
      | coveredDimensionSpacePoints   | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                 |
      | nodeName                      | "document"                                               |
      | nodeAggregateClassification   | "regular"                                                |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                    |
      | contentStreamIdentifier       | "cs-identifier"                                          |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint     | {"language":"de"}                                        |
      | coveredDimensionSpacePoints   | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                             |
      | nodeName                      | "child-document"                                         |
      | nodeAggregateClassification   | "regular"                                                |
    And the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                                                    |
      | contentStreamIdentifier      | "cs-identifier"                                          |
      | nodeAggregateIdentifier      | "sir-david-nodenborough"                                 |
      | affectedDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
    And the graph projection is fully up to date

  Scenario: Detach a restriction relation from its origin
    When I detach the following restriction relation from its origin:
      | Key                             | Value                    |
      | contentStreamIdentifier         | "cs-identifier"          |
      | dimensionSpacePoint             | {"language":"de"}        |
      | originNodeAggregateIdentifier   | "sir-david-nodenborough" |
      | affectedNodeAggregateIdentifier | "nody-mc-nodeface"       |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597846598

  Scenario: Detach a restriction relation from its target
    When I detach the following restriction relation from its target:
      | Key                             | Value                    |
      | contentStreamIdentifier         | "cs-identifier"          |
      | dimensionSpacePoint             | {"language":"de"}        |
      | originNodeAggregateIdentifier   | "sir-david-nodenborough" |
      | affectedNodeAggregateIdentifier | "sir-david-nodenborough" |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597846598
