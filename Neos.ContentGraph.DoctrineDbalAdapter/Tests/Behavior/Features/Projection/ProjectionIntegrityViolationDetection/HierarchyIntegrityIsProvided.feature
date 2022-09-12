@contentrepository
Feature: Run integrity violation detection regarding hierarchy relations and nodes

  As a user of the CR I want to know whether there are nodes or hierarchy relations with invalid hashes or parents / children

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
      | newContentStreamId | "cs-identifier"                        |
    And the graph projection is fully up to date
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                    |
      | contentStreamId     | "cs-identifier"                                          |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"                                 |
      | nodeTypeName                | "Neos.ContentRepository:Root"                            |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000"                   |
      | nodeAggregateClassification | "root"                                                   |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                    |
      | contentStreamId       | "cs-identifier"                                          |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint     | {"language":"de"}                                        |
      | coveredDimensionSpacePoints   | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                 |
      | nodeName                      | "child-document"                                         |
      | nodeAggregateClassification   | "regular"                                                |
    And the graph projection is fully up to date

  Scenario: Detach a hierarchy relation from its parent
    When I add the following hierarchy relation:
      | Key                           | Value             |
      | contentStreamId       | "cs-identifier"   |
      | dimensionSpacePoint           | {"language":"de"} |
      | parentNodeAggregateIdentifier | "i-do-not-exist"  |
      | childNodeAggregateIdentifier  | "i-do-not-exist"  |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597909228

  Scenario: Change a hierarchy relation's dimension space point hash
    When I change the following hierarchy relation's dimension space point hash:
      | Key                           | Value                    |
      | contentStreamId       | "cs-identifier"          |
      | dimensionSpacePoint           | {"language":"gsw"}       |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford" |
      | childNodeAggregateIdentifier  | "nody-mc-nodeface"       |
      | newDimensionSpacePointHash    | "invalidhash"            |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597909228

  # Scenario: Change a node's origin dimension space point hash
  # This is rendundant since a node with invalid origin DSP hash will not cover its origin (see AllNodesCoverTheirOrigin)
  # or the covering relation's hash is also corrupted which is already detected by above test
